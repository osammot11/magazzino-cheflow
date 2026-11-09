<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveProductRequest;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr((string) $request->query('q', ''), 0, 100);
        $status = $request->query('status', 'active');
        $products = Product::query()->withCount('variants')->withSum('variants', 'quantity')
            ->when(in_array($status, ['active', 'archived'], true), fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%$search%")
                ->orWhere('vendor', 'like', "%$search%")->orWhereHas('variants', fn ($q) => $q->where('sku', 'like', "%$search%"))))
            ->latest()->paginate(15)->withQueryString();

        return view('products.index', compact('products', 'search', 'status'));
    }

    public function create(): View
    {
        return view('products.form', ['product' => new Product(['status' => 'active']), 'variants' => []]);
    }

    public function edit(Product $product): View
    {
        return view('products.form', ['product' => $product, 'variants' => $product->variants->map(fn ($v) => [
            'id' => $v->id, 'name' => $v->name, 'sku' => $v->sku, 'barcode' => $v->barcode, 'price' => number_format($v->price_cents / 100, 2, '.', ''),
            'low_stock_threshold' => $v->low_stock_threshold, 'quantity' => $v->quantity,
        ])->all()]);
    }

    public function store(SaveProductRequest $request, StockService $stock): RedirectResponse
    {
        $product = $this->save($request, new Product, $stock);

        return to_route('products.edit', $product)->with('success', 'Prodotto creato correttamente.');
    }

    public function update(SaveProductRequest $request, Product $product, StockService $stock): RedirectResponse
    {
        $this->save($request, $product, $stock);

        return to_route('products.edit', $product)->with('success', 'Prodotto aggiornato.');
    }

    private function save(SaveProductRequest $request, Product $product, StockService $stock): Product
    {
        return DB::transaction(function () use ($request, $product, $stock) {
            $data = $request->validated();
            $product->fill(collect($data)->except('variants')->all())->save();
            foreach ($data['variants'] as $row) {
                $variant = ! empty($row['id']) ? $product->variants()->findOrFail($row['id']) : $product->variants()->make();
                $new = ! $variant->exists;
                $variant->fill(collect($row)->only(['name', 'sku', 'barcode', 'low_stock_threshold'])->all());
                $variant->price_cents = (int) round((float) $row['price'] * 100);
                $variant->save();
                if ($new && ($row['initial_quantity'] ?? 0) > 0) {
                    $stock->move($variant, 'in', (int) $row['initial_quantity'], 'Giacenza iniziale');
                }
            }

            return $product;
        }, 5);
    }
}
