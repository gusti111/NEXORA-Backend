<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ]);

        return response()->json([
            'message' => 'Notifikasi berhasil diambil.',
            'data' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'data' => [
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notifikasi ditandai telah dibaca.',
            'data' => ['id' => $notification->id, 'read_at' => $notification->read_at],
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $updated = $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Semua notifikasi telah ditandai dibaca.',
            'data' => ['updated' => $updated],
        ]);
    }
}
