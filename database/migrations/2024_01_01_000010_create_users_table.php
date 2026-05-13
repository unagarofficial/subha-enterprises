<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_name', 50)->unique();
            $table->string('password', 255);
            $table->enum('user_type', ['ADMIN', 'USER'])->default('USER');
            $table->unsignedSmallInteger('br_code');
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('br_code')->references('br_code')->on('branches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
