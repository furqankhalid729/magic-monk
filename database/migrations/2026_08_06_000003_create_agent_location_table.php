<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $agentIdColumn = ['type' => 'bigint', 'unsigned' => true];
    protected array $locationIdColumn = ['type' => 'bigint', 'unsigned' => true];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->agentIdColumn = $this->resolveIdColumnDefinition('agents');
        $this->locationIdColumn = $this->resolveIdColumnDefinition('locations');

        Schema::create('agent_location', function (Blueprint $table) {
            $table->id();

            $this->addMatchingIdColumn($table, 'agent_id', $this->agentIdColumn);
            $this->addMatchingIdColumn($table, 'location_id', $this->locationIdColumn);

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

    protected function resolveIdColumnDefinition(string $tableName): array
    {
        $default = ['type' => 'bigint', 'unsigned' => true];

        try {
            if (DB::getDriverName() === 'mysql') {
                // SHOW COLUMNS is typically permitted even when information_schema access is restricted.
                $column = DB::selectOne("SHOW COLUMNS FROM `{$tableName}` LIKE 'id'");
                $columnType = strtolower((string) ($column->Type ?? ''));

                if ($columnType === '') {
                    return $default;
                }

                $type = str_contains($columnType, 'bigint') ? 'bigint' : 'int';
                $unsigned = str_contains($columnType, 'unsigned');

                return [
                    'type' => $type,
                    'unsigned' => $unsigned,
                ];
            }
        } catch (\Throwable $throwable) {
            // Fallback to bigint when schema introspection is unavailable.
        }

        return $default;
    }

    protected function addMatchingIdColumn(Blueprint $table, string $columnName, array $definition): void
    {
        $type = $definition['type'] ?? 'bigint';
        $unsigned = (bool) ($definition['unsigned'] ?? true);

        if ($type === 'int') {
            if ($unsigned) {
                $table->unsignedInteger($columnName);

                return;
            }

            $table->integer($columnName);

            return;
        }

        if ($unsigned) {
            $table->unsignedBigInteger($columnName);

            return;
        }

        $table->bigInteger($columnName);
    }
};
