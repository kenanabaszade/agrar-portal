<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
 
class PaymentController extends Controller
{
  
    public function index(Request $request)
    {
        return Payment::where('user_id', $request->user()->id)->latest('payment_date')->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_method' => ['required', 'in:card,bank_transfer,cash,mobile_money'],
            'status' => ['required', 'in:pending,paid,failed,refunded'],
            'related_exam_registration_id' => ['nullable', 'exists:exam_registrations,id'],
        ]);
        $payment = Payment::create(array_merge($validated, [
            'user_id' => $request->user()->id,
        ]));
        return response()->json($payment, 201);
    }

    // Webhook endpoint to update payment status (e.g., from PSP)
    public function webhook(Request $request)
    {
        // Verify webhook signature for security
        $signature = $request->header('X-Webhook-Signature');
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, config('app.webhook_secret', 'default-secret'));
        
        if (!hash_equals($expectedSignature, $signature ?? '')) {
            Log::warning('Invalid webhook signature', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            abort(401, 'Invalid signature');
        }

        $data = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
            'status' => ['required', 'in:pending,paid,failed,refunded'],
        ]);
        
        $payment = Payment::findOrFail($data['payment_id']);
        $payment->update(['status' => $data['status']]);
        
        Log::info('Payment status updated via webhook', [
            'payment_id' => $payment->id,
            'new_status' => $data['status']
        ]);
        
        return response()->json(['message' => 'ok']);
    }
}


