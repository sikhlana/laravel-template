<?php

use App\Services\ResourceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\StructureDiscoverer\Data\DiscoveredStructure;
use Spatie\StructureDiscoverer\Discover;
use Spatie\StructureDiscoverer\Enums\Sort;

if (! function_exists('discover')) {
    /**
     * Discover classes in the given directories.
     */
    function discover(string ...$directories): Discover
    {
        return Discover::in(...$directories)
            ->useReflection(base_path())
            ->sortBy(Sort::CaseInsensitiveName);
    }
}

if (! function_exists('models')) {
    /**
     * Get all models in the application.
     *
     * @return array<int, class-string<Model>>
     */
    function models(): array
    {
        static $models = null;

        if (is_null($models)) {
            $models = discover(app_path())
                ->extending(Model::class)
                ->get();
        }

        return $models;
    }
}

if (! function_exists('resource_class')) {
    /**
     * Get the resource class for the given model.
     *
     * @param  Model|class-string<Model>  $model
     * @return class-string<JsonResource>
     */
    function resource_class(Model|string $model): string
    {
        if (! is_string($model)) {
            $model = get_class($model);
        }

        return resolve(ResourceService::class)->models[$model] ?? throw new RuntimeException("Resource class for model [{$model}] not found.");
    }
}

if (! function_exists('resource')) {
    /**
     * Get the resource object for the given model.
     */
    function resource(?Model $model): ?JsonResource
    {
        if (! $model) {
            return null;
        }

        $cls = resource_class($model);

        return new $cls($model);
    }
}

if (! function_exists('classes_using_trait')) {
    /**
     * Get all classes that use the given trait.
     *
     * @return array<int, class-string>
     */
    function classes_using_trait(string ...$trait): array
    {
        return discover(app_path())
            ->classes()
            ->custom(function (DiscoveredStructure $structure) use (&$trait): bool {
                $traits = trait_uses_recursive($structure->getFcqn());

                if (empty($traits)) {
                    return false;
                }

                return count(array_intersect($traits, $trait)) > 0;
            })
            ->get();
    }
}

if (! function_exists('models_using_trait')) {
    /**
     * Get all models that use the given trait.
     *
     * @return array<int, class-string<Model>>
     */
    function models_using_trait(string ...$trait): array
    {
        return array_filter(models(), function (string $model) use (&$trait): bool {
            $traits = trait_uses_recursive($model);

            if (empty($traits)) {
                return false;
            }

            return count(array_intersect($traits, $trait)) > 0;
        });
    }
}

if (! function_exists('enum_label')) {
    /**
     * Get the label of the given enum.
     */
    function enum_label(UnitEnum $enum): ?string
    {
        return method_exists($enum, 'label') ? $enum->label() : null;
    }
}

if (! function_exists('default_enum_label')) {
    /**
     * Get the default label of the given enum.
     */
    function default_enum_label(UnitEnum $enum): string
    {
        return Str::of($enum->name)->lower()->headline()->value();
    }
}

if (! function_exists('enum_options')) {
    /**
     * Get the options of the given enum.
     *
     * @param  class-string<UnitEnum>  $enum
     * @return Collection<int, array{value: string|int, label: string}>
     */
    function enum_options(string $enum): Collection
    {
        return collect(($enum::cases()))
            ->map(fn (UnitEnum $enum): array => [
                'value' => $enum instanceof BackedEnum ? $enum->value : $enum->name,
                'label' => enum_label($enum) ?? default_enum_label($enum),
            ]);
    }
}
