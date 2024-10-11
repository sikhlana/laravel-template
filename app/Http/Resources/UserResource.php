<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            '_type' => User::class,
            '_label' => $this->name,
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            /** @var array<int, string> */
            'roles' => $this->whenLoaded('roles', fn ($roles) => $roles->pluck('name')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
