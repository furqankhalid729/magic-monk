<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallerController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Caller webhook received a request.');
        // Log the incoming webhook payload for debugging
        Log::info('Received Caller webhook:', $request->all());

        // Process the webhook data as needed
        // For example, you might want to verify the request, update records, etc.

        return response()->json(['status' => 'success'], 200);
    }
}
