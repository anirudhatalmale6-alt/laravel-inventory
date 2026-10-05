<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stock = app(StockService::class);
    }

    public function test_stock_in_raises_the_level_and_writes_a_positive_movement(): void
    {
        $product = Product::factory()->create();

        $movement = $this->stock->stockIn($product, 25, 'PO-1');

        $this->assertSame(25, $product->fresh()->stock_on_hand);
        $this->assertSame(25, $movement->quantity);
        $this->assertSame(25, $movement->balance_after);
        $this->assertSame(StockMovement::TYPE_IN, $movement->type);
    }

    public function test_stock_out_stores_a_negative_delta(): void
    {
        $product = Product::factory()->create();
        $this->stock->stockIn($product, 10);

        $movement = $this->stock->stockOut($product->fresh(), 4);

        $this->assertSame(6, $product->fresh()->stock_on_hand);
        $this->assertSame(-4, $movement->quantity);
        $this->assertSame(6, $movement->balance_after);
    }

    public function test_stock_out_cannot_push_the_level_below_zero(): void
    {
        $product = Product::factory()->create();
        $this->stock->stockIn($product, 3);

        $this->expectException(RuntimeException::class);

        try {
            $this->stock->stockOut($product->fresh(), 4);
        } finally {
            // The guard must leave both the level and the ledger untouched.
            $this->assertSame(3, $product->fresh()->stock_on_hand);
            $this->assertSame(1, $product->movements()->count());
        }
    }

    public function test_adjustment_writes_only_the_difference(): void
    {
        $product = Product::factory()->create();
        $this->stock->stockIn($product, 10);

        $movement = $this->stock->adjustTo($product->fresh(), 7);

        $this->assertSame(-3, $movement->quantity);
        $this->assertSame(7, $movement->balance_after);
        $this->assertSame(7, $product->fresh()->stock_on_hand);
    }

    public function test_adjustment_to_the_current_level_records_nothing(): void
    {
        $product = Product::factory()->create();
        $this->stock->stockIn($product, 5);

        $this->assertNull($this->stock->adjustTo($product->fresh(), 5));
        $this->assertSame(1, $product->movements()->count());
    }

    public function test_the_ledger_always_sums_to_the_stored_level(): void
    {
        $product = Product::factory()->create();
        $this->stock->stockIn($product, 40, 'PO-9');
        $this->stock->stockOut($product->fresh(), 12);
        $this->stock->adjustTo($product->fresh(), 30);

        $product->refresh();

        $this->assertSame(30, $product->stock_on_hand);
        $this->assertSame(30, (int) $product->movements()->sum('quantity'));
        $this->assertSame([], app(StockService::class)->reconcile(false));
    }

    public function test_reconcile_corrects_a_level_that_drifted_from_the_ledger(): void
    {
        $product = Product::factory()->create();
        $this->stock->stockIn($product, 20);

        // Simulate a direct database edit that bypassed the service.
        $product->forceFill(['stock_on_hand' => 99])->save();

        $drift = $this->stock->reconcile();

        $this->assertCount(1, $drift);
        $this->assertSame(99, $drift[0]['was']);
        $this->assertSame(20, $drift[0]['now']);
        $this->assertSame(20, $product->fresh()->stock_on_hand);
    }

    public function test_low_stock_scope_matches_the_model_helper(): void
    {
        $low = Product::factory()->create(['reorder_level' => 10]);
        $fine = Product::factory()->create(['reorder_level' => 10]);
        $unflagged = Product::factory()->create(['reorder_level' => 0]);

        $this->stock->stockIn($low, 10);
        $this->stock->stockIn($fine, 11);

        $this->assertTrue($low->fresh()->isLowStock());
        $this->assertFalse($fine->fresh()->isLowStock());
        $this->assertFalse($unflagged->fresh()->isLowStock(), 'A reorder level of 0 must never flag.');

        $this->assertEqualsCanonicalizing(
            [$low->id],
            Product::lowStock()->pluck('id')->all()
        );
    }
}
