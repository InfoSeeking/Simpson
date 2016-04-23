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

	// At first, let's hard code.
	// private function evalFormula($formula, $args) {
	// 	extract($args);
	// 	$value = 0;
	// 	eval("$value = " . $formula . ";");
	// 	return $value;
	// }

	private function applyScoreChange($scoreChange, $projectId) {
		$score = Score::firstOrCreate([
				'user_id' => $this->user->id,
				'project_id' => $projectId]);
		$score->score += $scoreChange;
		$score->save();

		$this->realtimeService
			->withModel($score)
			->onProject($projectId)
			->emit('update');
		return $score->score;
	}

	// This is called when a request state changes (including when it opens).
	public function applyRequestScore(Request $request) {
		$recipient = User::find($request->recipient_id);
		$intermediary = ($request->intermediary_id === null) ? null : User::find($request->intermediary_id);
		$type = $request->type;
		$state = $request->state;

		// Get connection count of recipient.
		$numConnections = Connection::where('initiator_id', $recipient->id)
			->orWhere('recipient_id', $recipient->id)
			->count();

		$scoreChange = 0;

		switch ($state) {
			case 'open':
				if ($type == 'connection') {
					$scoreChange = -1 * $numConnections;
					if ($intermediary != null) $scoreChange += 2;
				} else if ($type == 'answer') {
					$scoreChange = -5;
				}
				break;
			case 'accepted':
				$scoreChange = 2;
				if ($intermediary != null) $scoreChange = 5;
				break;
			case 'rejected':
				$scoreChange = 0;
				if ($intermediary != null) $scoreChange = -2;
				break;
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