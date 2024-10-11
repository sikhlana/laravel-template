<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesVisitor
{
    protected function visitor(Request $request): User
    {
        return $request->user() ?: throw new HttpException(417, 'Unable to resolve visitor');
    }
}
