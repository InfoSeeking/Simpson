<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use Auth;
use Validator;
use App\Models\User;
use App\Models\Score;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Utilities\Status;
use App\Utilities\ApiResponse;
use App\Services\RealtimeService;

// Should be called by the front-end every five seconds.
class TickController extends Controller
{
    public function __construct(RealtimeService $realtimeService) {
        $this->realtimeService = $realtimeService;
    }

    public function tick(Request $req) {
        $validator = Validator::make($req->all(), [
            'project_id' => 'required|integer|exists:projects,id'
            ]);
        if ($validator->fails()) 
            return ApiResponse::fromStatus(Status::fromValidator($validator));
        $projectId = $req->input('project_id');
        $user = Auth::user();
        if (!$user) return ApiResponse::fromStatus(Status::fromError('Not logged in'));

        // TODO: verify the user has not performed any actions in last five seconds.
        
        $score = Score::firstOrCreate([
            'user_id' => $user->id,
            'project_id' => $projectId]);
        $score->score += 1;
        $score->save();
        $this->realtimeService
            ->withModel($score)
            ->onProject($projectId)
            ->emit('update');
        return ApiResponse::OK();
    }
}
