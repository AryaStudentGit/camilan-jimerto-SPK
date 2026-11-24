<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SawResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'calculation_run_id',
        'product_id',
        'final_score',
        'rank',
        'priority_tier',
    ];

    protected $casts = [
        'final_score' => 'decimal:6',
        'rank' => 'integer',
    ];

    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(CalculationRun::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

