<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlySalesRecap extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'year',
        'month',
        'total_units_sold',
        'total_revenue',
        'notes',
        'input_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'total_units_sold' => 'integer',
        'total_revenue' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function getPeriodAttribute(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}

