<?php

namespace App\Providers;

use App\Services\Service;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Matchish\ScoutElasticSearch\Engines\ElasticSearchEngine;
use ReflectionClass;
use ReflectionException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Scramble::ignoreDefaultRoutes();
        $this->registerElasticsearchEngine();
        $this->extendScramble();
    }

    public function boot(): void
    {
        Scramble::routes(fn () => true);

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(SecurityScheme::http('bearer'));
        });

        $this->bootServices();
        $this->configureRateLimiters();
    }

    protected function bootServices(): void
    {
        $classes = discover(app_path('Services'))
            ->extending(Service::class)
            ->get();

        collect($classes)
            ->each(
                /**
                 * @throws ReflectionException
                 * @throws BindingResolutionException
                 */
                function (string $cls): void {
                    $ref = new ReflectionClass($cls);

                    if (! $ref->isInstantiable()) {
                        return;
                    }

                    $this->app->make($cls);
                }
            );
    }

    protected function registerElasticsearchEngine(): void
    {
        $this->callAfterResolving(EngineManager::class, function (EngineManager $manager, Application $app) {
            $manager->extend('elasticsearch', fn () => $app->make(ElasticSearchEngine::class));
        });
    }

    protected function configureRateLimiters(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perSecond(60)->by(
            $request->user()?->id ?: $request->ip()
        ));
    }

    /**
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    protected function extendScramble(): void
    {
        $this->callAfterResolving(TypeTransformer::class, function (TypeTransformer $transformer, Application $app) {
            $ref = new ReflectionClass($transformer);
            $property = $ref->getProperty('components');

            $property->setValue($transformer, $app->make(Components::class));
        });
    }
}
