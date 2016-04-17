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
use App\Utilities\Status;
use App\Utilities\StatusCodes;

class AnswerService {
	public function __construct(
		MembershipService $memberService,
		RealtimeService $realtimeService
		) {
		$this->memberService = $memberService;
		$this->realtimeService = $realtimeService;
		$this->user = Auth::user();
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
	
	public function handle(Request $request) {
		if (!$request->type == 'answer') throw new \InvalidArgumentException();
		$answer = Answer::where('user_id', $request->recipient_id)
			->where('project_id', $request->project_id)
			->where('name', $request->answer_id) // request->answer_id <==> answer->name...
			->first();
		if ($answer->answered) {
			// Check if this user already has this answer.
			$existingAnswer = Answer::where('user_id', $this->user->id)
				->where('project_id', $request->project_id)
				->where('name', $request->answer_id)
				->first();
			if ($existingAnswer->answered) {
				return Status::fromError('User already has this answer');
			}

			// Save this answer for the user.
			$existingAnswer->answered = true;
			$existingAnswer->save();
			
			$this->realtimeService
				->withModel($existingAnswer)
				->onProject($request->project_id)
				->emit('update');
			return true;
		}
		return false;
	}
}