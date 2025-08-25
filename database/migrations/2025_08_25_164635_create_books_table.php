<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBooksTable extends Migration
{
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('language')->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->boolean('is_donation')->default(false);
            $table->string('barcode')->unique()->nullable();
            $table->string('shelf_code')->nullable();
            $table->string('fixture_no')->nullable();

            // Foreign Keys (Yabancı Anahtarlar)
            $table->foreignId('author_id')->constrained('authors');
            $table->foreignId('publisher_id')->constrained('publishers');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books');
    }
}