<?php

namespace App\Services\Concerns;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;
use Throwable;

/**
 * @template TAttribute
 */
trait WorksWithAttributedModels
{
    /**
     * @var array<class-string<Model>, TAttribute>
     */
    protected array $models = [];

    /**
     * @param  class-string<TAttribute>  $attribute
     *
     * @throws Throwable
     */
    protected function load(string $attribute): void
    {
        try {
            $this->models = collect(models())
                ->mapWithKeys(function ($cls) use ($attribute): array {
                    $ref = new ReflectionClass($cls);

                    if (! $ref->isInstantiable()) {
                        return [];
                    }

                    /** @var ReflectionAttribute<TAttribute>|null $attr */
                    if (empty($attr = collect($ref->getAttributes($attribute))->first())) {
                        return [];
                    }

                    return [
                        $cls => $attr->newInstance(),
                    ];
                })
                ->all();
        } catch (Throwable $e) {
            resolve(ExceptionHandler::class)->report($e);
        }
    }

    /**
     * @param  Model|class-string<Model>  $model
     */
    public function exists(Model|string $model): bool
    {
        if (! is_string($model)) {
            $model = get_class($model);
        }

        return array_key_exists($model, $this->models);
    }

    /**
     * @return array<int, class-string<Model>>
     */
    public function models(): array
    {
        return array_keys($this->models);
    }

    /**
     * @return array<class-string<Model>, TAttribute>
     */
    public function attributes(): array
    {
        return $this->models;
    }

    /**
     * @param  Model|class-string<Model>  $model
     * @return TAttribute
     */
    public function attribute(Model|string $model): object
    {
        if (! is_string($model)) {
            $model = get_class($model);
        }

        return $this->models[$model] ?? throw new RuntimeException("Model '{$model}' is not attributed.");
    }
}
