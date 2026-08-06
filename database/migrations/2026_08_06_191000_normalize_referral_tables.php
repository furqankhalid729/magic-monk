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
        if (Schema::hasColumn('referrals', 'referee')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->renameColumn('referee', 'customer_number');
            });
        }

        if (Schema::hasColumn('referrals', 'referrer')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->renameColumn('referrer', 'referral_id');
            });
        }

        $referralDropColumns = array_values(array_filter([
            Schema::hasColumn('referrals', 'status') ? 'status' : null,
            Schema::hasColumn('referrals', 'accepted_at') ? 'accepted_at' : null,
            Schema::hasColumn('referrals', 'rewarded_at') ? 'rewarded_at' : null,
        ]));

        if ($referralDropColumns !== []) {
            Schema::table('referrals', function (Blueprint $table) use ($referralDropColumns) {
                $table->dropColumn($referralDropColumns);
            });
        }

        if (!Schema::hasColumn('customer_referrals', 'referral_code')) {
            Schema::table('customer_referrals', function (Blueprint $table) {
                $table->string('referral_code')->nullable()->after('id');
            });
        }

        $customerReferralDropColumns = array_values(array_filter([
            Schema::hasColumn('customer_referrals', 'referee_number') ? 'referee_number' : null,
            Schema::hasColumn('customer_referrals', 'joined_at') ? 'joined_at' : null,
            Schema::hasColumn('customer_referrals', 'ordered_at') ? 'ordered_at' : null,
        ]));

        if ($customerReferralDropColumns !== []) {
            Schema::table('customer_referrals', function (Blueprint $table) use ($customerReferralDropColumns) {
                $table->dropColumn($customerReferralDropColumns);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('customer_referrals', 'referee_number')) {
            Schema::table('customer_referrals', function (Blueprint $table) {
                $table->string('referee_number')->nullable()->after('referrer_number');
            });
        }

        if (!Schema::hasColumn('customer_referrals', 'joined_at')) {
            Schema::table('customer_referrals', function (Blueprint $table) {
                $table->timestamp('joined_at')->nullable();
            });
        }

        if (!Schema::hasColumn('customer_referrals', 'ordered_at')) {
            Schema::table('customer_referrals', function (Blueprint $table) {
                $table->timestamp('ordered_at')->nullable();
            });
        }

        if (Schema::hasColumn('customer_referrals', 'referral_code')) {
            Schema::table('customer_referrals', function (Blueprint $table) {
                $table->dropColumn('referral_code');
            });
        }

        if (!Schema::hasColumn('referrals', 'status')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->enum('status', ['pending', 'accepted', 'rewarded', 'expired'])->default('pending');
            });
        }

        if (!Schema::hasColumn('referrals', 'accepted_at')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->timestamp('accepted_at')->nullable();
            });
        }

        if (!Schema::hasColumn('referrals', 'rewarded_at')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->timestamp('rewarded_at')->nullable();
            });
        }

        if (Schema::hasColumn('referrals', 'customer_number')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->renameColumn('customer_number', 'referee');
            });
        }

        if (Schema::hasColumn('referrals', 'referral_id')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->renameColumn('referral_id', 'referrer');
            });
        }
    }
};