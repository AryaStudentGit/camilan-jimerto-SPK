<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AhpWeight extends Model
{
    use HasFactory;

    protected $fillable = [
        'calculation_run_id',
        'criterion_id',
        'weight',
    ];

    protected $casts = [
        'weight' => 'decimal:6',
    ];

    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(CalculationRun::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}

