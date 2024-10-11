<?php

namespace App\Support\Scramble\Extensions;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType as GeneratorObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use UnitEnum;

class EnumOptionsResponseExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $returnType = $routeInfo->getReturnType();

        if (
            $returnType instanceof Generic
            && $returnType->name === 'enum-options'
            && count($returnType->templateTypes) >= 1
            && $returnType->templateTypes[0] instanceof ObjectType
            && class_exists($returnType->templateTypes[0]->name)
            && is_subclass_of($returnType->templateTypes[0]->name, UnitEnum::class)
        ) {
            $enum = $returnType->templateTypes[0]->name;

            if (! enum_exists($enum)) {
                return;
            }

            $operation->addResponse(
                Response::make(200)->setContent(
                    'application/json',
                    Schema::fromType(
                        (new GeneratorObjectType)
                            ->addProperty('data', (new ArrayType)->setItems(
                                (new GeneratorObjectType)
                                    ->addProperty('value', $this->openApiTransformer->transform(new ObjectType($enum)))
                                    ->addProperty('label', new StringType)
                            )),
                    ),
                ),
            );
        }
    }
}
