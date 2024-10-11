<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateAcceptableContentTypes
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->acceptsHtml() || $request->acceptsAnyContentType()) {
            abort(406, 'Invalid requested media type');
        }

        return $next($request);
    }
}
