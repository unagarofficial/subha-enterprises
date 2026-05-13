<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Firm extends Model
{
    protected $primaryKey = 'firm_code';
    protected $fillable = [
        'firm_name', 'address', 'place', 'phone', 'mobile',
        'website', 'tin_no', 'ho_code', 'type',
    ];
}
