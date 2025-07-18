<?php

namespace App\Http\Controllers\Interakt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Webhook extends Controller
{
    public function handle(Request $request)
    {
        $topic = $request->input('type');
        switch ($topic) {
            case 'message_received':
                $messageType = $request->input('data.message.message_content_type');

                switch ($messageType) {
                    case 'Location':
                        $locationData = json_decode($request->input('data.message.message'), true);

                        if (isset($locationData['latitude'], $locationData['longitude'])) {
                            $lat = $locationData['latitude'];
                            $lng = $locationData['longitude'];
                            $message = "Location received: Latitude = $lat, Longitude = $lng";
                        } else {
                            $message = 'Invalid location data received.';
                        }
                        break;

                    case 'Text':
                        $text = $request->input('data.message.message');
                        $message = "Text message received: \"$text\"";
                        break;

                    default:
                        $message = "Unhandled message type: $messageType";
                }

                break;

            default:
                $message = 'Unknown webhook topic.';
        }

        return response()->json([
            'status' => 'ok',
            'message' => $message,
        ]);
    }
}
