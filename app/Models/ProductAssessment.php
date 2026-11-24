<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'calculation_run_id',
        'product_id',
        'criterion_id',
        'raw_value',
    ];

    protected $casts = [
        'raw_value' => 'decimal:2',
    ];

    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(CalculationRun::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}

