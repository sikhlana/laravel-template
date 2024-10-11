<?php

namespace App\Services;

use App\Attributes\Metadata;
use App\Services\Concerns\WorksWithAttributedModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class MetadataService extends Service
{
    /**
     * @use WorksWithAttributedModels<Metadata>
     */
    use WorksWithAttributedModels;

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        $this->load(Metadata::class);
    }

    public function meta(Model|string $model): array
    {
        if (! is_string($model)) {
            $model = $model::class;
        }

        $meta = $this->attribute($model);

        return [
            'searchable' => method_exists($model, 'search'),
            'label' => Str::of($model)->classBasename()->snake(' ')->ucfirst(),
            'label_plural' => Str::of($model)->classBasename()->pluralStudly()->snake(' ')->ucfirst(),
            'model' => $model,
            'filters' => array_map(resolve(...), $meta->filters),
            'sorts' => $meta->sorts,
            'actions' => array_map(resolve(...), $meta->actions),
            'total' => $model::query()->count('id'),
            'paginate' => $meta->paginate,
        ];
    }
}
