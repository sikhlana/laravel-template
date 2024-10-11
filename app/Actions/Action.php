<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JsonSerializable;
use Override;
use Sikhlana\Singleton\Singleton;

/**
 * @template TModel of Model
 *
 * @implements Arrayable<string, mixed>
 */
abstract class Action implements Arrayable, JsonSerializable, Singleton
{
    public function label(): string
    {
        return Str::of(static::class)
            ->classBasename()
            ->replaceEnd('Action', '')
            ->snake(' ')
            ->ucfirst();
    }

    abstract public function icon(): string;

    abstract public function ability(): string;

    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $data
     */
    abstract public function __invoke(Model $model, ?User $user, array $data = []): void;

    #[Override]
    public function toArray(): array
    {
        return [
            'action' => static::class,
            'label' => $this->label(),
            'icon' => $this->icon(),
            'ability' => $this->ability(),
            'confirm' => $this instanceof Contracts\RequiresConfirmation,
            'rules' => $this instanceof Contracts\RequiresExtraData ? $this->rules() : null,
        ];
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
