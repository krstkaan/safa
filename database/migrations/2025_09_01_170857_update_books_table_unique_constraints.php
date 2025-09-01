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
        Schema::table('books', function (Blueprint $table) {
            // Barcode'dan unique constraint'i kaldır
            $table->dropUnique(['barcode']);
            
            // Fixture_no'ya unique constraint ekle (eğer null değilse)
            $table->unique('fixture_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Fixture_no'dan unique constraint'i kaldır
            $table->dropUnique(['fixture_no']);
            
            // Barcode'a unique constraint ekle
            $table->unique('barcode');
        });
    }
};
