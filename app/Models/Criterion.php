<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'attribute_type',
        'unit_or_scale',
        'description',
    ];

    public function assessments(): HasMany
    {
        return $this->hasMany(ProductAssessment::class);
    }

    public function weights(): HasMany
    {
        return $this->hasMany(AhpWeight::class);
    }
}

