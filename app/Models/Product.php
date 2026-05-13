<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $primaryKey = 'mat_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'cat_code', 'mat_code', 'mat_name', 'uom',
        'sale_rate', 'y_rate', 'b_rate', 'br_code',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'cat_code', 'cat_code');
    }

    public function uomUnit()
    {
        return $this->belongsTo(Uom::class, 'uom', 'uom_code');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'br_code', 'br_code');
    }

    public function stock()
    {
        return $this->hasOne(Stock::class, 'mat_code', 'mat_code');
    }
}
