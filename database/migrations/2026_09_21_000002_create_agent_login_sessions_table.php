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
        Schema::create('agent_login_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->date('login_date');
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->string('device_id')->nullable();
            $table->json('device_info')->nullable();
            $table->boolean('device_matched')->nullable();
            $table->boolean('blocked')->default(false);
            $table->json('mismatch_fields')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('public_ip', 45)->nullable();
            $table->string('app_version')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'login_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_login_sessions');
    }
};
