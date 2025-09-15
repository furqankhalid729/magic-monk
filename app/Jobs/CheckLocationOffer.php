<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckLocationOffer implements ShouldQueue
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
        $now = Carbon::now();
        $locations = Location::where('is_offer_live', true)->get();
        foreach ($locations as $location) {
            if ($location->offer_live_until && $now->greaterThan($location->offer_live_until)) {
                $location->is_offer_live = false;
                $location->save();
            }
        }
    }
}
