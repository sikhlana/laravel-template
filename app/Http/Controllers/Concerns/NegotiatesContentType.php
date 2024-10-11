<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait NegotiatesContentType
{
    protected function accepts(Request $request, string $type): bool
    {
        if ($request->acceptsJson()) {
            return false;
        }

        return $request->accepts(sprintf('application/vnd.%s+json', $type));
    }
}
