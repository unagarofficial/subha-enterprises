<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // UOMs
        DB::table('uoms')->insert([
            ['uom_name' => 'PCS'],
            ['uom_name' => 'GMS'],
            ['uom_name' => 'KGS'],
            ['uom_name' => 'SET'],
            ['uom_name' => 'DOZ'],
        ]);

        // Categories (starts at 1000 via auto_increment)
        DB::table('categories')->insert([
            ['cat_name' => 'Covering'],
            ['cat_name' => 'Plating'],
            ['cat_name' => 'Yoshita'],
        ]);

        // Taxes
        DB::table('taxes')->insert([
            ['tax_name' => 'GST 3%',  'tax_percent' => 3.00],
            ['tax_name' => 'GST 5%',  'tax_percent' => 5.00],
            ['tax_name' => 'GST 12%', 'tax_percent' => 12.00],
            ['tax_name' => 'GST 18%', 'tax_percent' => 18.00],
        ]);
    }
}
