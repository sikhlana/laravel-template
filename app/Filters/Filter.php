<?php

namespace App\Filters;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;
use Override;
use Sikhlana\Singleton\Singleton;
use Spatie\QueryBuilder\AllowedFilter;

abstract class Filter implements Arrayable, JsonSerializable, Singleton
{
    public function label(): string
    {
        return Str::of(static::class)
            ->classBasename()
            ->replaceEnd('Filter', '')
            ->pluralStudly($this->multiple() ? 2 : 1)
            ->snake(' ')
            ->ucfirst();
    }

    public function multiple(): bool
    {
        return true;
    }

    abstract public function icon(): string;

    /**
     * @return array<int, array{value: int|string, label: string}>
     */
    abstract public function options(): array;

    abstract protected function field(): string;

    protected function internalField(): ?string
    {
        return null;
    }

    public function __invoke(): AllowedFilter
    {
        return AllowedFilter::exact($this->field(), $this->internalField());
    }

    #[Override]
    public function toArray(): array
    {
        return [
            'field' => $this->field(),
            'label' => $this->label(),
            'icon' => $this->icon(),
            'options' => $this->options(),
            'multiple' => $this->multiple(),
        ];
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
