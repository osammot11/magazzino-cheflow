<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockMovementRequest;
use App\Models\StockMovement;
use App\Models\Variant;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type');
        $search = mb_substr((string) $request->query('q', ''), 0, 100);
        $movements = StockMovement::query()->with('variant.product')
            ->when(in_array($type, ['in', 'out'], true), fn ($q) => $q->where('type', $type))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('reference', 'like', "%$search%")
                ->orWhereHas('variant', fn ($q) => $q->where('sku', 'like', "%$search%")
                    ->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%$search%")))))
            ->latest('id')->paginate(20)->withQueryString();

        return view('movements.index', compact('movements', 'type', 'search'));
    }

    public function create(): View
    {
        return view('movements.create', ['variants' => Variant::query()->with('product')->whereHas('product', fn ($q) => $q->where('status', 'active'))->orderBy('product_id')->get()->map(fn ($v) => [
            'id' => $v->id, 'label' => $v->product->name.' / '.$v->name.' · '.$v->sku, 'quantity' => $v->quantity,
        ])]);
    }

    public function store(StoreStockMovementRequest $request, StockService $stock): RedirectResponse
    {
        $data = $request->validated();
        $stock->move(Variant::query()->findOrFail($data['variant_id']), $data['type'], (int) $data['quantity'], $data['reason'], $data['reference'] ?? null, $data['notes'] ?? null);

        return to_route('movements.index')->with('success','Movimento registrato. Giacenza aggiornata.');
    }
}
