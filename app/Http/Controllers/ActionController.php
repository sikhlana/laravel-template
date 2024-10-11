<?php

namespace App\Http\Controllers;

use App\Actions\Action;
use App\Http\Controllers\Concerns\ResolvesClassFromRouteParameter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('/_actions')]
class ActionController extends Controller
{
    use ResolvesClassFromRouteParameter;

    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function modelAndAction(Request $request): array
    {
        return [
            $this->routeFromRouteParameter($request, 'model', Model::class),
            $this->routeFromRouteParameter($request, 'action', Action::class),
        ];
    }

    protected function hash(string $model, string $action, array $ids): string
    {
        return hash('sha256', serialize([
            $model,
            $action,
            collect($ids)->values()->map(strval(...))->sort()->all(),
        ]));
    }
}
