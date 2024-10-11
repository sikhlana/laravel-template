<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use UnitEnum;

trait RespondsWithEnumOptions
{
    /**
     * @param  class-string<UnitEnum>  $enum
     *
     * @response array{data: array<int, array{value: string|int, label: string}>}
     */
    protected function respondEnumOptions(string $enum): JsonResponse
    {
        return response()->json([
            'data' => enum_options($enum),
        ]);
    }
}
