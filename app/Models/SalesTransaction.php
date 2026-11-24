<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'row_id',
        'invoice_no',
        'transaction_date',
        'year',
        'month',
        'month_period',
        'day_name',
        'original_note_no',
        'original_store_name',
        'standard_store_name',
        'district',
        'transaction_status',
        'original_item_name',
        'sku_code',
        'standard_product_name',
        'product_id',
        'product_category',
        'qty_original',
        'qty_clean',
        'unit_price',
        'total_written',
        'total_valid',
        'clean_turnover',
        'difference_amount',
        'spelling_status',
        'spelling_notes',
        'audit_notes',
        'photo_source',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'year' => 'integer',
        'month' => 'integer',
        'qty_original' => 'integer',
        'qty_clean' => 'integer',
        'unit_price' => 'decimal:2',
        'total_written' => 'decimal:2',
        'total_valid' => 'decimal:2',
        'clean_turnover' => 'decimal:2',
        'difference_amount' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

