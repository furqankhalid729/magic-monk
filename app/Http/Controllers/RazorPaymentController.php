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

            // $customerId = $request->input('customer_id');

            // if (!$customerId) {
            //     $email = $request->input('email', 'test' . rand(1000, 9999) . '@gmail.com');
            //     $customer = $api->customer->create([
            //         'name'    => $request->input('name', 'Test User'),
            //         'email'   => $email,
            //         'contact' => $request->input('contact', '9999999999'),
            //     ]);
            //     $customerId = $customer['id'];
            //     Log::info('Razorpay Customer Created:', (array)$customer);
            // }

            // ✅ Create subscription and tell Razorpay to send checkout link
            $subscriptionData = [
                "plan_id" => $existingPlanId,
                "total_count" => 12,
                "customer_notify" => true,
                // "customer_id" => $customerId,
                "notify_info" => [
                    "notify_email" => $request->input('email', 'nikunjb@monkmagic.in'),
                    "notify_phone" => $request->input('contact', '9999999999'),
                ],
            ];

            Log::info('Creating Razorpay Subscription:', [$subscriptionData]);
            $subscription = $api->subscription->create($subscriptionData);
            Log::info('Razorpay Subscription Created:', (array)$subscription);

            // ✅ Fetch the complete subscription details to get the short_url
            $subscriptionId = $subscription['id'];
            $completeSubscription = $api->subscription->fetch($subscriptionId);

            Log::info('Complete Razorpay Subscription Details:', (array)$completeSubscription);

            // ✅ Get the checkout link from the complete subscription details
            $checkoutLink = $completeSubscription['short_url'] ?? null;

            // ✅ If still no checkout link, you can construct one manually
            if (!$checkoutLink) {
                $checkoutLink = "https://rzp.io/i/" . $subscriptionId;
            }

            return response()->json([
                'status' => 'success',
                'subscription_id' => $subscriptionId,
                'plan_id' => $existingPlanId,
                'checkout_link' => $checkoutLink,
                'subscription_status' => $completeSubscription['status'] ?? $subscription['status'],
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
