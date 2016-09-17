<?php

namespace App\Services;

use Auth;
use Validator;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Request;
use App\Models\Membership;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\RealtimeService;
use App\Services\ConnectionService;
use App\Services\LogService;
use App\Utilities\Status;
use App\Utilities\StatusCodes;

class QuestionService {
    public function __construct(
        MembershipService $memberService,
        RealtimeService $realtimeService,
        ConnectionService $connectionService,
        LogService $logService
        ) {
        $this->memberService = $memberService;
        $this->realtimeService = $realtimeService;
        $this->connectionService = $connectionService;
        $this->logService = $logService;
        $this->user = Auth::user();
    }
    
    // Get all questions for this this project.
    public function getMultiple($args) {
        $validator = Validator::make($args, [
            'project_id' => 'required|exists:projects,id',
            ]);
        if ($validator->fails()) return Status::fromValidator($validator);

        $memberStatus = $this->memberService->checkPermission($args['project_id'], 'r', $this->user);
        if (!$memberStatus->isOK()) return Status::fromStatus($memberStatus);
        $questions = Question::where('project_id', $args['project_id']);
        return Status::fromResult($questions->get());
    }
    
    public function handle(Request $request) {
        return false;
    }
}