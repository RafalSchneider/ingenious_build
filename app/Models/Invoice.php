<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';
    protected $fillable = [
        'status',
        'customer_name',
        'customer_email',
        'total_price',
    ];

    public function productLines()
    {
        return $this->hasMany(InvoiceProductLine::class);
    }
}
