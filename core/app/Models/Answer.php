<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $table = 'answers';
    protected $fillable = ['user_id', 'name', 'project_id'];
    protected $visible = ['id', 'name', 'user_id', 'project_id', 'answered'];
}
