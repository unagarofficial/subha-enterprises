<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_dtl', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('br_code');
            $table->unsignedInteger('ord_no');
            $table->unsignedSmallInteger('sl_no');
            $table->string('mat_code', 50);
            $table->string('narration', 255)->nullable();
            $table->decimal('ord_qty', 18, 3)->default(0.000);
            $table->unsignedSmallInteger('uom');
            $table->decimal('bill_qty', 18, 3)->default(0.000);
            $table->decimal('ex_qty', 18, 3)->default(0.000);
            $table->unsignedInteger('po_no')->nullable();
            $table->date('po_date')->nullable();
            $table->decimal('p_qty', 18, 3)->default(0.000);
            $table->unsignedInteger('pb_no')->nullable();
            $table->decimal('req_qty', 18, 3)->default(0.000);
            $table->tinyInteger('ord_type')->default(1);
            $table->timestamps();

            $table->foreign('mat_code')->references('mat_code')->on('products');
            $table->foreign('uom')->references('uom_code')->on('uoms');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_dtl');
    }
};
