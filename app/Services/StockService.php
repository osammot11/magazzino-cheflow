<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function move(Variant $variant, string $type, int $quantity, string $reason, ?string $reference = null, ?string $notes = null): StockMovement
    {
        return DB::transaction(function () use ($variant, $type, $quantity, $reason, $reference, $notes) {
            $current = Variant::query()->lockForUpdate()->findOrFail($variant->id);
            if ($quantity < 1 || ! in_array($type, ['in', 'out'], true)) {
                throw ValidationException::withMessages(['quantity' => 'Inserisci una quantità valida.']);
            }
            if ($current->product->status !== 'active') {
                throw ValidationException::withMessages(['variant_id' => 'Il prodotto è archiviato. Riattivalo prima di movimentarlo.']);
            }
            $delta = $type === 'in' ? $quantity : -$quantity;
            $before = $current->quantity;
            if ($before + $delta < 0) {
                throw ValidationException::withMessages(['quantity' => 'Disponibilità insufficiente: sono presenti '.$before.' unità.']);
            }
            $current->quantity = $before + $delta;
            $current->save();

            return $current->movements()->create(['type' => $type, 'delta' => $delta, 'quantity_before' => $before, 'quantity_after' => $current->quantity, 'reason' => $reason, 'reference' => $reference, 'notes' => $notes]);
        }, 5);
    }
}
