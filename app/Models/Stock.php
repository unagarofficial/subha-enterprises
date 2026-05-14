<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stock';

    protected $fillable = ['br_code', 'mat_code', 'cat_code', 'ob', 'rcpts', 'issues', 'cl_stock'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'mat_code', 'mat_code');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'br_code', 'br_code');
    }
}
