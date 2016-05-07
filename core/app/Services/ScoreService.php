<?php
namespace App\Services;

use Auth;
use App\Models\Connection;
use App\Models\Project;
use App\Models\Request;
use App\Models\User;
use App\Models\Score;
use App\Services\RealtimeService;
use App\Services\ConnectionService;

class ScoreService {
	public function __construct(RealtimeService $realtimeService,
		ConnectionService $connectionService) {
		$this->user = Auth::user();
		$this->realtimeService = $realtimeService;
		$this->connectionService = $connectionService;
	}

	// Returns false if this would leave user with a negative score.
	private function applyScoreChange($scoreChange, $projectId, $userId=null) {
		if(is_null($userId)) $userId = $this->user->id;
		$score = Score::firstOrCreate([
				'user_id' => $userId,
				'project_id' => $projectId]);
		$score->score += $scoreChange;
		if ($score->score < 0) return false;
		$score->save();

		$this->realtimeService
			->withModel($score)
			->onProject($projectId)
			->emit('update');
		return $score->score;
	}

	public function applyOpenRequestScore(Request $request) {
		$recipient = User::find($request->recipient_id);
		$intermediary = ($request->intermediary_id === null) ? null : User::find($request->intermediary_id);
		$type = $request->type;
		$state = $request->state;

		if ($type == 'connection') {
			// Get connection count of recipient.
			$numConnections = Connection::where('initiator_id', $recipient->id)
				->orWhere('recipient_id', $recipient->id)
				->count();
			$scoreChange = -1 * $numConnections - 2;
			if ($intermediary != null) $scoreChange += 4;
		} else if ($type == 'answer') {
			$scoreChange = -5;
		} else if ($type == 'answer_all') {
			$scoreChange = -5 * count($this->connectionService->getOtherIds()) + 5;
		}

		return $this->applyScoreChange($scoreChange, $request->project_id);
	}

	public function applyUpdateRequestScore(Request $request) {
		$initiator = User::find($request->initiator_id);
		$intermediary = ($request->intermediary_id === null) ? null : User::find($request->intermediary_id);
		$type = $request->type;
		$state = $request->state;

		$yourScoreChange = 0;
		$intermediaryScoreChange = 0;
		$theirScoreChange = 0;

		if ($state == 'accepted') {
			$yourScoreChange = 2;
			$theirScoreChange = 10;
			if ($intermediary != null) {
				$yourScoreChange = 5;
				$intermediaryScoreChange = 5;
			}
		} else if ($state == 'rejected') {
			$yourScoreChange = -1;
			$theirScoreChange = -10;
			if ($intermediary != null) {
				$yourScoreChange = -2;
			}
		}

		if (!is_null($intermediary)) {
			$outcome = $this->applyScoreChange(
				$intermediaryScoreChange, $request->project_id, $intermediary->id);
			if ($outcome === false) return false;
		}

		// You are the recipient of the request.
		$outcome = $this->applyScoreChange(
			$yourScoreChange, $request->project_id, $this->user->id);
		if ($outcome === false) return false;

		return $this->applyScoreChange($theirScoreChange, $request->project_id, $initiator->id);
	}

	public function get($projectId) {
		$score = Score::firstOrCreate([
				'user_id' => $this->user->id,
				'project_id' => $projectId]);
		return $score->score;
	}
}