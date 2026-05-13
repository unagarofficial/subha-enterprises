<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $primaryKey = 'br_code';
    protected $fillable = ['br_name', 'br_place'];

    public function users()
    {
        return $this->hasMany(User::class, 'br_code', 'br_code');
    }

    public function parties()
    {
        return $this->hasMany(Party::class, 'br_code', 'br_code');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'br_code', 'br_code');
    }
}
