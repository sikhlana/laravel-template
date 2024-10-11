<?php

namespace App\Attributes;

use App\Actions\Action;
use App\Filters\Filter;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class Metadata
{
    /**
     * @param  array<int, class-string<Filter>>  $filters
     * @param  array<int, string>  $sorts
     * @param  array<int, string>  $with
     * @param  array<int, class-string<Action>>  $actions
     */
    public function __construct(
        public string $endpoint,
        public array $filters = [],
        public array $sorts = [],
        public array $with = [],
        public array $actions = [],
        public bool $paginate = true,
    ) {}
}
