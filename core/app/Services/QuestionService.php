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
use App\Services\ProjectService;
use App\Utilities\Status;
use App\Utilities\StatusCodes;
use Illuminate\Support\Facades\Log;

class QuestionService {
    public function __construct(
        MembershipService $memberService,
        RealtimeService $realtimeService,
        ConnectionService $connectionService,
        LogService $logService,
        ProjectService $projectService
        ) {
        $this->projectService = $projectService;
        $this->memberService = $memberService;
        $this->realtimeService = $realtimeService;
        $this->connectionService = $connectionService;
        $this->logService = $logService;
        $this->user = Auth::user();
    }
    
    public function getTopics($userId, $projectId) {
        // Get all existing answers.
        $answers = Answer::where('user_id', $userId)->where('project_id', $projectId)->where('answered', 1)->get();
        $topics = Question::whereIn('id', $answers->pluck('question_id'))->get()->pluck('topic')->toArray();
        return array_unique($topics);
    }

    public function getAllUserTopics($projectId) {
        $users = $this->projectService->getSharedUsers($projectId)->getResult()->pluck('user');
        $userMap = [];
        foreach ($users as $user) {
            $uid = $user['id'];
            $userMap[$uid] = $this->getTopics($uid, $projectId);
        }
        return $userMap;
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
        // Get other user's answers for this question.
        $otherUserAnswers = Answer::where('question_id', $request->question_id)->where('answered', 1);
        if ($request->type == 'answer') {
            $otherUserAnswers->where('user_id', $request->recipient_id);
        } else if ($request->type == 'answer_all') {
            // Get id list of other users who we are connected to directly.
            $otherIds = $this->connectionService->getOtherIds();
            $otherUserAnswers->whereIn('user_id', $otherIds);
        } else {
            throw new \IllegalArgumentException('Invalid request type');
        }
        Log::info("After user id, otherUserAnswers.count=" . $otherUserAnswers->count());

        
        // Get the answers that this user needs for this question.
        $answersNeeded = Answer::where('question_id', $request->question_id)
            ->where('user_id', $this->user->id)
            ->where('answered', 0);
        Log::info("answersNeeded=" . $answersNeeded->count());
        if ($answersNeeded->count() == 0) return false;

        $answered = $answersNeeded->whereIn('position', $otherUserAnswers->get()->pluck('position'))->first();
        if ($answered != null) {
            $answered->answered = 1;
            $answered->save();   
        }

        $this->logService
                ->withUserId($this->user->id)
                ->withProjectId($request->project_id)
                ->withRequestId($request->id)
                ->withAnswerId($answered == null ? 0 : $answered->id)
                ->withKey($answered == null ? 'answer_unfulfilled' : 'answer_get')
                ->withValue($request->question_id)
                ->save();

        $numUnanswered = Answer::where('user_id', $this->user->id)
            ->where('project_id', $request->project_id)
            ->where('answered', 0)
            ->get()
            ->count();

        if ($numUnanswered == 0) {
            // User finished.
            $this->logService
                ->withUserId($this->user->id)
                ->withProjectId($request->project_id)
                ->withRequestId($request->id)
                ->withKey('finished')
                ->save();
        }
        
        if ($answered != null) {
            $this->realtimeService
                ->withModel($answered)
                ->onProject($request->project_id)
                ->emit('update');

            $newTopics = $this->getTopics($this->user->id, $request->project_id);
            $this->realtimeService
                ->withRawData($newTopics, 'topics')
                ->onProject($request->project_id)
                ->emit('update');
        }
        return $answered != null;
    }
}