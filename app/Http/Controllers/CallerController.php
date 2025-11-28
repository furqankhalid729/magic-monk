<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallerController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Caller webhook received a request.');
        Log::info('Received Caller webhook:', $request->all());
        $callFrom = $this->normalizeToIndia($request->input('CallFrom'));
        sendInteraktMessage(
            $callFrom,
            [],
            ["https://interaktprodmediastorage.blob.core.windows.net/mediaprodstoragecontainer/04df994b-7058-44f8-b916-7243184e7f63/message_template_media/SzF1t1ZMPPlI/Monkmagic%20Logo%20Vertical.jpeg?se=2030-11-22T12%3A37%3A54Z&sp=rt&sv=2019-12-12&sr=b&sig=4N251CXyRvJ/SxhxvJdgrsdQgxHVaTuOT7%2B58hLP9PQ%3D"],
            'missedcallmessage'
        );

        return response()->json(['status' => 'success'], 200);
    }

    function normalizeToIndia($raw)
    {
        $raw = trim($raw);

        // If already in international form (starts with +) -> keep as-is
        if (strlen($raw) > 0 && $raw[0] === '+') {
            return $raw;
        }

        // Remove all non-digit characters so we work with digits only
        $digits = preg_replace('/\D+/', '', $raw);

        // If empty after cleaning, return empty
        if ($digits === '') {
            return $raw;
        }

        // Remove only one leading '0' if present
        if ($digits[0] === '0') {
            $digits = substr($digits, 1);
        }

        // If already starts with country code '91' (no plus), add '+'
        if (substr($digits, 0, 2) === '91') {
            return '+' . $digits;
        }

        // Otherwise prefix with +91
        return '+91' . $digits;
    }
}
