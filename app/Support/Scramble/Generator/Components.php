<?php

namespace App\Support\Scramble\Generator;

use App\Attributes\Extension;
use Dedoc\Scramble\Support\Generator\Components as BaseComponents;
use Illuminate\Support\Str;

#[Extension]
class Components extends BaseComponents
{
    const array PREFIXES = [
        'Http\Requests',
        'Http\Resources',
    ];

    public function uniqueSchemaName(string $fullName): string
    {
        foreach (static::PREFIXES as $prefix) {
            if (Str::contains($fullName, $prefix)) {
                return static::slug(Str::after($fullName, $prefix.'\\'));
            }
        }

        return parent::uniqueSchemaName($fullName);
    }
}
