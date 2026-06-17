<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::updateOrCreate(
            ['username' => env('ADMIN_USERNAME', 'admin')],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => env('ADMIN_PASSWORD', 'admin123'),
            ]
        );
    }
}
