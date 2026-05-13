<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{
    protected $table = 'financial_years';
    protected $fillable = ['year_name', 'start_date', 'end_date', 'is_active'];

    public static function active()
    {
        return static::where('is_active', 1)->first();
    }
}
