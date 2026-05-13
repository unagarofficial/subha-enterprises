<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('branches')->insert([
            'br_name'  => 'Subha Enterprises',
            'br_place' => 'Machilipatnam',
        ]);
    }
}
