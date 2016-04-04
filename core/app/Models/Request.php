<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $table = 'requests';
    protected $fillable = ['initiator_id', 'recipient_id', 'type', 'answer_id', 'state', 'project_id'];
    protected $visible = ['id', 'initiator_id', 'recipient_id', 'type', 'answer_id', 'state', 'project_id'];
}
