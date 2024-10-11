<?php

namespace App\Services;

use App\Actions\Action;

class ActionService extends Service
{
    /**
     * @var array<int, class-string<Action>>
     */
    public readonly array $actions;

    public function __construct()
    {
        $this->actions = array_values(
            discover(app_path('Actions'))
                ->classes()
                ->extending(Action::class)
                ->get()
        );
    }
}
