<?php

namespace App\Http\Controllers;

use App\Http\Requests\Permission\AssociateRequest;
use App\Http\Resources\PermissionResource;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Permission;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('/users/{user}/permissions')]
class UserPermissionController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * List all associated permissions for a user
     *
     * @return AnonymousResourceCollection<PermissionResource>
     */
    #[Get('/')]
    public function index(User $user): AnonymousResourceCollection
    {
        $this->authorize('view', $user);

        return PermissionResource::collection($user->permissions);
    }

    /**
     * Associate permissions to a user
     */
    #[Put('/')]
    public function associate(AssociateRequest $request, User $user): Response
    {
        $this->authorize('update', $user);

        $permissions = Permission::query()->findMany($request->validated('ids'));
        $user->syncPermissions(...$permissions);

        return response()->noContent();
    }
}
