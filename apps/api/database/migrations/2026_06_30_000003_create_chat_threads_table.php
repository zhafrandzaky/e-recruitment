<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the chat_threads table.
     *
     * Per docs/SCHEMA.md and FR-017:
     * - One thread per application (UNIQUE application_id, one-to-one).
     * - UUID primary key.
     * - Only created_at is tracked (no updated_at) — a thread is immutable
     *   once created; its messages carry their own timestamps.
     */
    public function up(): void
    {
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('application_id');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('application_id')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();

            $table->unique('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_threads');
    }
};
