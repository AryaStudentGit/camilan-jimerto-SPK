<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function monthlySalesRecaps(): HasMany
    {
        return $this->hasMany(MonthlySalesRecap::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(ProductAssessment::class);
    }

    public function sawResults(): HasMany
    {
        return $this->hasMany(SawResult::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class);
    }
}

