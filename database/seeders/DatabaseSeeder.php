<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,        // branches first (FK dependency)
            FinancialYearSeeder::class, // financial_years
            MasterDataSeeder::class,    // uoms, categories, taxes
            UserSeeder::class,          // users (needs br_code=1)
        ]);
    }
}
