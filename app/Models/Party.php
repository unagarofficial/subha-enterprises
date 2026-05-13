<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    protected $primaryKey = 'party_code';
    protected $fillable = [
        'br_code', 'party_type', 'party_name', 'address', 'place',
        'state', 'phone', 'mobile', 'inout_state', 'tin_grn_flag', 'tin_grn_no',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'br_code', 'br_code');
    }

    public function scopeCustomers($query)
    {
        return $query->where('party_type', 'C');
    }

    public function scopeSuppliers($query)
    {
        return $query->where('party_type', 'S');
    }
}
