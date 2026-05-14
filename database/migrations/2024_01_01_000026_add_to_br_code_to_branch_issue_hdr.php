<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_issue_hdr', function (Blueprint $table) {
            $table->unsignedSmallInteger('to_br_code')->after('br_code');
            $table->foreign('to_br_code')->references('br_code')->on('branches');
        });
    }

    public function down(): void
    {
        Schema::table('branch_issue_hdr', function (Blueprint $table) {
            $table->dropForeign(['to_br_code']);
            $table->dropColumn('to_br_code');
        });
    }
};
