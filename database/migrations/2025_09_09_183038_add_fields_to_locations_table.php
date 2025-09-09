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
        Schema::table('locations', function (Blueprint $table) {
            $table->integer('reach_or_flats')->nullable();
            $table->string('road_name')->nullable()->after('reach_or_flats');
            $table->string('sub_locality')->nullable()->after('road_name');
            $table->string('city')->nullable()->after('sub_locality');
            $table->string('state')->nullable()->after('city');
            $table->string('pincode', 20)->nullable()->after('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'reach_or_flats',
                'road_name',
                'sub_locality',
                'city',
                'state',
                'pincode',
            ]);
        });
    }
};
