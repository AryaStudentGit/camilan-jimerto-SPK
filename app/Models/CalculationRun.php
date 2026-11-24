<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalculationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'start_period',
        'end_period',
        'lambda_max',
        'consistency_index',
        'consistency_ratio',
        'is_consistent',
        'is_active_dashboard',
        'created_by',
    ];

    protected $casts = [
        'lambda_max' => 'decimal:6',
        'consistency_index' => 'decimal:6',
        'consistency_ratio' => 'decimal:6',
        'is_consistent' => 'boolean',
        'is_active_dashboard' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comparisons(): HasMany
    {
        return $this->hasMany(AhpComparison::class);
    }

    public function weights(): HasMany
    {
        return $this->hasMany(AhpWeight::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(ProductAssessment::class);
    }

    public function sawResults(): HasMany
    {
        return $this->hasMany(SawResult::class)->orderBy('rank');
    }
}

