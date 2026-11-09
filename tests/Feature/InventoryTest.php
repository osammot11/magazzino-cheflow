<?php

namespace Tests\Feature;

use App\Models\StockMovement;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $variant = []): array
    {
        return ['name' => 'T-shirt Cheflow', 'status' => 'active', 'variants' => [array_merge([
            'name' => 'Nero / M', 'sku' => 'CF-NERO-M', 'price' => '29.90',
            'low_stock_threshold' => 5, 'initial_quantity' => 12,
        ], $variant)]];
    }

    public function test_product_creation_records_initial_stock_and_exact_price(): void
    {
        $this->post(route('products.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('variants', ['sku' => 'CF-NERO-M', 'quantity' => 12, 'price_cents' => 2990]);
        $this->assertDatabaseHas('stock_movements', ['type' => 'in', 'delta' => 12, 'quantity_before' => 0, 'quantity_after' => 12]);
    }

    public function test_stock_movements_update_quantity_and_preserve_audit_trail(): void
    {
        $variant = Variant::factory()->create(['quantity' => 10]);
        $this->post(route('movements.store'), ['variant_id' => $variant->id, 'type' => 'out', 'quantity' => 4, 'reason' => 'Vendita', 'reference' => 'ORD-12'])->assertSessionHasNoErrors()->assertRedirect(route('movements.index'));
        $this->assertSame(6, $variant->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', ['delta' => -4, 'quantity_before' => 10, 'quantity_after' => 6, 'reference' => 'ORD-12']);
        $this->post(route('movements.store'), ['variant_id' => $variant->id, 'type' => 'in', 'quantity' => 3, 'reason' => 'Reso cliente'])->assertSessionHasNoErrors();
        $this->assertSame(9, $variant->fresh()->quantity);
        $this->assertSame(2, StockMovement::query()->count());
    }

    public function test_insufficient_stock_is_rejected_without_a_partial_movement(): void
    {
        $variant = Variant::factory()->create(['quantity' => 2]);
        $this->post(route('movements.store'), ['variant_id' => $variant->id, 'type' => 'out', 'quantity' => 3, 'reason' => 'Vendita'])->assertSessionHasErrors('quantity');
        $this->assertSame(2, $variant->fresh()->quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_duplicate_sku_is_rejected_case_insensitively(): void
    {
        Variant::factory()->create(['sku' => 'CF-NERO-M']);
        $this->post(route('products.store'), $this->payload(['sku' => ' cf-nero-m ']))->assertSessionHasErrors('variants.0.sku');
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_existing_stock_cannot_be_overwritten_by_editing_product(): void
    {
        $variant = Variant::factory()->create(['quantity' => 8]);
        $this->put(route('products.update', $variant->product), $this->payload(['id' => $variant->id, 'initial_quantity' => 100, 'quantity' => 500]))->assertSessionHasNoErrors();
        $this->assertSame(8, $variant->fresh()->quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_foreign_variants_and_removal_of_existing_variants_are_rejected(): void
    {
        $variant = Variant::factory()->create();
        $other = Variant::factory()->create();
        $this->put(route('products.update', $variant->product), $this->payload(['id' => $other->id]))->assertSessionHasErrors('variants.0.id');
        $this->put(route('products.update', $variant->product), $this->payload())->assertSessionHasErrors('variants');
        $this->assertDatabaseCount('variants', 2);
    }

    public function test_archiving_preserves_stock_history_and_blocks_new_movements(): void
    {
        $variant = Variant::factory()->create(['quantity' => 8]);
        $this->post(route('movements.store'), ['variant_id' => $variant->id, 'type' => 'out', 'quantity' => 1, 'reason' => 'Vendita'])->assertSessionHasNoErrors();
        $payload = $this->payload(['id' => $variant->id]);
        $payload['status'] = 'archived';
        $this->put(route('products.update', $variant->product), $payload)->assertSessionHasNoErrors();
        $this->post(route('movements.store'), ['variant_id' => $variant->id, 'type' => 'in', 'quantity' => 1, 'reason' => 'Acquisto'])->assertSessionHasErrors('variant_id');
        $this->assertSame(7, $variant->fresh()->quantity);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->get(route('inventory.index'))->assertDontSee($variant->fresh()->sku);
        $this->get(route('movements.index'))->assertSee($variant->fresh()->sku);
    }

    public function test_invalid_movements_are_rejected(): void
    {
        $variant = Variant::factory()->create();
        foreach ([0, -1, 1.5] as $quantity) {
            $this->post(route('movements.store'), ['variant_id' => $variant->id, 'type' => 'in', 'quantity' => $quantity, 'reason' => 'Acquisto'])->assertSessionHasErrors('quantity');
        }
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_pages_render_empty_and_populated_states_and_filters(): void
    {
        foreach (['dashboard', 'products.index', 'products.create', 'inventory.index', 'movements.index', 'movements.create'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $variant = Variant::factory()->create(['sku' => 'TEST-LOW', 'quantity' => 2, 'low_stock_threshold' => 5]);
        Variant::factory()->create(['sku' => 'TEST-HIGH', 'quantity' => 50]);
        $this->get(route('dashboard'))->assertOk()->assertSee('TEST-LOW')->assertDontSee('TEST-HIGH');
        $this->get(route('inventory.index', ['stock' => 'low']))->assertSee('TEST-LOW')->assertDontSee('TEST-HIGH');
        $this->get(route('inventory.index', ['stock' => 'out']))->assertDontSee('TEST-LOW');
        $this->get(route('products.edit', $variant->product))->assertOk()->assertSee('TEST-LOW');
        $this->get(route('movements.create'))->assertOk()->assertSee('TEST-LOW');
        $this->get(route('products.index', ['q' => 'TEST-LOW']))->assertSee($variant->product->name);
    }

    public function test_invalid_second_variant_rolls_back_entire_product(): void
    {
        $payload = $this->payload();
        $payload['variants'][] = array_merge($payload['variants'][0], ['sku' => 'OTHER', 'initial_quantity' => -1]);
        $this->post(route('products.store'), $payload)->assertSessionHasErrors('variants.1.initial_quantity');
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('variants', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
