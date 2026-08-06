<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected string $agentIdType = 'bigint';
    protected string $locationIdType = 'bigint';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->agentIdType = $this->resolveIdColumnType('agents');
        $this->locationIdType = $this->resolveIdColumnType('locations');

        Schema::create('agent_location', function (Blueprint $table) {
            $table->id();

            if ($this->agentIdType === 'int') {
                $table->unsignedInteger('agent_id');
            } else {
                $table->unsignedBigInteger('agent_id');
            }

            if ($this->locationIdType === 'int') {
                $table->unsignedInteger('location_id');
            } else {
                $table->unsignedBigInteger('location_id');
            }

            $table->timestamps();

            $table->unique(['agent_id', 'location_id']);
        });

        Schema::table('agent_location', function (Blueprint $table) {
            $table->foreign('agent_id')
                ->references('id')
                ->on('agents')
                ->cascadeOnDelete();

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->cascadeOnDelete();
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

    protected function resolveIdColumnType(string $tableName): string
    {
        try {
            if (DB::getDriverName() === 'mysql') {
                $databaseName = DB::getDatabaseName();
                $column = DB::table('information_schema.columns')
                    ->select('DATA_TYPE')
                    ->where('TABLE_SCHEMA', $databaseName)
                    ->where('TABLE_NAME', $tableName)
                    ->where('COLUMN_NAME', 'id')
                    ->first();

                $dataType = strtolower((string) ($column->DATA_TYPE ?? ''));

                if (in_array($dataType, ['int', 'integer'], true)) {
                    return 'int';
                }
            }
        } catch (\Throwable $throwable) {
            // Fallback to bigint when schema introspection is unavailable.
        }

        return 'bigint';
    }
};
