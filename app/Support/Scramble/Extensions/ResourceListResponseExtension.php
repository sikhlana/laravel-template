<?php

namespace App\Support\Scramble\Extensions;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Combined\AnyOf;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType as GeneratorObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class ResourceListResponseExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $returnType = $routeInfo->getReturnType();

        if (
            $returnType instanceof Generic
            && $returnType->name === 'resource-list'
            && count($returnType->templateTypes) >= 1
            && $returnType->templateTypes[0] instanceof ObjectType
            && class_exists($returnType->templateTypes[0]->name)
            && is_subclass_of($returnType->templateTypes[0]->name, Model::class)
        ) {
            $model = $returnType->templateTypes[0]->name;
            $resource = resource_class($model);

            $operation->addResponse(
                $this->openApiTransformer->toResponse(
                    new Generic(AnonymousResourceCollection::class, [
                        new Generic(LengthAwarePaginator::class, [
                            new ObjectType($resource),
                        ]),
                    ]),
                )->setContent(
                    'application/vnd.all+json',
                    Schema::fromType(
                        $this->openApiTransformer->transform(
                            new Generic(AnonymousResourceCollection::class, [
                                new ObjectType($resource),
                            ]),
                        ),
                    ),
                )->setContent(
                    'application/vnd.meta+json',
                    Schema::fromType(
                        (new GeneratorObjectType)
                            ->addProperty('searchable', new BooleanType)
                            ->addProperty('label', new StringType)
                            ->addProperty('label_plural', new StringType)
                            ->addProperty('model', (new StringType)->example($model))
                            ->addProperty('filters', (new ArrayType)->setItems(
                                (new GeneratorObjectType)
                                    ->addProperty('field', new StringType)
                                    ->addProperty('label', new StringType)
                                    ->addProperty('icon', new StringType)
                                    ->addProperty('options', (new ArrayType)->setItems(
                                        (new GeneratorObjectType)
                                            ->addProperty('value', (new AnyOf)->setItems([
                                                new StringType,
                                                new IntegerType,
                                            ]))
                                            ->addProperty('label', new StringType)),
                                    )
                                    ->addProperty('default', new StringType),
                            ))
                            ->addProperty('sorts', new StringType)
                            ->addProperty('actions', (new ArrayType)->setItems(
                                (new GeneratorObjectType)
                                    ->addProperty('action', new StringType)
                                    ->addProperty('label', new StringType)
                                    ->addProperty('icon', new StringType)
                                    ->addProperty('ability', new StringType)
                                    ->addProperty('confirm', new BooleanType)
                                    ->addProperty('rules', (new GeneratorObjectType)->additionalProperties(
                                        (new ArrayType)->setItems(new StringType)
                                    )),
                            ))
                            ->addProperty('total', new IntegerType)
                            ->addProperty('paginate', new BooleanType),
                    ),
                ),
            );
        }
    }
}
