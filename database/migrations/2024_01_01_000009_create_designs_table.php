<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('cat_code');
            $table->string('design_code', 50);
            $table->string('design_desc', 200)->nullable();
            $table->unsignedSmallInteger('uom');
            $table->decimal('rate', 18, 2)->default(0.00);
            $table->decimal('y_rate', 18, 2)->default(0.00);
            $table->decimal('b_rate', 18, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('cat_code')->references('cat_code')->on('categories');
            $table->foreign('uom')->references('uom_code')->on('uoms');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};
