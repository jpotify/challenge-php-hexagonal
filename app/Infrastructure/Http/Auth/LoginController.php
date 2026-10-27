<?php

namespace App\Infrastructure\Http\Auth;

use App\Application\Auth\Login;
use App\Application\Auth\LoginCommand;

class LoginController
{
    public function __invoke(LoginRequest $request, Login $login): AccessTokenResource
    {
        $token = $login->execute(new LoginCommand(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
        ));

        return new AccessTokenResource($token);
    }
}
