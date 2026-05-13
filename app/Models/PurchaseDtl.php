<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseDtl extends Model
{
    protected $table = 'purchase_dtl';
    protected $fillable = [
        'br_code', 'inv_no', 'sl_no', 'mat_code', 'qty',
        'uom', 'rate', 'amount', 'narration', 'cat_code', 'po_no', 'inv_date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'mat_code', 'mat_code');
    }
}
