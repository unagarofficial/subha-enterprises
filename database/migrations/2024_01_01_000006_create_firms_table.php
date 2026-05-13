<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firms', function (Blueprint $table) {
            $table->smallIncrements('firm_code');
            $table->string('firm_name', 100);
            $table->string('address', 255)->nullable();
            $table->string('place', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('website', 100)->nullable();
            $table->string('tin_no', 30)->nullable();
            $table->unsignedSmallInteger('ho_code')->nullable();
            $table->char('type', 1)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firms');
    }
};
