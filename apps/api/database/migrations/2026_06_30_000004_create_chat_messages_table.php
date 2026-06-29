<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the chat_messages table.
     *
     * Per docs/SCHEMA.md and FR-017:
     * - UUID primary key.
     * - FK to chat_threads (the thread the message belongs to) and users (sender).
     * - content (text body).
     * - sent_at carries the message timestamp (no created_at/updated_at).
     * - Index on (chat_thread_id, sent_at) for time-ordered history pagination.
     */
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chat_thread_id');
            $table->uuid('sender_id');
            $table->text('content');
            $table->timestamp('sent_at')->useCurrent();

            $table->foreign('chat_thread_id')
                ->references('id')
                ->on('chat_threads')
                ->cascadeOnDelete();
            $table->foreign('sender_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index('chat_thread_id');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
