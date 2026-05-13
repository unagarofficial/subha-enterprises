<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinancialYearSeeder extends Seeder
{
    public function run(): void
    {
        $years = [];
        for ($startYear = 2024; $startYear < 2050; $startYear++) {
            $endYear = $startYear + 1;
            $years[] = [
                'year_name'  => $startYear . '-' . substr($endYear, 2),
                'start_date' => $startYear . '-04-01',
                'end_date'   => $endYear . '-03-31',
                'is_active'  => ($startYear === 2024) ? 1 : 0,
            ];
        }
        DB::table('financial_years')->insert($years);
    }
}
