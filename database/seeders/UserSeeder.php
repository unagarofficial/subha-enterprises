<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'user_name' => 'admin',
                'password'  => Hash::make('admin123'),
                'user_type' => 'ADMIN',
                'br_code'   => 1,
            ],
            [
                'user_name' => 'user',
                'password'  => Hash::make('user123'),
                'user_type' => 'USER',
                'br_code'   => 1,
            ],
        ]);
    }
}
