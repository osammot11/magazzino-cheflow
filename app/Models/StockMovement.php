<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = ['type', 'delta', 'quantity_before', 'quantity_after', 'reason', 'reference', 'notes'];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }
}
