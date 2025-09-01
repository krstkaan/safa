<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mevcut unique constraint'i kaldır
        Schema::table('books', function (Blueprint $table) {
            $table->dropUnique(['fixture_no']);
        });
        
        // Soft delete ile uyumlu partial unique index oluştur
        // Sadece deleted_at NULL olan (silinmemiş) kayıtlarda unique olacak
        DB::statement('CREATE UNIQUE INDEX books_fixture_no_unique_not_deleted ON books (fixture_no) WHERE deleted_at IS NULL AND fixture_no IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Partial index'i kaldır
        DB::statement('DROP INDEX IF EXISTS books_fixture_no_unique_not_deleted');
        
        // Normal unique constraint'i geri ekle
        Schema::table('books', function (Blueprint $table) {
            $table->unique('fixture_no');
        });
    }
};
