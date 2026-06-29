<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the application_status_history table.
     *
     * Per docs/SCHEMA.md:
     * Tracks every status change on an application, supporting:
     * - Reporting funnel (FR-018) — distribution of statuses per job
     * - Time-to-hire calculation — diff between status change timestamps
     * - Basic audit trail — who changed what and when
     *
     * previous_status is nullable for the initial entry (application creation).
     */
    public function up(): void
    {
        Schema::create('application_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('application_id');
            $table->enum('previous_status', ['pending', 'shortlisted', 'rejected'])->nullable();
            $table->enum('new_status', ['pending', 'shortlisted', 'rejected']);
            $table->uuid('changed_by');
            $table->timestamp('changed_at');

            $table->foreign('application_id')->references('id')->on('applications')->cascadeOnDelete();
            $table->foreign('changed_by')->references('id')->on('users');

            $table->index('application_id');
            $table->index('changed_at'); // for time-series reporting queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_history');
    }
};
