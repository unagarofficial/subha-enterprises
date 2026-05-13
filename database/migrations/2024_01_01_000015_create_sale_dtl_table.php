<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_dtl', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('br_code');
            $table->unsignedInteger('inv_no');
            $table->unsignedSmallInteger('sl_no');
            $table->string('mat_code', 50);
            $table->decimal('qty', 18, 3)->default(0.000);
            $table->unsignedSmallInteger('uom');
            $table->decimal('rate', 18, 2)->default(0.00);
            $table->decimal('s_value', 18, 2)->default(0.00);
            $table->string('narration', 255)->nullable();
            $table->date('inv_date');
            $table->tinyInteger('sale_type')->default(1);
            $table->timestamps();

            $table->foreign('mat_code')->references('mat_code')->on('products');
            $table->foreign('uom')->references('uom_code')->on('uoms');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_dtl');
    }
};
