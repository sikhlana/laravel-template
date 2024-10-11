<?php

use App\Http\Middleware\ValidateAcceptableContentTypes;
use Dedoc\Scramble\Scramble;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Broadcast;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(function () {
        Broadcast::routes();
        Scramble::registerUiRoute('/docs');
        Scramble::registerJsonSpecificationRoute('/docs.json');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [ValidateAcceptableContentTypes::class])
            ->statefulApi()
            ->throttleApi()
            ->throttleWithRedis();
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('sanctum:prune-expired')
            ->everyMinute()
            ->sentryMonitor();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
        $exceptions->shouldRenderJsonWhen(fn () => true);
    })->create();
