<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $primaryKey = 'cat_code';
    protected $fillable = ['cat_name'];

    public function products()
    {
        return $this->hasMany(Product::class, 'cat_code', 'cat_code');
    }
}
