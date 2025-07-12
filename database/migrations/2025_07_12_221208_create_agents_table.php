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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('photo_path')->nullable();
            $table->string('whatsapp_number', 10);
            $table->string('pan_number');
            $table->string('pan_card_path')->nullable();
            $table->string('aadhar_card_path')->nullable();
            $table->string('upi_id')->nullable();
            $table->string('city');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('source_pos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
