<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait ResolvesClassFromRouteParameter
{
    /**
     * @param  class-string|null  $parent
     * @param  array<int, class-string>  $allowed
     * @return class-string
     */
    protected function routeFromRouteParameter(Request $request, string $param, ?string $parent = null, array $allowed = []): string
    {
        $message = sprintf('Invalid %s', $parent ? Str::of($parent)->classBasename()->snake(' ') : 'class');
        $cls = $request->route($param);

        if (empty($cls)) {
            throw new BadRequestHttpException($message);
        }

        if (empty($cls = base64_decode($cls))) {
            throw new BadRequestHttpException($message);
        }

        if (! class_exists($cls)) {
            throw new BadRequestHttpException($message);
        }

        if ($parent && ! is_subclass_of($cls, $parent)) {
            throw new BadRequestHttpException($message);
        }

        if (! empty($allowed) && ! in_array($cls, $allowed)) {
            throw new BadRequestHttpException($message);
        }

        return $cls;
    }
}
