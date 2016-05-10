<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = ['user_id', 'key', 'value', 'project_id', 'request_id', 'connection_id'];
}
