<?php

namespace App\Http\Controllers\Interakt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Webhook extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Valid Interakt Webhook Received', $request->all());
        $topic = $request->input('type');
        $allowedTopics = ['message_received', 'workflow_response_update'];

        // if (!in_array($topic, $allowedTopics)) {
        //     Log::warning('Unhandled Interakt Webhook Topic', ['topic' => $topic]);
        //     return response()->json(['error' => 'Unhandled topic'], 400);
        // }
        // Log::info('Valid Interakt Webhook Received', $request->all());

        return response()->json(['status' => 'success', 'message' => 'Webhook processed successfully'], 200);
    }
}
