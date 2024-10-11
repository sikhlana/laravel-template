<?php

namespace App\Support\PhpParser\NodeVisitor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Override;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use SplStack;

class PolicyResolver extends NodeVisitorAbstract
{
    /**
     * @var array<string, array<string, array{depends_on?: array<class-string<Model>, array<int, string>>, permissions?: array<class-string<Model>, array<int, string>>}>>
     */
    public array $permissions = [];

    protected ?string $currentPolicy = null;

    protected ?string $currentAction = null;

    protected ?string $currentUserVariable = null;

    protected ?string $currentUserModel = null;

    /**
     * @param  SplStack<string>  $oldUserModels
     */
    public function __construct(
        protected NameResolver $nameResolver,
        protected SplStack $oldUserModels = new SplStack,
    ) {}

    #[Override]
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            if (is_null($node->name)) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            $policy = $node->name->toString();
            if (! Str::endsWith($policy, 'Policy')) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            $this->currentPolicy = $policy;
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            if (is_null($this->currentPolicy)) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (! $node->isPublic()) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (count($node->params) < 1) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            $this->currentAction = $node->name->toString();
            $userParam = $node->params[0]; // First parameter is always the user model.

            if (! $userParam->type instanceof Node\Name) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (! $userParam->var instanceof Node\Expr\Variable) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (! is_string($userParam->var->name)) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            $this->currentUserModel = $this->resolveName($userParam->type);
            $this->currentUserVariable = $userParam->var->name;
        } elseif ($node instanceof Node\Expr\Instanceof_) {
            if (is_null($this->currentAction)) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (is_null($this->currentUserModel)) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (is_null($this->currentUserVariable)) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (! $node->expr instanceof Node\Expr\Variable) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if ($node->expr->name !== $this->currentUserVariable) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (! $node->class instanceof Node\Name) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (($model = $this->resolveName($node->class)) === $this->currentUserModel) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            $this->oldUserModels->push($this->currentUserModel);
            $this->currentUserModel = $model;
        } elseif ($node instanceof Node\Expr\MethodCall) {
            if (is_null($this->currentAction)) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (is_null($this->currentUserModel)) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (is_null($this->currentUserVariable)) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if (! $node->var instanceof Node\Expr\Variable) {
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }

            if ($node->var->name === $this->currentUserVariable) {
                if (! $node->name instanceof Node\Identifier) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                if (! in_array($node->name->name, ['checkPermissionTo', 'hasPermissionTo'])) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                if (count($node->args) < 1) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                $permission = $node->args[0]; // First argument is always the permission name.

                if (! $permission instanceof Node\Arg) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                if (! $permission->value instanceof Node\Scalar\String_) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                $pointer = [
                    $this->currentPolicy,
                    $this->currentAction,
                    'permissions',
                    $this->currentUserModel,
                ];

                data_set($this->permissions, $pointer, array_unique([
                    ...data_get($this->permissions, $pointer, []),
                    ...$this->inherited($pointer),
                    $permission->value->value,
                ]));
            } elseif ($node->var->name === 'this') {
                if (! $node->name instanceof Node\Identifier) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                if (count($node->args) < 1) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                $user = $node->args[0]; // First argument is always the user model.

                if (! $user instanceof Node\Arg) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                if (! $user->value instanceof Node\Expr\Variable) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                if ($user->value->name !== $this->currentUserVariable) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }

                $pointer = [
                    $this->currentPolicy,
                    $this->currentAction,
                    'depends_on',
                    $this->currentUserModel,
                ];

                data_set($this->permissions, $pointer, array_unique([
                    ...data_get($this->permissions, $pointer, []),
                    ...$this->inherited($pointer),
                    $node->name->name,
                ]));
            }

            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    #[Override]
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->currentPolicy = null;
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            $this->currentAction = null;
            $this->currentUserModel = null;
            $this->currentUserVariable = null;
        } elseif ($node instanceof Node\Stmt\If_ || $node instanceof Node\Stmt\ElseIf_) {
            if (
                $node->cond instanceof Node\Expr\Instanceof_
                && $node->cond->expr instanceof Node\Expr\Variable
                && $node->cond->expr->name === $this->currentUserVariable
                && $node->cond->class instanceof Node\Name
                && $this->currentUserModel === $this->resolveName($node->cond->class)
            ) {
                $this->currentUserModel = $this->oldUserModels->pop();
            }
        }

        return null;
    }

    protected function resolveName(Node\Name $name): string
    {
        if (! $name->isFullyQualified()) {
            $name = $this->nameResolver->getNameContext()->getResolvedClassName($name);
        }

        return $name->toString();
    }

    protected function inherited(array $pointer): array
    {
        $data = [];

        foreach ($this->oldUserModels as $model) {
            $pointer[3] = $model;

            $data = array_unique([
                ...$data,
                ...data_get($this->permissions, $pointer, []),
            ]);
        }

        return $data;
    }
}
