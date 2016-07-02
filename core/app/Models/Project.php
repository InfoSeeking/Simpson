<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
	protected $table = 'projects';
    protected $fillable = ['title', 'description', 'scores', 'active', 'description'];
    protected $guarded = ['creator_id', 'private'];
    protected $visible = ['state', 'scenario_name', 'next_project', 'prev_project', 'scores', 'private', 'creator_id', 'title', 'description', 'created_at', 'updated_at', 'id', 'active', 'description'];
}
