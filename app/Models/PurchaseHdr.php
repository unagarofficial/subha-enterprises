<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseHdr extends Model
{
    protected $table = 'purchase_hdr';
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'br_code', 'inv_no', 'inv_date', 'party_code',
        'gross', 'tax_rate', 'tax_amount', 'nett', 'fin_year_id',
    ];

    public function details()
    {
        return $this->hasMany(PurchaseDtl::class, 'inv_no', 'inv_no')
                    ->where('purchase_dtl.br_code', $this->br_code);
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
