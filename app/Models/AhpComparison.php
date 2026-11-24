<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AhpComparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'calculation_run_id',
        'criterion_1_id',
        'criterion_2_id',
        'comparison_value',
    ];

    protected $casts = [
        'comparison_value' => 'decimal:4',
    ];

    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(CalculationRun::class);
    }

    public function criterion1(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criterion_1_id');
    }

    public function criterion2(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criterion_2_id');
    }
}

