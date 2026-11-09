<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function dashboard(): View
    {
        $active = Variant::query()->whereHas('product', fn ($q) => $q->where('status', 'active'));

        return view('dashboard', [
            'productCount' => Product::query()->where('status', 'active')->count(),
            'variantCount' => (clone $active)->count(), 'units' => (clone $active)->sum('quantity'),
            'lowCount' => (clone $active)->whereColumn('quantity', '<=', 'low_stock_threshold')->count(),
            'value' => (clone $active)->selectRaw('COALESCE(SUM(quantity * price_cents), 0) as total')->value('total'),
            'lowStock' => (clone $active)->with('product')->whereColumn('quantity', '<=', 'low_stock_threshold')->orderBy('quantity')->limit(5)->get(),
            'movements' => StockMovement::query()->with('variant.product')->latest('id')->limit(6)->get(),
        ]);
    }

    public function index(Request $request): View
    {
        $search = mb_substr((string) $request->query('q', ''), 0, 100);
        $filter = $request->query('stock', 'all');
        $variants = Variant::query()->with('product')->whereHas('product', fn ($q) => $q->where('status', 'active'))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('sku', 'like', "%$search%")
                ->orWhere('name', 'like', "%$search%")->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%$search%"))))
            ->when($filter === 'low', fn ($q) => $q->whereColumn('quantity', '<=', 'low_stock_threshold'))
            ->when($filter === 'out', fn ($q) => $q->where('quantity', 0))
            ->orderBy('product_id')->orderBy('id')->paginate(20)->withQueryString();

        return view('inventory.index',compact('variants','search','filter'));
    }
}
