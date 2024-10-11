<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Permission;
use Spatie\ValidationRules\Rules\ModelsExist;

class AssociateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** @var array<int, int> */
            'ids' => [
                'required',
                'list',
                new ModelsExist(Permission::class),
            ],
        ];
    }
}
