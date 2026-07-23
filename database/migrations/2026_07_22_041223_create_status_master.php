<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_master', function (Blueprint $table) {
            $table->tinyIncrements('id');
            
            // varchar(50), unique
            $table->string('kode', 50)->unique();
            
            // varchar(100)
            $table->string('nama', 100);
            
            // tinyint
            $table->tinyInteger('urutan');
            
            // boolean, default false
            $table->boolean('is_final')->default(false);
            
            // boolean, default false
            $table->boolean('is_clockoff')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_master');
    }
};
