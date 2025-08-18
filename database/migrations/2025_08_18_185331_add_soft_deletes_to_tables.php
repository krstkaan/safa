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
        // Add soft deletes to requesters table
        Schema::table('requesters', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to approvers table
        Schema::table('approvers', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to print_requests table
        Schema::table('print_requests', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove soft deletes from requesters table
        Schema::table('requesters', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from approvers table
        Schema::table('approvers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from print_requests table
        Schema::table('print_requests', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
