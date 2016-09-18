<?php

namespace App\Services;

use Auth;
use Validator;

use App\Models\Answer;
use App\Models\Request;
use App\Models\Membership;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\RealtimeService;
use App\Services\ConnectionService;
use App\Services\LogService;
use App\Utilities\Status;
use App\Utilities\StatusCodes;

class AnswerService {
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

	// TODO: if this function is unused, remove it.
	public function getNumUnanswered($projectId) {
		return Answer::where('user_id', $this->user->id)
			->where('project_id', $projectId)
			->where('answered', false)
			->get()
			->count();
	}
	
	// Get all answers for this user on this project.
	public function getMultiple($args) {
		$validator = Validator::make($args, [
			'project_id' => 'required|exists:projects,id',
			]);
		if ($validator->fails()) {
			return Status::fromValidator($validator);
		}

		$memberStatus = $this->memberService->checkPermission($args['project_id'], 'r', $this->user);
		if (!$memberStatus->isOK()) return Status::fromStatus($memberStatus);
		$answers = Answer::where('project_id', $args['project_id'])->where('user_id', $this->user->id);

		return Status::fromResult($answers->get());
	}
}