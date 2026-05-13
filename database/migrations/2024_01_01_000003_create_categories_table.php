<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->smallIncrements('cat_code');
            $table->string('cat_name', 100);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE categories AUTO_INCREMENT = 1000');
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
