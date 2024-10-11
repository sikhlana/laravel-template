<?php

namespace App\Models;

use App\Attributes\Authenticatable;
use App\Attributes\Metadata;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Model;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Override;
use Spatie\Permission\Traits\HasRoles;

#[Metadata(
    endpoint: '/users',
)]
#[Authenticatable(
    roles: UserRole::class,
)]
class User extends Model implements HasApiTokensContract
{
    use HasApiTokens, HasRoles, Searchable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
