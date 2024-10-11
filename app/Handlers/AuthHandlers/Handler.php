<?php

namespace App\Handlers\AuthHandlers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Foundation\Auth\User;
use Laravel\Socialite\Two\User as SocialiteUser;
use SensitiveParameter;
use Sikhlana\Singleton\Singleton;

/**
 * @template TUser of User
 */
abstract class Handler implements Singleton
{
    /**
     * @param  class-string<TUser>  $model
     */
    public function __construct(
        protected string $model,
    ) {}

    /**
     * @return TUser
     */
    abstract public function login(string $username, #[SensitiveParameter] string $password): User;

    /**
     * @return TUser
     */
    abstract public function grant(#[SensitiveParameter] string $token): User;

    /**
     * @return TUser
     */
    abstract public function sso(SocialiteUser $sso): User;

    protected function provider(): EloquentUserProvider
    {
        return resolve(EloquentUserProvider::class, [
            'model' => $this->model,
        ]);
    }
}
