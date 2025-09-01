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
        Schema::dropIfExists('grades');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            // 1-8 arası sayıları tutmak için küçük, işaretsiz bir integer yeterlidir.
            // unique() kuralı, aynı sınıf seviyesinin (örn: 5) birden çok kez eklenmesini engeller.
            $table->unsignedTinyInteger('name')->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
