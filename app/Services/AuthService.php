<?php

namespace App\Services;

use App\Attributes\Authenticatable;
use App\Enums\UserRole;
use App\Models\Administrator;
use App\Services\Concerns\WorksWithAttributedModels;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Throwable;

class AuthService extends Service
{
    /**
     * @use WorksWithAttributedModels<Authenticatable>
     */
    use WorksWithAttributedModels;

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        $this->load(Authenticatable::class);

        collect($this->models)
            ->each(function (Authenticatable $attr, string $model): void {
                $name = Str::of($model)
                    ->classBasename()
                    ->snake()
                    ->value();

                config([
                    "auth.guards.{$name}" => [
                        'driver' => 'sanstum',
                        'provider' => $name,
                    ],
                    "auth.providers.{$name}" => [
                        'driver' => 'eloquent',
                        'model' => $model,
                    ],
                    "auth.passwords.{$name}" => [
                        'table' => 'password_reset_tokens',
                        'expire' => 60,
                        'throttle' => 60,
                        ...$attr->passwordResetConfig,
                        'provider' => $name,
                    ],
                ]);
            });

        $this->registerSuperAdminPolicy();
    }

    protected function registerSuperAdminPolicy(): void
    {
        Gate::before(
            fn ($user) => $user instanceof Administrator &&
                $user->hasRole(UserRole::SUPER_ADMIN)
                    ? true
                    : null,
        );
    }
}
