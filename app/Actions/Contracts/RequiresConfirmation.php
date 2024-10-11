<?php

namespace App\Actions\Contracts;

use Illuminate\Database\Eloquent\Model;

interface RequiresConfirmation
{
    /**
     * @param  class-string<Model>  $model
     */
    public function confirmationMessage(string $model, int $count): string;
}
