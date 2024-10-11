<?php

namespace App\Console\Commands\Seed;

use App\Services\PolicyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class PermissionsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'seed:permissions';

    /**
     * @var string
     */
    protected $description = 'Seeds any new permissions that do not exist in the database.';

    public function handle(PolicyService $service): int
    {
        if (! Schema::hasTable('permissions')) {
            $this->error('`permissions` table does not exist.');

            return 1;
        }

        collect($service->permissions)
            ->each(function (array $actions): void {
                collect($actions)
                    ->each(function (array $action): void {
                        collect($action['permissions'] ?? [])
                            ->each(function (array $permissions, string $model): void {
                                collect($permissions)
                                    ->each(
                                        fn (string $permission) => Permission::findOrCreate($permission, $model),
                                    );
                            });
                    });
            });

        return 0;
    }
}
