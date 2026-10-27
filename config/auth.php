<?php

use App\Infrastructure\Persistence\Eloquent\UserModel;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard". The API is
    | stateless: protected routes use the Passport "api" guard (bearer token).
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'api'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Every guard has a user provider, which defines how users are retrieved.
    |
    | Supported: "session", "passport"
    |
    */

    'guards' => [
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', UserModel::class),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Token Lifetime
    |--------------------------------------------------------------------------
    |
    | Minutes before an access token issued by the login service expires.
    |
    */

    'token_expiration_minutes' => (int) env('AUTH_TOKEN_EXPIRATION_MINUTES', 30),

];
