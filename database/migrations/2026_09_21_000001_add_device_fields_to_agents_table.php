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
        Schema::table('agents', function (Blueprint $table) {
            $table->boolean('bypass_device_check')->default(true);
            $table->string('device_id')->nullable();
            $table->json('device_info')->nullable();
            $table->timestamp('device_registered_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'bypass_device_check',
                'device_id',
                'device_info',
                'device_registered_at',
            ]);
        });
    }
};
