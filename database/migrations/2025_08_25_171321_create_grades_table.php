<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGradesTable extends Migration
{
    public function up()
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

    public function down()
    {
        Schema::dropIfExists('grades');
    }
}