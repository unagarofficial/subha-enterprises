<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_rtn_dtl', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('br_code');
            $table->unsignedInteger('inv_no');
            $table->unsignedSmallInteger('sl_no');
            $table->string('mat_code', 50);
            $table->decimal('qty', 18, 3)->default(0.000);
            $table->unsignedSmallInteger('uom');
            $table->decimal('rate', 18, 2)->default(0.00);
            $table->decimal('amount', 18, 2)->default(0.00);
            $table->string('narration', 255)->nullable();
            $table->date('inv_date');
            $table->timestamps();

            $table->foreign('mat_code')->references('mat_code')->on('products');
            $table->foreign('uom')->references('uom_code')->on('uoms');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_rtn_dtl');
    }
};
