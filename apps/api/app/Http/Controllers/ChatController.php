<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Application;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-application real-time chat (FR-017, UC-09, docs/SEQUENCE-DIAGRAM.md Alur 3).
 *
 * Both endpoints enforce the same ownership rule as the WebSocket channel
 * (routes/channels.php) independently — a REST request and a channel
 * subscription are separate security boundaries (docs/SECURITY.md Section 3.2).
 */
class ChatController extends Controller
{
    /**
     * Maximum characters allowed in a single chat message.
     */
    private const MAX_MESSAGE_LENGTH = 5000;

    /**
     * GET /applications/{id}/messages — chat history for the application's thread.
     *
     * Applicant (own) or HR. Returns an empty list when no thread exists yet.
     */
    public function index(Request $request, string $id): JsonResponse
    {
        $application = Application::findOrFail($id);
        $this->authorizeChatAccess($request, $application);

        $thread = $this->resolveThread($application);

        $messages = $thread->messages()->with('sender:id,name')->get();

        return response()->json([
            'data' => $messages->map(fn (ChatMessage $message) => $this->formatMessage($message)),
        ]);
    }

    /**
     * POST /applications/{id}/messages — send a message (FR-017).
     *
     * Persists the message first, then broadcasts MessageSent via Reverb, so a
     * disconnected client can always reload history from the database
     * (docs/ARCHITECTURE.md Section 8).
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $application = Application::findOrFail($id);
        $this->authorizeChatAccess($request, $application);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:'.self::MAX_MESSAGE_LENGTH],
        ]);

        $thread = $this->resolveThread($application);
        $sender = $request->user();

        $message = ChatMessage::create([
            'chat_thread_id' => $thread->id,
            'sender_id' => $sender->id,
            'content' => $validated['content'],
            'sent_at' => now(),
        ]);

        $message->setRelation('sender', $sender);

        broadcast(new MessageSent($message, $application->id));

        return response()->json($this->formatMessage($message), Response::HTTP_CREATED);
    }

    /**
     * Enforce that the requesting user may access this application's chat.
     * Returns 404 (not 403) to avoid leaking existence of others' applications,
     * mirroring ApplicationController.
     */
    private function authorizeChatAccess(Request $request, Application $application): void
    {
        if (! $application->canAccessChat($request->user())) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Get the application's chat thread, creating it lazily on first use.
     */
    private function resolveThread(Application $application): ChatThread
    {
        return ChatThread::firstOrCreate(['application_id' => $application->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'content' => $message->content,
            'sender_id' => $message->sender_id,
            'sent_at' => $message->sent_at->toIso8601String(),
            'sender' => [
                'id' => $message->sender_id,
                'name' => $message->sender?->name,
            ],
        ];
    }
}
