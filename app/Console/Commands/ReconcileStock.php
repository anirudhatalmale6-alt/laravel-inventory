<?php

namespace App\Console\Commands;

use App\Services\StockService;
use Illuminate\Console\Command;

class ReconcileStock extends Command
{
    protected $signature = 'inventory:reconcile {--dry-run : Report drift without writing}';

    protected $description = 'Recompute every product stock level from the movement ledger';

    public function handle(StockService $stock): int
    {
        $apply = ! $this->option('dry-run');
        $drift = $stock->reconcile($apply);

        if ($drift === []) {
            $this->info('Every product matches its ledger.');

            return self::SUCCESS;
        }

        $this->table(
            ['SKU', 'Product', 'Stored', 'Ledger'],
            array_map(fn ($row) => [
                $row['product']->sku,
                $row['product']->name,
                $row['was'],
                $row['now'],
            ], $drift)
        );

        $this->warn(count($drift).' product(s) out of step'.($apply ? ' and corrected.' : ' (dry run, nothing written).'));

        return self::SUCCESS;
    }
}
