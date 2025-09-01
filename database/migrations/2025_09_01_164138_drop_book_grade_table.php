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
        Schema::dropIfExists('book_grade');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pivot tablo için isimlendirme standardı: tekil model isimleri, alfabetik sıra
        Schema::create('book_grade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->foreignId('grade_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }
};
