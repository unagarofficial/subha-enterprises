<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_hdr', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('ho_code')->nullable();
            $table->unsignedSmallInteger('br_code');
            $table->unsignedInteger('ord_no');
            $table->date('ord_date');
            $table->unsignedInteger('party_code');
            $table->tinyInteger('is_locked')->default(0);
            $table->unsignedInteger('inv_no')->nullable();
            $table->tinyInteger('ord_type')->default(1);
            $table->unsignedSmallInteger('fin_year_id');
            $table->timestamps();

            $table->unique(['br_code', 'ord_no', 'ord_type']);
            $table->foreign('br_code')->references('br_code')->on('branches');
            $table->foreign('party_code')->references('party_code')->on('parties');
            $table->foreign('fin_year_id')->references('id')->on('financial_years');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_hdr');
    }
};
