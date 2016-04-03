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

class RequestService {
	public function __construct(
		MembershipService $memberService,
		RealtimeService $realtimeService
		) {
		$this->memberService = $memberService;
		$this->realtimeService = $realtimeService;
		$this->user = Auth::user();
	}

	public function getMultiple($args) {}
	
	public function answerRequest(Request $request) {
		if (!$request->type == 'answer') throw new \InvalidArgumentException();
		$answer = Answer::where('user_id', $request->recipient_id)
			->where('project_id', $request->project_id)
			->where('answer_id', $request->answer_id)
			->first();
		if (!is_null($answer)) {
			// Save this answer for the user.
			$copy = $answer->replicate();
			$copy->user_id = $this->user->id;
			$copy->save();
			$this->realtimeService
				->withModel($copy)
				->onProject($request->project_id)
				->emit('create');
			return true;
		}
		return false;
	}
}