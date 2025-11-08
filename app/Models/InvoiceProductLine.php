<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
