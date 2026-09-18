<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate(15);

        auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
        Cache::forget('notif:unread:' . auth()->id());

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(Notification $notification)
    {
        abort_if($notification->user_id !== auth()->id(), 403);
        $notification->update(['read_at' => now()]);
        Cache::forget('notif:unread:' . auth()->id());
        return redirect($notification->link ?? route('dashboard'));
    }

    public function poll()
    {
        $unreadCount = auth()->user()->unreadNotificationsCount();

        $latest = auth()->user()
            ->notifications()
            ->whereNull('read_at')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function ($notif) {
                return [
                    'id'         => $notif->id,
                    'title'      => $notif->title,
                    'message'    => $notif->message,
                    'link'       => $notif->link,
                    'created_at' => $notif->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'unread_count' => $unreadCount,
            'latest'       => $latest,
        ]);
    }

    public function markAllRead()
    {
        auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
        Cache::forget('notif:unread:' . auth()->id());
        return response()->json(['success' => true]);
    }
}
