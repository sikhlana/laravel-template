<?php

namespace App\Console\Commands\Seed;

use App\Attributes\Authenticatable;
use App\Services\AuthService;
use BackedEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RolesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'seed:roles';

    /**
     * @var string
     */
    protected $description = 'Seeds any new roles that do not exist in the database.';

    public function handle(AuthService $service): int
    {
        if (! Schema::hasTable('roles')) {
            $this->error('`roles` table does not exist.');

            return 1;
        }

        collect($service->attributes())
            ->each(function (Authenticatable $attr, string $model): void {
                if (is_null($attr->roles)) {
                    return;
                }

                collect($attr->roles::cases())
                    ->each(
                        fn (BackedEnum $role) => Role::findOrCreate($role->value, $model),
                    );
            });

        return 0;
    }
}
