<?php
namespace App\Services;

use Auth;
use App\Models\Connection;
use App\Models\Project;
use App\Models\Request;
use App\Models\User;
use App\Models\Score;
use App\Services\RealtimeService;

class ScoreService {
	public function __construct(RealtimeService $realtimeService) {
		$this->user = Auth::user();
		$this->realtimeService = $realtimeService;
	}

	// Returns false if this would leave user with a negative score.
	private function applyScoreChange($scoreChange, $projectId) {
		$score = Score::firstOrCreate([
				'user_id' => $this->user->id,
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

		// Get connection count of recipient.
		$numConnections = Connection::where('initiator_id', $recipient->id)
			->orWhere('recipient_id', $recipient->id)
			->count();

		if ($type == 'connection') {
			$scoreChange = -1 * $numConnections;
			if ($intermediary != null) $scoreChange += 2;
		} else if ($type == 'answer') {
			$scoreChange = -5;
		}

		return $this->applyScoreChange($scoreChange, $request->project_id);
	}

	public function applyUpdateRequestScore(Request $request) {
		$recipient = User::find($request->recipient_id);
		$intermediary = ($request->intermediary_id === null) ? null : User::find($request->intermediary_id);
		$type = $request->type;
		$state = $request->state;

		// Get connection count of recipient.
		$numConnections = Connection::where('initiator_id', $recipient->id)
			->orWhere('recipient_id', $recipient->id)
			->count();

		$scoreChange = 0;
		
		if ($state == 'accepted') {
			$scoreChange = 2;
			if ($intermediary != null) $scoreChange = 5;
		} else if ($type == 'rejected') {
			$scoreChange = 0;
			if ($intermediary != null) $scoreChange = -2;
		}

		return $this->applyScoreChange($scoreChange, $request->project_id);
	}

	public function get($projectId) {
		$score = Score::firstOrCreate([
				'user_id' => $this->user->id,
				'project_id' => $projectId]);
		return $score->score;
	}
}