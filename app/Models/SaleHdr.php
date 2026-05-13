<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleHdr extends Model
{
    protected $table = 'sale_hdr';
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'ho_code', 'br_code', 'inv_no', 'inv_date', 'party_code',
        'gross', 'tax_rate', 'tax_amount', 'nett', 'bill_type',
        'is_locked', 'ord_no', 'sale_type', 'fin_year_id',
    ];

    public function details()
    {
        return $this->hasMany(SaleDtl::class, 'inv_no', 'inv_no')
                    ->where('sale_dtl.br_code', $this->br_code);
    }

    public function party()
    {
        return $this->belongsTo(Party::class, 'party_code', 'party_code');
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class, 'fin_year_id');
    }
}
