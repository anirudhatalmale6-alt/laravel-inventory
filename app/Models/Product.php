<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'category_id',
        'unit',
        'cost_price',
        'sale_price',
        'reorder_level',
        'location',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock_on_hand' => 'integer',
        'reorder_level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('id');
    }

    public function isLowStock(): bool
    {
        return $this->reorder_level > 0 && $this->stock_on_hand <= $this->reorder_level;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock_on_hand <= 0;
    }

    public function stockValue(): float
    {
        return (float) $this->cost_price * $this->stock_on_hand;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('reorder_level', '>', 0)
            ->whereColumn('stock_on_hand', '<=', 'reorder_level');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%");
        });
    }
}
