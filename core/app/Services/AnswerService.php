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
	
	public function handle(Request $request) {
		// $answer = Answer::where('project_id', $request->project_id)
		// 	->where('name', $request->answer_id)
		// 	->where('answered', true);

		// if ($request->type == 'answer') {
		// 	$answer = $answer->where('user_id', $request->recipient_id);
		// } else if ($request->type == 'answer_all') {
		// 	// Get id list of other users who we are connected to directly.
		// 	$otherIds = $this->connectionService->getOtherIds();
		// 	$answer = $answer->whereIn('user_id', $otherIds);
		// } else {
		// 	throw new \IllegalArgumentException('Invalid request type');
		// }
			
		// $hasAnswer = !is_null($answer->first());
		// if ($hasAnswer) {
		// 	// Check if this user already has this answer.
		// 	$existingAnswer = Answer::where('user_id', $this->user->id)
		// 		->where('project_id', $request->project_id)
		// 		->where('name', $request->answer_id)
		// 		->first();
		// 	if ($existingAnswer->answered) {
		// 		return Status::fromError('User already has this answer');
		// 	}

		// 	// Save this answer for the user.
		// 	$existingAnswer->answered = true;
		// 	$existingAnswer->save();

		// 	$numUnanswered = Answer::where('user_id', $this->user->id)
		// 		->where('project_id', $request->project_id)
		// 		->where('answered', false)
		// 		->get()
		// 		->count();

		// 	$this->logService
		// 		->withUserId($this->user->id)
		// 		->withProjectId($request->project_id)
		// 		->withRequestId($request->id)
		// 		->withAnswerId($existingAnswer->id)
		// 		->withKey('answer_get')
		// 		->withValue($request->answer_id)
		// 		->save();


		// 	if ($numUnanswered == 0) {
		// 		// User finished.
		// 		$this->logService
		// 			->withUserId($this->user->id)
		// 			->withProjectId($request->project_id)
		// 			->withRequestId($request->id)
		// 			->withKey('finished')
		// 			->save();
		// 	}
			
		// 	$this->realtimeService
		// 		->withModel($existingAnswer)
		// 		->onProject($request->project_id)
		// 		->emit('update');
		// 	return true;
		//}
		return false;
	}
}