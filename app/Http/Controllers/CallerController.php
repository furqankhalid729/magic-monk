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
            [],
            'missedcallmsg'
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
