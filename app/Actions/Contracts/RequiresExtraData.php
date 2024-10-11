<?php

namespace App\Actions\Contracts;

interface RequiresExtraData
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array;
}
