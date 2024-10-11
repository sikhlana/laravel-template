<?php

namespace App\Http\Controllers;

use App\Exceptions\UnableToAuthenticateException;
use App\Handlers\AuthHandlers\Handler;
use App\Http\Controllers\Concerns\ResolvesClassFromRouteParameter;
use App\Http\Controllers\Concerns\ResolvesVisitor;
use App\Http\Requests\Auth\GrantRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SsoRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use RuntimeException;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;

#[Prefix('/auth')]
class AuthController extends Controller
{
    use AuthenticatesUsers, ResolvesClassFromRouteParameter, ResolvesVisitor, ThrottlesLogins;

    public function __construct(
        protected AuthService $service
    ) {}

    /**
     * Authenticate visitor using login credentials
     *
     * @unauthenticated
     */
    #[Post('/login/{model}')]
    public function login(LoginRequest $request): JsonResponse
    {
        return $this->authenticate(
            $request,
            'login',
            fn (Handler $handler) => $handler->login(
                $request->string($this->username()),
                $request->string('password'),
            ),
        );
    }

    /**
     * Authenticates visitor using grant token
     *
     * @unauthenticated
     */
    #[Post('/grant/{model}')]
    public function grant(GrantRequest $request): JsonResponse
    {
        return $this->authenticate(
            $request,
            'grant',
            fn (Handler $handler) => $handler->grant(
                $request->string('token'),
            ),
        );
    }

    /**
     * Authenticates visitor using SSO token
     *
     * @unauthenticated
     */
    #[Post('/sso/{provider}/{model}')]
    public function sso(SsoRequest $request, string $provider): JsonResponse
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $this->authenticate(
            $request,
            'sso',
            fn (Handler $handler) => $handler->sso(
                $driver->userFromToken(
                    $request->string('token'),
                ),
            ),
        );
    }

    /**
     * Invalidate visitor's bearer token
     */
    #[Post('/logout', middleware: 'auth')]
    public function logout(Request $request): Response
    {
        $user = $this->visitor($request);

        if (
            $user instanceof HasApiTokens &&
            ($token = $user->currentAccessToken()) instanceof PersonalAccessToken
        ) {
            $token->delete();
        }

        return response()->noContent();
    }

    /**
     * @param  callable(Handler<User>): User  $callback
     */
    protected function authenticate(Request $request, string $method, callable $callback): JsonResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        $model = $this->routeFromRouteParameter($request, 'model', Model::class, $this->service->models());
        $handler = resolve($this->service->attribute($model)->handler, ['model' => $model]);

        try {
            $user = $callback($handler);

            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
                $request->session()->regenerate();
            }

            $this->clearLoginAttempts($request);
        } catch (UnableToAuthenticateException) {
            $this->incrementLoginAttempts($request);
            $this->sendFailedLoginResponse($request);
        }

        if (! $user instanceof HasApiTokens) {
            throw new RuntimeException('User model must implement HasApiTokens');
        }

        return response()->json([
            'token' => $user->createToken(sprintf('%s@%s', get_class($handler), $method))->plainTextToken,
        ]);
    }

    protected function username(): string
    {
        return 'username';
    }
}
