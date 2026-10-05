<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'sku' => 'SKU-TEST-1',
            'name' => 'Test widget',
            'unit' => 'pcs',
            'cost_price' => '4.50',
            'sale_price' => '9.99',
            'reorder_level' => 5,
            'is_active' => '1',
        ], $overrides);
    }

    public function test_guests_are_sent_to_the_login_screen(): void
    {
        foreach (['/dashboard', '/products', '/movements', '/categories'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_the_root_url_lands_on_the_dashboard_once_signed_in(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Stock value');
    }

    public function test_a_product_can_be_created_with_opening_stock(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('products.store'), $this->validPayload([
            'opening_stock' => 30,
        ]));

        $product = Product::firstWhere('sku', 'SKU-TEST-1');

        $response->assertRedirect(route('products.show', $product));
        $this->assertSame(30, $product->stock_on_hand);
        $this->assertSame(1, $product->movements()->count());
        $this->assertSame('Opening stock', $product->movements()->first()->reference);
        $this->assertSame($user->id, $product->movements()->first()->user_id);
    }

    public function test_a_product_submitted_without_the_optional_fields_still_saves(): void
    {
        // The validator drops absent nullable keys, so the controller must not
        // read them blind. This is the form with every optional field left empty.
        $response = $this->actingAs(User::factory()->create())
            ->post(route('products.store'), $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $product = Product::firstWhere('sku', 'SKU-TEST-1');
        $this->assertNotNull($product);
        $this->assertNull($product->description);
        $this->assertNull($product->category_id);
        $this->assertNull($product->location);
        $this->assertSame(0, $product->stock_on_hand);
        $this->assertSame(0, $product->movements()->count());
    }

    public function test_the_sku_must_be_unique(): void
    {
        Product::factory()->create(['sku' => 'SKU-TEST-1']);

        $this->actingAs(User::factory()->create())
            ->post(route('products.store'), $this->validPayload())
            ->assertSessionHasErrors('sku');
    }

    public function test_unticking_active_turns_the_product_off(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $this->actingAs(User::factory()->create())
            ->put(route('products.update', $product), $this->validPayload([
                'sku' => $product->sku,
                'is_active' => '0',
            ]));

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_an_edit_keeps_the_products_own_sku(): void
    {
        $product = Product::factory()->create(['sku' => 'SKU-KEEP']);

        $this->actingAs(User::factory()->create())
            ->put(route('products.update', $product), $this->validPayload(['sku' => 'SKU-KEEP', 'name' => 'Renamed']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $product->fresh()->name);
    }

    public function test_a_movement_can_be_filed_from_the_product_page(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('products.movements.store', $product), ['type' => 'in', 'quantity' => 12])
            ->assertRedirect(route('products.show', $product));

        $this->assertSame(12, $product->fresh()->stock_on_hand);
    }

    public function test_overdrawing_stock_comes_back_as_a_validation_error(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('products.movements.store', $product), ['type' => 'out', 'quantity' => 5])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(0, $product->fresh()->stock_on_hand);
        $this->assertSame(0, $product->movements()->count());
    }

    public function test_the_low_stock_filter_only_lists_flagged_products(): void
    {
        $low = Product::factory()->create(['name' => 'Nearly gone', 'reorder_level' => 10]);
        $fine = Product::factory()->create(['name' => 'Plenty left', 'reorder_level' => 1]);
        $fine->forceFill(['stock_on_hand' => 50])->save();

        $this->actingAs(User::factory()->create())
            ->get(route('products.index', ['status' => 'low']))
            ->assertOk()
            ->assertSee('Nearly gone')
            ->assertDontSee('Plenty left');

        $this->assertTrue($low->isLowStock());
    }

    public function test_search_matches_sku_and_name(): void
    {
        Product::factory()->create(['sku' => 'FIND-ME-01', 'name' => 'Needle']);
        Product::factory()->create(['sku' => 'OTHER-02', 'name' => 'Haystack']);

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('products.index', ['q' => 'FIND-ME']))
            ->assertSee('Needle')->assertDontSee('Haystack');

        $this->actingAs($user)->get(route('products.index', ['q' => 'needle']))
            ->assertSee('Needle')->assertDontSee('Haystack');
    }

    public function test_deleting_a_category_leaves_its_products_in_place(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs(User::factory()->create())
            ->delete(route('categories.destroy', $category))
            ->assertRedirect();

        $this->assertNotNull($product->fresh());
        $this->assertNull($product->fresh()->category_id);
    }

    public function test_deleting_a_product_removes_its_movements(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create())
            ->post(route('products.movements.store', $product), ['type' => 'in', 'quantity' => 3]);

        $this->actingAs(User::factory()->create())
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertNull(Product::find($product->id));
        $this->assertSame(0, \App\Models\StockMovement::where('product_id', $product->id)->count());
    }

    public function test_the_movement_log_can_be_filtered_by_type(): void
    {
        $in = Product::factory()->create(['name' => 'Received item']);
        $out = Product::factory()->create(['name' => 'Shipped item']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('products.movements.store', $in), ['type' => 'in', 'quantity' => 5]);
        $this->actingAs($user)->post(route('products.movements.store', $out), ['type' => 'in', 'quantity' => 5]);
        $this->actingAs($user)->post(route('products.movements.store', $out), ['type' => 'out', 'quantity' => 2]);

        // Asserted on the view data, not the HTML: every product name also appears
        // in the filter dropdown, so assertDontSee would never pass.
        $this->actingAs($user)->get(route('movements.index', ['type' => 'out']))
            ->assertOk()
            ->assertSee('Shipped item')
            ->assertViewHas('movements', fn ($movements) => $movements->count() === 1
                && $movements->first()->type === 'out'
                && $movements->first()->product_id === $out->id);
    }
}
