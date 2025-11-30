<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AndroidAgentController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'phoneNumber' => 'required|string',
            'password' => 'required|string',
        ]);

        $phone = preg_replace('/^\+91/', '', $request->phoneNumber);
        $password = preg_replace('/^\+91/', '', $request->password);

        $user = Agent::where('whatsapp_number', $phone)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($phone === $password) {
            return response()->json(['message' => 'Login successful.', 'status' => true, 'user' => $user], 200);
        }

        return response()->json(['message' => 'Invalid credentials.', 'status' => false], 200);
    }

    public function order(Request $request)
    {
        $request->validate([
            'phoneNumber' => 'required|integer',
        ]);

        $agentId = $request->query('phoneNumber');
        if (!str_starts_with($agentId, '+91')) {
            $agentId = '+91' . ltrim($agentId, '+');
        }

        $orders = Order::where('agent_number', $agentId)
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->get();


        if (!$orders) {
            return response()->json(['message' => 'Orders not found.', 'status' => false], 200);
        }

        return response()->json(['message' => 'Order retrieved successfully.', 'status' => true, 'orders' => $orders], 200);
    }

    public function storeToken(Request $request)
    {
        $request->validate([
            'phoneNumber' => 'required|string',
            'token' => 'required|string',
        ]);

        $phone = preg_replace('/^\+91/', '', $request->phoneNumber);
        $token = $request->token;

        $user = Agent::where('whatsapp_number', $phone)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->notification_token = $token;
        $user->save();

        return response()->json(['message' => 'Token stored successfully.'], 200);
    }

    public function updateOrderStatus(Request $request)
    {
        $request->validate([
            'orderId' => 'required|integer',
            'status' => 'required|string',
        ]);

        $order = Order::find($request->orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $order->status = $request->status;
        $order->delivered_on = $request->status === 'delivered'
            ? Carbon::now('Asia/Kolkata')
            : null;
        $order->save();

        $response = sendInteraktMessage($order->customer_phone, [
            (string) $order->order_id
        ], [asset('storage/feedback.jpeg')], 'feedback_with_nps', "");

        $order->review_message_id = $response['id'] ?? null;
        $order->save();

        $orderCount = Order::where('customer_phone', $order->customer_phone)
            ->where('status', 'delivered')
            ->count();

        $additionalInfo = $order->additional_info ?? [];
        if ($orderCount === 1 && $additionalInfo['first_time_discount'] === true) {
            addCustomerCoupon($order->customer_phone, '50-off');
        }

        sendInteraktMessage(
            $order->customer_phone,
            [],
            ['https://interaktprodmediastorage.blob.core.windows.net/mediaprodstoragecontainer/04df994b-7058-44f8-b916-7243184e7f63/message_template_media/MAXeQDrV2jsL/WhatsApp%20Image%202025-11-16%20at%2015.56.19.jpeg?se=2030-11-10T10%3A26%3A36Z&sp=rt&sv=2019-12-12&sr=b&sig=FrET1nh1R4%2BAfU9/WvHvlVWvmqCn7zBZ7jocjSIhQE4%3D'],
            'subscriptionnudge',
            ""
        );
        return response()->json(['message' => 'Order status updated successfully.', 'status' => true], 200);
    }
}
