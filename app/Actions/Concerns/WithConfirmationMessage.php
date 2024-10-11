<?php

namespace App\Actions\Concerns;

use App\Actions\Action;
use Illuminate\Support\Str;
use Override;

/**
 * @mixin Action
 */
trait WithConfirmationMessage
{
    #[Override]
    public function confirmationMessage(string $model, int $count): string
    {
        $model = Str::of($model)->classBasename()->pluralStudly($count)->snake(' ');
        $action = Str::of($this->label())->lower();

        $thisOrThese = $count === 1 ? 'this' : 'these';

        return sprintf('Are you sure you want to %s %s %s?', $action, $thisOrThese, $model);
    }
}
