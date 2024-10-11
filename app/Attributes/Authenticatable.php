<?php

namespace App\Attributes;

use App\Handlers\AuthHandlers\DefaultHandler;
use App\Handlers\AuthHandlers\Handler;
use Attribute;
use BackedEnum;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class Authenticatable
{
    /**
     * @param  class-string<Handler>  $handler
     * @param  array<string, mixed>  $args
     * @param  class-string<BackedEnum>|null  $roles
     */
    public function __construct(
        public string $handler = DefaultHandler::class,
        public array $args = [],
        public ?string $roles = null,
        public array $passwordResetConfig = [],
    ) {}
}
