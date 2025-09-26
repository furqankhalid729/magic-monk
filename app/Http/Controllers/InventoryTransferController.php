<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryTransfer;
use App\Models\TransferItem;
use Illuminate\Support\Facades\DB;

class InventoryTransferController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'source_location_id' => 'required|exists:locations,id',
            'destination_location_id' => 'required|exists:locations,id|different:source_location_id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
            'type' => 'required|string|max:100',
        ]);

        try {
            DB::beginTransaction();
            $transfer = InventoryTransfer::create([
                'source_agent_id' => $validatedData['source_location_id'],
                'destination_agent_id' => $validatedData['destination_location_id'],
                'transfer_type' => $validatedData['type'],
                'notes' => $validatedData['notes'] ?? null,
            ]);
            // Create transfer items
            foreach ($validatedData['products'] as $item) {
                TransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Inventory transfer request received successfully.',
                'data' => $validatedData,
                'transfer_id' => $transfer->id,
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Failed to create inventory transfer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
