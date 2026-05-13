<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_issue_hdr', function (Blueprint $table) {
            $table->increments('iss_no');
            $table->date('iss_date');
            $table->unsignedSmallInteger('br_code');
            $table->timestamps();

            $table->foreign('br_code')->references('br_code')->on('branches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_issue_hdr');
    }
};
