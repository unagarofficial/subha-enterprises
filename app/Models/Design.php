<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Design extends Model
{
    protected $fillable = [
        'cat_code', 'design_code', 'design_desc', 'uom', 'rate', 'y_rate', 'b_rate',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'cat_code', 'cat_code');
    }

    public function uomUnit()
    {
        return $this->belongsTo(Uom::class, 'uom', 'uom_code');
    }
}
