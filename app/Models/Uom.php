<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Uom extends Model
{
    protected $table = 'uoms';
    protected $primaryKey = 'uom_code';
    protected $fillable = ['uom_name'];
}
