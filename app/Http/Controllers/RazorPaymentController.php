<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Exception;
use Illuminate\Support\Facades\Log;

class RazorPaymentController extends Controller
{
    public function testConnection(Request $request)
    {
        return generatePaymentLink();
    }

    public function createQr(Request $request)
    {
        Log::info('Creating Razorpay QR Code');

        $razorpayKey = config('services.razorpay.key');
        $razorpaySecret = config('services.razorpay.secret');

        if (empty($razorpayKey) || empty($razorpaySecret)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Razorpay credentials not configured',
            ], 500);
        }

        try {
            $api = new Api($razorpayKey, $razorpaySecret);

            $amount = (int)$request->input('amount', 100);
            $description = $request->input('description', 'Payment for order');

            $qrData = [
                "type" => "upi_qr",
                "usage" => "single_use",
                "fixed_amount" => true,
                "payment_amount" => $amount,
                "description" => $description,
            ];

            Log::info('Razorpay QR Data:', [$qrData]);

            // Create the QR code
            $qr = $api->qrCode->create($qrData);
            Log::info('Razorpay QR Created:', (array)$qr);

            // Re-fetch QR details to get the image_url
            $qrDetails = $api->qrCode->fetch($qr['id']);
            Log::info('Razorpay QR Details:', (array)$qrDetails);

            return response()->json([
                'status' => 'success',
                'qr_id' => $qr['id'],
                'qr_image' => $qrDetails['image_url'], // actual QR image
                'amount' => $amount,
            ], 200);
        } catch (\Razorpay\Api\Errors\Error $e) {
            Log::error('Razorpay API Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Razorpay API Error: ' . $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('General Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function testSubscription(Request $request)
    {
        Log::info('Testing Razorpay Subscription Creation');

        $razorpayKey = config('services.razorpay.key');
        $razorpaySecret = config('services.razorpay.secret');

        if (empty($razorpayKey) || empty($razorpaySecret)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Razorpay credentials not configured',
            ], 500);
        }

        try {
            $api = new Api($razorpayKey, $razorpaySecret);

            // Create a plan
            $planData = [
                "period" => "monthly",
                "interval" => 1,
                "item" => [
                    "name" => "Test Monthly Plan",
                    "amount" => 5000, // Amount in paise (50.00 INR)
                    "currency" => "INR",
                    "description" => "Monthly subscription plan"
                ]
            ];
            Log::info('Creating Razorpay Plan:', [$planData]);

            $plan = $api->plan->create($planData);
            Log::info('Razorpay Plan Created:', (array)$plan);
            // Create a subscription
            $subscriptionData = [
                "plan_id" => $plan['id'],
                "customer_notify" => 1,
                "total_count" => 12,
            ];
            Log::info('Creating Razorpay Subscription:', [$subscriptionData]);

            $subscription = $api->subscription->create($subscriptionData);
            Log::info('Razorpay Subscription Created:', (array)$subscription);

            return response()->json([
                'status' => 'success',
                'plan_id' => $plan['id'],
                'subscription_id' => $subscription['id'],
            ], 200);
        } catch (\Razorpay\Api\Errors\Error $e) {
            Log::error('Razorpay API Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Razorpay API Error: ' . $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('General Error: ' . $e->getMessage());
        }
    }
}
