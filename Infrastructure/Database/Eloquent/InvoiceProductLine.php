<?php

namespace Infrastructure\Database\Eloquent;

use Illuminate\Database\Eloquent\Model;

class InvoiceProductLine extends Model
{
    protected $table = 'invoice_product_lines';
    protected $fillable = [
        'invoice_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_unit_price',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
