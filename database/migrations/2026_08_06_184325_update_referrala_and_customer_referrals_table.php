<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->renameColumn('referee', 'customer_number');
            $table->renameColumn('referrer', 'referral_id');
            $table->dropColumn(['status', 'accepted_at', 'rewarded_at']);
        });

        Schema::table('customer_referrals', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->after('id');
            $table->dropColumn(['referee_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->renameColumn('customer_number', 'referee');
            $table->renameColumn('referral_id', 'referrer');
            $table->enum('status', ['pending', 'accepted', 'rewarded', 'expired'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
        });

        Schema::table('customer_referrals', function (Blueprint $table) {
            $table->string('referee_number')->nullable()->after('referrer_number');
            $table->dropColumn('referral_code');
        });
    }
};
