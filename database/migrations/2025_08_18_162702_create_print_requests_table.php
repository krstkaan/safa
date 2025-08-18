<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('print_requests', function (Blueprint $table) {
            $table->id();
            $table->timestamp('requested_at')->nullable();
            $table->integer('color_copies')->default(0);
            $table->integer('bw_copies')->default(0);
            $table->foreignId('requester_id')->constrained('requesters')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('approvers')->cascadeOnDelete();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_requests');
    }
};
