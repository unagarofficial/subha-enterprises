<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_issue_dtl', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('iss_no');
            $table->unsignedSmallInteger('sl_no');
            $table->unsignedSmallInteger('br_code');
            $table->string('item_code', 50);
            $table->integer('order_qty')->default(0);
            $table->integer('sent_qty')->default(0);
            $table->unsignedInteger('po_no')->nullable();
            $table->timestamps();

            $table->foreign('iss_no')->references('iss_no')->on('branch_issue_hdr');
            $table->foreign('br_code')->references('br_code')->on('branches');
            $table->foreign('item_code')->references('mat_code')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_issue_dtl');
    }
};
