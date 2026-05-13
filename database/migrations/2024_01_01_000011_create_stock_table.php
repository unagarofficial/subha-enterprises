<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('br_code');
            $table->string('mat_code', 50);
            $table->unsignedSmallInteger('cat_code');
            $table->decimal('ob', 18, 3)->default(0.000);       // Opening Balance
            $table->decimal('rcpts', 18, 3)->default(0.000);    // Receipts
            $table->decimal('issues', 18, 3)->default(0.000);   // Issues
            $table->decimal('cl_stock', 18, 3)->default(0.000); // Closing Stock
            $table->timestamps();

            $table->foreign('br_code')->references('br_code')->on('branches');
            $table->foreign('mat_code')->references('mat_code')->on('products');
            $table->foreign('cat_code')->references('cat_code')->on('categories');
            $table->unique(['br_code', 'mat_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
