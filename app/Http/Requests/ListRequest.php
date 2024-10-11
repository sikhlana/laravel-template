<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\ValidationRules\Rules\Delimited;

class ListRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'only' => [
                'sometimes',
                'string',
                new Delimited('int'),
            ],
            'selected' => [
                'sometimes',
                'string',
                new Delimited('int'),
            ],
            'search' => [
                'sometimes',
                'string',
            ],
            'filter' => [
                'sometimes',
                'array',
            ],
            'filter.*' => [
                'required',
                'string',
            ],
            'sort' => [
                'sometimes',
                'string',
                new Delimited('string'),
            ],
            'page' => [
                'sometimes',
                'int',
            ],
            'per_page' => [
                'sometimes',
                'int',
            ],
        ];
    }
}
