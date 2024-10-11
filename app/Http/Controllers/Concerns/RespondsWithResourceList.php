<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\ListRequest;
use App\Services\MetadataService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;

/**
 * @template TModel of Model
 */
trait RespondsWithResourceList
{
    use NegotiatesContentType;

    /**
     * @param  class-string<TModel>|Builder<TModel>|Relation<TModel>  $subject
     */
    protected function respondResourceList(ListRequest $request, string|Builder|Relation $subject): JsonResponse|JsonResource|StreamedJsonResponse
    {
        $model = match (true) {
            is_string($subject) => $subject,
            $subject instanceof Builder => $subject->getModel(),
            $subject instanceof Relation => $subject->getRelated(),
        };

        if ($this->accepts($request, 'meta')) {
            return response()->json(resolve(MetadataService::class)->meta($model));
        }

        $meta = resolve(MetadataService::class)->attribute($model);

        $builder = QueryBuilder::for($subject, $request)
            ->allowedSorts($meta->sorts)
            ->with($meta->with);

        if ($request->filled('only')) {
            $builder->whereKey(explode(',', $request->string('only')));
        } else {
            $builder->allowedFilters(array_map(value(...), array_map(resolve(...), $meta->filters)));

            if ($request->filled('search') && method_exists($model, 'search')) {
                $builder->whereKey($model::search($request->string('search'))->keys());
            }

            if ($request->filled('selected')) {
                $builder->whereRaw('1 = 1')
                    ->orWhere(
                        fn (Builder $query) => $query->whereKey(
                            explode(',', $request->string('selected'))
                        )
                    );
            }
        }

        if ($this->accepts($request, 'all')) {
            return new StreamedJsonResponse($builder->cursor()->map(resource(...)));
        }

        return resource_class($model)::collection(
            $builder->paginate(
                perPage: $request->integer('per_page', 10),
                page: $request->integer('page', 1),
            ),
        );
    }
}
