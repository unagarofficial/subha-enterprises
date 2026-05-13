<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->unsignedSmallInteger('cat_code');
            $table->string('mat_code', 50)->primary();
            $table->string('mat_name', 200);
            $table->unsignedSmallInteger('uom');
            $table->decimal('sale_rate', 18, 2)->default(0.00);
            $table->decimal('y_rate', 18, 2)->default(0.00);  // Yoshita rate
            $table->decimal('b_rate', 18, 2)->default(0.00);  // Brand rate
            $table->unsignedSmallInteger('br_code');
            $table->timestamps();

            $table->foreign('cat_code')->references('cat_code')->on('categories');
            $table->foreign('uom')->references('uom_code')->on('uoms');
            $table->foreign('br_code')->references('br_code')->on('branches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
