<?php

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Private chat channel — private-chat.{applicationId} (FR-017)
|--------------------------------------------------------------------------
|
| Authorizes a user to subscribe to a single application's chat thread.
| Only the owning applicant or an HR admin may subscribe — verified
| server-side here, never trusting the client's claim about which
| application it is chatting on (docs/SECURITY.md Section 3.2).
|
| This is an independent enforcement point from the REST endpoints in
| ChatController; both reuse Application::canAccessChat() as the single
| source of truth. The SPA sends its Sanctum bearer token to
| /api/broadcasting/auth (tokens live in localStorage — docs/SECURITY.md
| Section 10); that route's auth:sanctum middleware authenticates the user
| and promotes 'sanctum' to the default guard, so $user is resolved here.
|
| Echo subscribes to the wire channel `private-chat.{id}`; Laravel strips
| the `private-` prefix, so this pattern matches. UUIDs are compared as
| strings. A non-existent or non-owned application both return false →
| Laravel responds with a uniform 403 (no existence leak).
*/
Broadcast::channel('chat.{applicationId}', function (User $user, string $applicationId) {
    $application = Application::find($applicationId);

    return $application !== null && $application->canAccessChat($user);
});
