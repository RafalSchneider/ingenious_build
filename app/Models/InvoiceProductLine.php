<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InvoiceProductLine extends Model
{
    protected $table = 'invoice_product_lines';

    // UUID configuration
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'invoice_id',
        'name',
        'price',
        'quantity',
    ];

    protected $casts = [
        'price' => 'integer',
        'quantity' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (InvoiceProductLine $line) {
            if (empty($line->id)) {
                $line->id = (string) Str::uuid();
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // ==================== DOMAIN LOGIC ====================

    public function getTotalUnitPrice(): int
    {
        return $this->quantity * $this->price;
    }
}
