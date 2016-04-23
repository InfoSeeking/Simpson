<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    protected $table ='scores';
    protected $fillable = ['user_id', 'project_id', 'score'];
    protected $visible = ['user_id', 'project_id', 'score', 'created_at', 'updated_at'];
}
