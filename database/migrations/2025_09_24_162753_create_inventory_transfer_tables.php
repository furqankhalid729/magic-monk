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
        // Schema::create('inventory_transfers', function (Blueprint $table) {
        //     $table->id();

        //     // From which agent (could be store or agent)
        //     $table->unsignedInteger('source_agent_id')->nullable();
        //     $table->foreign('source_agent_id')
        //         ->references('id')
        //         ->on('agents')
        //         ->nullOnDelete();

        //     $table->unsignedInteger('destination_agent_id')->nullable();
        //     $table->foreign('destination_agent_id')
        //         ->references('id')
        //         ->on('agents')
        //         ->nullOnDelete();

        //     // borrow, return, buy, adjustment
        //     $table->enum('transfer_type', ['borrow', 'return', 'buy', 'adjustment']);

        //     $table->text('notes')->nullable();
        //     $table->timestamps();
        // });

        // Items inside each transfer
        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_transfer_id')
                ->constrained('inventory_transfers')
                ->onDelete('cascade');

            $table->unsignedInteger('product_id');
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->integer('quantity');
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_items');
        Schema::dropIfExists('inventory_transfers');
    }
};
