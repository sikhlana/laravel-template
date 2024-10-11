<?php

namespace App\Services;

use Dedoc\Scramble\Infer\Reflector\ClassReflector;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node\Name;
use PHPStan\PhpDocParser\Ast\PhpDoc\MixinTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use Throwable;

class ResourceService extends Service
{
    /**
     * @var array<class-string<Model>, class-string<JsonResource>>
     */
    public readonly array $models;

    /**
     * @throws Throwable
     */
    public function __construct(Lexer $lexer, PhpDocParser $parser, ExceptionHandler $exceptionHandler)
    {
        try {
            $classes = discover(app_path('Http/Resources'))
                ->extending(JsonResource::class)
                ->get();

            $this->models = collect($classes)
                ->flatMap(function (string $cls) use ($lexer, $parser): array {
                    $ref = new \ReflectionClass($cls);

                    if (! ($comment = $ref->getDocComment())) {
                        return [];
                    }

                    $node = $parser->parse(new TokenIterator($lexer->tokenize($comment)));
                    $ctx = ClassReflector::make($cls)->getNameContext();

                    return collect($node->getMixinTagValues())
                        ->mapWithKeys(function (MixinTagValueNode $node) use ($cls, $ctx): array {
                            if (! $node->type instanceof IdentifierTypeNode) {
                                return [];
                            }

                            $name = new Name($node->type->name);

                            if (! $name->isQualified()) {
                                $name = $ctx->getResolvedClassName($name);
                            }

                            $model = $name->toString();

                            try {
                                $ref = new \ReflectionClass($model);

                                return [
                                    $ref->getName() => $cls,
                                ];
                            } catch (\ReflectionException) {
                                return [];
                            }
                        })
                        ->all();
                })
                ->all();
        } catch (Throwable $e) {
            $exceptionHandler->report($e);
        }
    }
}
