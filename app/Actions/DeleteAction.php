<?php

namespace App\Actions;

use App\Actions\Concerns\WithConfirmationMessage;
use App\Actions\Contracts\RequiresConfirmation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @extends Action<Model>
 */
class DeleteAction extends Action implements RequiresConfirmation
{
    use WithConfirmationMessage;

    #[Override]
    public function icon(): string
    {
        return 'i-ph-trash-fill';
    }

    #[Override]
    public function ability(): string
    {
        return 'delete';
    }

    #[Override]
    public function __invoke(Model $model, ?User $user, array $data = []): void
    {
        $model->delete();
    }
}
