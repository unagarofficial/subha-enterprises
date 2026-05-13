<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_rtn_hdr', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('ho_code')->nullable();
            $table->unsignedSmallInteger('br_code');
            $table->unsignedInteger('inv_no');
            $table->date('inv_date');
            $table->unsignedInteger('party_code');
            $table->decimal('gross', 18, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 18, 2)->default(0.00);
            $table->decimal('nett', 18, 2)->default(0.00);
            $table->string('bill_type', 20)->nullable();
            $table->unsignedSmallInteger('fin_year_id');
            $table->timestamps();

            $table->foreign('br_code')->references('br_code')->on('branches');
            $table->foreign('party_code')->references('party_code')->on('parties');
            $table->foreign('fin_year_id')->references('id')->on('financial_years');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_rtn_hdr');
    }
};
