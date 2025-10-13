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
        try {
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            
            // Test with a simple API call
            $payments = $api->payment->all(['count' => 1]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Razorpay connection successful',
                'key_type' => substr(config('services.razorpay.key'), 0, 8) === 'rzp_test' ? 'test' : 'live'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Razorpay connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function createQr(Request $request)
    {
        Log::info('Creating Razorpay QR Code');
        
        // Check if Razorpay credentials are configured
        $razorpayKey = config('services.razorpay.key');
        $razorpaySecret = config('services.razorpay.secret');
        
        if (empty($razorpayKey) || empty($razorpaySecret)) {
            Log::error('Razorpay credentials not configured');
            return response()->json([
                'status' => 'error',
                'message' => 'Razorpay credentials not configured',
            ], 500);
        }
        
        try {
            $api = new Api($razorpayKey, $razorpaySecret);

            $amount = (int)$request->input('amount', 100); // Minimum 1 INR = 100 paise
            $description = $request->input('description', 'Payment for order');
            $storeName = $request->input('store_name', 'Magic Monk Store');

            // Simplified QR data for testing
            $qrData = [
                "type" => "upi_qr",
                "usage" => "single_use",
                "fixed_amount" => true,
                "payment_amount" => $amount,
                "description" => $description,
            ];

            Log::info('Razorpay QR Data:', [ $qrData ]);
            //Log::info('Using Razorpay Key:', substr($razorpayKey, 0, 12) . '...');
            
            $qr = $api->qrCode->create($qrData);

            Log::info('Razorpay QR Response:', (array) $qr);
            return response()->json([
                'status' => 'success',
                'qr_id' => $qr['id'],
                'qr_image' => $qr['image_url'],
                'amount' => $amount,
            ], 200);

        } catch (\Razorpay\Api\Errors\BadRequestError $e) {
            Log::error('Razorpay Bad Request: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid request: ' . $e->getMessage(),
                'suggestion' => 'Check if QR Code feature is enabled in your Razorpay dashboard',
            ], 400);
        } catch (\Razorpay\Api\Errors\ServerError $e) {
            Log::error('Razorpay Server Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Razorpay server error: ' . $e->getMessage(),
                'suggestion' => 'Try again later or contact Razorpay support',
            ], 503);
        } catch (\Razorpay\Api\Errors\Error $e) {
            Log::error('Razorpay API Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Razorpay API Error: ' . $e->getMessage(),
                'code' => $e->getCode(),
            ], 400);
        } catch (Exception $e) {
            Log::error('General Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
