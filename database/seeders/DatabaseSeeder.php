<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminUsername = strtolower(trim(env('ADMIN_USERNAME', 'admin')));
        $adminPassword = env('ADMIN_PASSWORD', 'adminsecret');

        $exists = DB::table('user')->where('username', $adminUsername)->exists();
        if (!$exists) {
            DB::table('user')->insert([
                'id' => (string) Str::uuid(),
                'username' => $adminUsername,
                'password_hash' => password_hash($adminPassword, PASSWORD_BCRYPT),
                'role' => 'admin',
            ]);
        }
    }
}
