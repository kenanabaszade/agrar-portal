<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\User;
use App\Mail\ContactMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Store a new contact message and send email to all admins
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        try {
            // Create the contact message
            $contactMessage = ContactMessage::create($validated);

            // Get all admin users
            $admins = User::where('user_type', 'admin')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get();

            // Send email notification to all admins
            $sentCount = 0;
            $failedCount = 0;

            foreach ($admins as $admin) {
                try {
                    Mail::to($admin->email)->send(new ContactMessageNotification($contactMessage));
                    $sentCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('Failed to send contact message notification email', [
                        'admin_id' => $admin->id,
                        'admin_email' => $admin->email,
                        'contact_message_id' => $contactMessage->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Contact message notification emails sent', [
                'contact_message_id' => $contactMessage->id,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'total_admins' => $admins->count()
            ]);

            return response()->json([
                'message' => 'Mesajınız uğurla göndərildi',
                'contact_message' => $contactMessage,
                'emails_sent' => $sentCount
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to process contact message', [
                'error' => $e->getMessage(),
                'request_data' => $validated
            ]);

            return response()->json([
                'message' => 'Mesaj göndərilərkən xəta baş verdi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
