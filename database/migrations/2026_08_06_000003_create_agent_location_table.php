<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agent_location', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('agent_id');
            $table->unsignedBigInteger('location_id');

            $table->foreign('agent_id')
                ->references('id')
                ->on('agents')
                ->cascadeOnDelete();

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['agent_id', 'location_id']);
        });

        $now = now();
        $rows = DB::table('locations')
            ->whereNotNull('agent_id')
            ->select('id as location_id', 'agent_id')
            ->get()
            ->map(fn($row) => [
                'agent_id' => $row->agent_id,
                'location_id' => $row->location_id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('agent_location')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_location');
    }
};
