<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'email_notifications_enabled' => (bool) $user->email_notifications_enabled,
            'push_notifications_enabled' => (bool) $user->push_notifications_enabled,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'email_notifications_enabled' => ['sometimes', 'boolean'],
            'push_notifications_enabled' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $user->fill($data);
        $user->save();

        return response()->json([
            'message' => 'Bildiriş parametrləri yeniləndi',
            'email_notifications_enabled' => (bool) $user->email_notifications_enabled,
            'push_notifications_enabled' => (bool) $user->push_notifications_enabled,
        ]);
    }
}


