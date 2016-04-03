<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    protected $table = 'connections';
    protected $visible = ['initiator_id', 'recipient_id', 'intermediary_id', 'project_id', 'id'];
    protected $fillable = ['initiator_id', 'recipient_id', 'intermediary_id', 'project_id'];
}
