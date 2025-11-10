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

        $razorpayKey = config('services.razorpay.key_test');
        $razorpaySecret = config('services.razorpay.secret_test');

        if (empty($razorpayKey) || empty($razorpaySecret)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Razorpay credentials not configured',
            ], 500);
        }

        try {
            $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);

            // ✅ Use your existing plan ID here (from Razorpay Dashboard)
            $existingPlanId = 'plan_RdxIziZm5vuIiP';

            $customerId = $request->input('customer_id');

            if (!$customerId) {
                // 🧠 Use unique email/contact each time OR fetch from DB
                $email = $request->input('email', 'test37647364' . rand(100, 999) . '@gmail.com');
                $customer = $api->customer->create([
                    'name'    => $request->input('name', 'Test User'),
                    'email'   => $email,
                    'contact' => $request->input('contact', '9999999786'),
                ]);
                $customerId = $customer['id'];
            }


            Log::info('Razorpay Customer Created:', (array)$customer);

            // ✅ Create subscription using existing plan
            $subscriptionData = [
                "plan_id" => $existingPlanId,
                "customer_notify" => 1,
                "total_count" => 12,
                "customer_id" => $customerId, // link to this customer
            ];

            Log::info('Creating Razorpay Subscription:', [$subscriptionData]);

            $subscription = $api->subscription->create($subscriptionData);
            Log::info('Razorpay Subscription Created:', (array)$subscription);

            return response()->json([
                'status' => 'success',
                'subscription_id' => $subscription['id'],
                'customer_id' => $customerId,
                'plan_id' => $existingPlanId,
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
                'message' => 'General Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
