<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->unsignedSmallInteger('br_code');
            $table->char('party_type', 1)->default('C'); // C=Customer, S=Supplier
            $table->increments('party_code');
            $table->string('party_name', 100);
            $table->string('address', 255)->nullable();
            $table->string('place', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->tinyInteger('inout_state')->default(0); // 0=In-State, 1=Out-State
            $table->tinyInteger('tin_grn_flag')->default(0);
            $table->char('tin_grn_no', 30)->nullable();
            $table->timestamps();

            $table->foreign('br_code')->references('br_code')->on('branches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
