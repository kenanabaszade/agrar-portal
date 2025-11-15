<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = max(1, min($request->integer('per_page', 20), 100));

        $notifications = Notification::where('user_id', $user->id)
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->get('type')))
            ->when($request->boolean('unread'), fn ($query) => $query->where('is_read', false))
            ->latest('sent_at')
            ->paginate($perPage);

        return NotificationResource::collection($notifications);
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['is_read' => true]);

        return new NotificationResource($notification->refresh());
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Bütün bildirişlər oxundu kimi işarələndi']);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}
