<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the applications table.
     *
     * Per docs/SCHEMA.md:
     * - UUID primary key
     * - FK to job_postings and users (applicant)
     * - cv_path (system-generated storage key)
     * - cv_original_filename (display metadata)
     * - additional_data (JSONB for flexible form fields)
     * - status enum (pending, shortlisted, rejected)
     * - Soft deletes for audit trail
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('job_posting_id');
            $table->uuid('applicant_id');
            $table->string('cv_path', 500)->nullable();
            $table->string('cv_original_filename')->nullable();
            $table->jsonb('additional_data')->nullable();
            $table->enum('status', ['pending', 'shortlisted', 'rejected'])->default('pending');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('job_posting_id')->references('id')->on('job_postings')->cascadeOnDelete();
            $table->foreign('applicant_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index('job_posting_id');
            $table->index('applicant_id');
            $table->index('status');
            $table->index(['job_posting_id', 'status']); // composite for HR dashboard funnel query
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
