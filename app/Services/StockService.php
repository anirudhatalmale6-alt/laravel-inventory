<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    /**
     * Add stock. $quantity is a positive count.
     */
    public function stockIn(Product $product, int $quantity, ?string $reference = null, ?string $note = null, ?int $userId = null): StockMovement
    {
        if ($quantity < 1) {
            throw new RuntimeException('Stock in quantity must be at least 1.');
        }

        return $this->record($product, StockMovement::TYPE_IN, $quantity, $reference, $note, $userId);
    }

    /**
     * Remove stock. $quantity is a positive count; it is stored as a negative delta.
     */
    public function stockOut(Product $product, int $quantity, ?string $reference = null, ?string $note = null, ?int $userId = null): StockMovement
    {
        if ($quantity < 1) {
            throw new RuntimeException('Stock out quantity must be at least 1.');
        }

        return $this->record($product, StockMovement::TYPE_OUT, -$quantity, $reference, $note, $userId);
    }

    /**
     * Set the stock to an exact figure, writing the difference as an adjustment.
     */
    public function adjustTo(Product $product, int $newLevel, ?string $note = null, ?int $userId = null): ?StockMovement
    {
        if ($newLevel < 0) {
            throw new RuntimeException('Stock level cannot be negative.');
        }

        return DB::transaction(function () use ($product, $newLevel, $note, $userId) {
            $locked = Product::lockForUpdate()->findOrFail($product->getKey());
            $delta = $newLevel - $locked->stock_on_hand;

            if ($delta === 0) {
                return null;
            }

            return $this->write($locked, StockMovement::TYPE_ADJUSTMENT, $delta, null, $note, $userId);
        });
    }

    private function record(Product $product, string $type, int $delta, ?string $reference, ?string $note, ?int $userId): StockMovement
    {
        return DB::transaction(function () use ($product, $type, $delta, $reference, $note, $userId) {
            // Lock the row so two people filing movements at once cannot both read
            // the same balance and overwrite each other.
            $locked = Product::lockForUpdate()->findOrFail($product->getKey());

            if ($delta < 0 && $locked->stock_on_hand + $delta < 0) {
                throw new RuntimeException(
                    "Only {$locked->stock_on_hand} {$locked->unit} of {$locked->name} in stock, cannot remove ".abs($delta).'.'
                );
            }

            return $this->write($locked, $type, $delta, $reference, $note, $userId);
        });
    }

    private function write(Product $product, string $type, int $delta, ?string $reference, ?string $note, ?int $userId): StockMovement
    {
        $balance = $product->stock_on_hand + $delta;

        $movement = $product->movements()->create([
            'user_id' => $userId,
            'type' => $type,
            'quantity' => $delta,
            'balance_after' => $balance,
            'reference' => $reference,
            'note' => $note,
        ]);

        $product->forceFill(['stock_on_hand' => $balance])->save();

        return $movement;
    }

    /**
     * Recompute stock_on_hand from the ledger. Returns the products that were out of step.
     *
     * @return array<int, array{product: Product, was: int, now: int}>
     */
    public function reconcile(bool $apply = true): array
    {
        $drift = [];

        Product::query()->chunkById(200, function ($products) use (&$drift, $apply) {
            foreach ($products as $product) {
                $ledger = (int) $product->movements()->sum('quantity');

                if ($ledger === $product->stock_on_hand) {
                    continue;
                }

                $drift[] = ['product' => $product, 'was' => $product->stock_on_hand, 'now' => $ledger];

                if ($apply) {
                    $product->forceFill(['stock_on_hand' => $ledger])->save();
                }
            }
        });

        return $drift;
    }
}
