<?php

namespace App\Services;

use App\Support\PhpParser\NodeVisitor\PolicyResolver;
use Dedoc\Scramble\Infer\Services\FileParser;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use ReflectionClass;
use Spatie\Permission\Models\Permission;
use Throwable;

class PolicyService extends Service
{
    /**
     * @var array<string, array<string, array{depends_on?: array<class-string<Model>, array<int, string>>, permissions?: array<class-string<Model>, array<int, string>>}>>
     */
    public readonly array $permissions;

    /**
     * @throws Throwable
     */
    public function __construct(FileParser $parser, ExceptionHandler $exceptionHandler)
    {
        try {
            $traverser = new NodeTraverser(
                $nameResolver = new NameResolver,
                $policyResolver = new PolicyResolver($nameResolver),
            );

            $classes = discover(app_path('Policies'))->get();

            collect($classes)
                ->each(function (string $cls) use ($parser, $traverser): void {
                    $ref = new ReflectionClass($cls);

                    if (! $ref->isInstantiable()) {
                        return;
                    }

                    if (! ($filename = $ref->getFileName())) {
                        return;
                    }

                    if (! ($content = file_get_contents($filename))) {
                        return;
                    }

                    $nodes = $parser->parseContent($content)->getStatements();
                    $traverser->traverse($nodes);
                });

            $this->permissions = $policyResolver->permissions;
        } catch (Throwable $e) {
            $exceptionHandler->report($e);
        }
    }
}
