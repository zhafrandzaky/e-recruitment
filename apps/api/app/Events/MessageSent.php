<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a chat message is sent (FR-017).
 *
 * Fired only after the message is persisted (docs/ARCHITECTURE.md Section 8),
 * so history survives a disconnected client. Broadcasts on the application's
 * private channel; the broadcast name is the class basename "MessageSent"
 * (matching docs/API.md Section 6), so the Echo client listens for
 * "MessageSent".
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatMessage $message,
        public string $applicationId,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.'.$this->applicationId),
        ];
    }

    /**
     * The payload pushed to subscribers — same shape as the POST response so
     * both surfaces agree (docs/API.md Section 6).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'sender_id' => $this->message->sender_id,
            'sent_at' => $this->message->sent_at->toIso8601String(),
            'sender' => [
                'id' => $this->message->sender_id,
                'name' => $this->message->sender?->name,
            ],
        ];
    }
}
