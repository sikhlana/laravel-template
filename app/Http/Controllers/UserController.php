<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\RespondsWithEnumOptions;
use App\Http\Controllers\Concerns\RespondsWithResourceList;
use App\Http\Requests\ListRequest;
use App\Http\Requests\User\SaveRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

#[Prefix('/users')]
class UserController extends Controller
{
    use AuthorizesRequests, RespondsWithEnumOptions, RespondsWithResourceList;

    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(User::class);
    }

    /**
     * List all users
     *
     * @response resource-list<User>
     */
    #[Get('/')]
    public function index(ListRequest $request)
    {
        return $this->respondResourceList($request, User::class);
    }

    /**
     * Create a new user
     */
    #[Post('/')]
    public function store(SaveRequest $request): UserResource
    {
        $this->save($request, $user = new User);

        return new UserResource($user);
    }

    /**
     * View a user
     */
    #[Get('/{user}')]
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * Update a user
     */
    #[Put('/{user}')]
    public function update(SaveRequest $request, User $user): UserResource
    {
        $this->save($request, $user);

        return new UserResource($user);
    }

    /**
     * Delete a user
     */
    #[Delete('/{user}')]
    public function destroy(User $user): Response
    {
        $user->delete();

        return response()->noContent();
    }

    /**
     * List all user roles as options
     *
     * @response enum-options<UserRole>
     */
    #[Get('_/roles')]
    public function roles(): JsonResponse
    {
        return $this->respondEnumOptions(UserRole::class);
    }

    protected function save(SaveRequest $request, User $user): void
    {
        $user->fill(Arr::except($request->validated(), ['roles']));

        $roles = Role::query()->findMany($request->validated('roles'));
        $user->syncRoles(...$roles);

        $user->save();
    }
}
