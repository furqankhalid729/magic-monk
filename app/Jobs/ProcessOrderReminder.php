<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\WhatsAppPayReminder;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessOrderReminder implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $reminders = WhatsAppPayReminder::where('is_sent', false)
            ->where('created_at', '<=', Carbon::now()->subMinute())
            ->get();
        foreach ($reminders as $reminder) {
            $data = $reminder->order_data;
            $orderItems = $data['order_items'] ?? [];
            $itemsString = collect($orderItems)
                ->map(fn($item) => "{$item['item_name']} x {$item['quantity']}")
                ->join(' | ');
            $totalItems = count($orderItems);
            $totalAmount = $data['total_amount'] ?? 0;
            $discount = $data['discount'] ?? 0;
            $payAbleAmount = $data['additional_info']['to_collect'] ?? 0;
            $bodyData = [
                $itemsString,
                $totalItems,
                number_format($totalAmount, 2),
                number_format($discount, 2),
                number_format($payAbleAmount, 2),
            ];
            $response = sendInteraktMessage(
                $reminder->phone_number,
                $bodyData,
                [],
                'backup_paymentfm',
                null
            );
            Log::info('Sent WhatsApp Pay reminder', ['response' => $response]);
            $reminder->is_sent = true;
            $reminder->sent_at = now();
            $reminder->save();
        }
        self::dispatch()->delay(now()->addSeconds(10));
    }
}
