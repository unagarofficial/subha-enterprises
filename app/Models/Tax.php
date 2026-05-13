<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $primaryKey = 'tax_code';
    protected $fillable = ['tax_name', 'tax_percent'];
}
