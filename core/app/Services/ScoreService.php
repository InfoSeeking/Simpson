<?php
namespace App\Services;

use Auth;
use App\Models\Answer;
use App\Models\Connection;
use App\Models\Project;
use App\Models\Request;
use App\Models\User;
use App\Models\Score;
use App\Services\ProjectService;
use App\Services\RealtimeService;
use App\Services\ConnectionService;
use App\Services\LogService;

class ScoreService {
	public function __construct(RealtimeService $realtimeService,
		ConnectionService $connectionService,
		LogService $logService,
		ProjectService $projectService) {
		$this->user = Auth::user();
		$this->realtimeService = $realtimeService;
		$this->connectionService = $connectionService;
		$this->logService = $logService;
		$this->projectService = $projectService;
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

		$this->logService
			->withUserId($this->user->id)
			->withProjectId($projectId)
			->withKey('score_change')
			->withValue($scoreChange)
			->save();

		$this->realtimeService
			->withModel($score)
			->onProject($projectId)
			->emit('update');
		return $score->score;
	}

	public function getPlace($projectId) {
		// Get cumulative scores for all finished scenarios for all users.
		$projectName = Project::find($projectId)->title;
		$allProjects = Project::where('title', $projectName)->get();
		$userScores = [];

		foreach($allProjects as $project) {
			$sharedUsersStatus = $this->projectService->getSharedUsers($project->id);
			if (!$sharedUsersStatus->isOK()) return 0;
			$projectMemberships = $sharedUsersStatus->getResult();
			foreach ($projectMemberships as $membership) {
				$user = $membership->user;

				$score = Score::where('project_id', $project->id)
					->where('user_id', $user->id)
					->first()
					->score;

				$numConnections = Connection::where('initiator_id', $user->id)
					->orWhere('recipient_id', $user->id)
					->count();

				$numAnswers = Answer::where('user_id', $user->id)
					->where('project_id', $project->id)
					->where('answered', true)
					->get()
					->count();
				if (!array_key_exists($user->id, $userScores)) $userScores[$user->id] = 0;
				$userScores[$user->id] += $numConnections + $numAnswers + $score;
			}
		}
		
		arsort($userScores);
		$place = 1;
		foreach ($userScores as $userId => $userScore) {
			if ($this->user->id == $userId) break;
			$place++;
		}
		return $place;
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