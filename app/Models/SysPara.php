<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysPara extends Model
{
    protected $table = 'sys_para';

    protected $fillable = ['admin_name', 'admin_pw', 'user_name', 'user_pw'];
}
