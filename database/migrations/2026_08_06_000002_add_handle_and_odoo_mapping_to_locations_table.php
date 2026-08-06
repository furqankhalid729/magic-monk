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
            $table->string('handle')->nullable()->after('building_name');
            $table->unsignedBigInteger('odoo_pos_config_id')->nullable()->after('handle');
            $table->string('odoo_pos_config_name')->nullable()->after('odoo_pos_config_id');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->unique('handle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique(['handle']);
            $table->dropColumn([
                'handle',
                'odoo_pos_config_id',
                'odoo_pos_config_name',
            ]);
        });
    }
};