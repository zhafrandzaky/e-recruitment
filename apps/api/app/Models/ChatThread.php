<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single real-time chat thread bound one-to-one to an Application (FR-017).
 *
 * Threads are created lazily on first access (see ChatController) — an
 * application that never chats never gets a row. Only created_at is tracked.
 */
#[Fillable(['application_id'])]
class ChatThread extends Model
{
    use HasFactory, HasUuids;

    /**
     * The schema tracks only created_at (no updated_at), so Eloquent's
     * automatic timestamp management is disabled; created_at defaults at the
     * database level.
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * Messages in this thread, oldest first (for time-ordered history).
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_thread_id')->orderBy('sent_at');
    }
}
