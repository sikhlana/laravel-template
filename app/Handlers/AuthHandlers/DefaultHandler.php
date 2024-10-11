<?php

namespace App\Handlers\AuthHandlers;

use App\Exceptions\UnableToAuthenticateException;
use Illuminate\Foundation\Auth\User;
use Laravel\Socialite\Two\User as SocialiteUser;
use Override;
use SensitiveParameter;

class DefaultHandler extends Handler
{
    /**
     * @param  class-string<User>  $model
     */
    public function __construct(
        string $model,
        protected string $usernameKey = 'email',
        protected string $tokenKey = 'token',
        protected string $passwordKey = 'password',
    ) {
        parent::__construct($model);
    }

    #[Override]
    public function login(string $username, #[SensitiveParameter] string $password): User
    {
        /** @var User|null $user */
        $user = $this->provider()->retrieveByCredentials([
            $this->usernameKey => $username,
        ]);

        if (! $user) {
            throw new UnableToAuthenticateException;
        }

        if (! $this->provider()->validateCredentials($user, [$this->passwordKey => $password])) {
            throw new UnableToAuthenticateException;
        }

        return $user;
    }

    #[Override]
    public function grant(#[SensitiveParameter] string $token): User
    {
        /** @var User|null $user */
        $user = $this->provider()->retrieveByCredentials([
            $this->tokenKey => $token,
        ]);

        if (! $user) {
            throw new UnableToAuthenticateException;
        }

        return $user;
    }

    #[Override]
    public function sso(SocialiteUser $sso): User
    {
        /** @var User|null $user */
        $user = $this->provider()->retrieveByCredentials([
            $this->usernameKey => $sso->email,
        ]);

        if (! $user) {
            throw new UnableToAuthenticateException;
        }

        $user->update([
            'name' => $sso->name,
        ]);

        return $user;
    }
}
