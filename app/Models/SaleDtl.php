<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDtl extends Model
{
    protected $table = 'sale_dtl';
    protected $fillable = [
        'br_code', 'inv_no', 'sl_no', 'mat_code', 'qty',
        'uom', 'rate', 's_value', 'narration', 'inv_date', 'sale_type',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'mat_code', 'mat_code');
    }
}
