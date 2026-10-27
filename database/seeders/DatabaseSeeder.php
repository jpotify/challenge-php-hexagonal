<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\UserModel;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with the demo user used by the API docs.
     */
    public function run(): void
    {
        UserModel::query()->updateOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Demo User', 'password' => 'password'],
        );
    }
}
