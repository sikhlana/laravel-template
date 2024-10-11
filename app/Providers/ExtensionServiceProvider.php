<?php

namespace App\Providers;

use App\Attributes\Extension;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use ReflectionException;

class ExtensionServiceProvider extends ServiceProvider
{
    /**
     * @throws ReflectionException
     */
    public function register(): void
    {
        $classes = discover(app_path())
            ->classes()
            ->get();

        collect($classes)
            ->each(function (string $cls) {
                $ref = new ReflectionClass($cls);

                if (! $ref->isInstantiable()) {
                    return;
                }

                if (! ($parent = $ref->getParentClass())) {
                    return;
                }

                if (count($ref->getAttributes(Extension::class)) > 0) {
                    $this->app->bind($parent->getName(), $cls);
                }
            });
    }
}
