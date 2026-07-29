<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_log', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('otp_log', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
