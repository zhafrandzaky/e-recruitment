<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single chat message within a ChatThread (FR-017).
 *
 * Persisted before the MessageSent event is broadcast, so history is always
 * reloadable from the database even if a client was disconnected
 * (docs/ARCHITECTURE.md Section 8).
 */
#[Fillable(['chat_thread_id', 'sender_id', 'content', 'sent_at'])]
class ChatMessage extends Model
{
    use HasFactory, HasUuids;

    /**
     * The schema tracks only sent_at (no created_at/updated_at), so Eloquent's
     * automatic timestamp management is disabled.
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
