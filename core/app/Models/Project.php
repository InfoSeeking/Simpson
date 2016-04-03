<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
	protected $table = 'projects';
    protected $fillable = ['title', 'description', 'scores'];
    protected $guarded = ['creator_id', 'private'];
    protected $visible = ['scores', 'private', 'creator_id', 'title', 'description', 'created_at', 'updated_at', 'id'];
}
