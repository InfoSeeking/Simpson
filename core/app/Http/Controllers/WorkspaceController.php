<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;

use Auth;
use Session;
use Redirect;
use Validator;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\User;

use App\Services\AnswerService;
use App\Services\ProjectService;
use App\Services\MembershipService;
use App\Services\RequestService;
use App\Services\ConnectionService;
use App\Services\ScoreService;
use App\Services\QuestionService;
use App\Utilities\Status;

class WorkspaceController extends Controller
{
    public function __construct(
        ProjectService $projectService,
        MembershipService $memberService,
        RequestService $requestService,
        ConnectionService $connectionService,
        AnswerService $answerService,
        ScoreService $scoreService,
        QuestionService $questionService) {
        $this->projectService = $projectService;
        $this->memberService = $memberService;
        $this->requestService = $requestService;
        $this->connectionService = $connectionService;
        $this->answerService = $answerService;
        $this->scoreService = $scoreService;
        $this->questionService = $questionService;
    }

    public function showProjectCreate() {
        return view('workspace.projects.create', [
            'user' => Auth::user()
            ]);
    }

    public function showProjects() {

        $projects = $this->projectService->getMyProjects();
        return view('workspace.projects.index', [
            'projects' => $projects,
            'user' => Auth::user(),
            'type' => 'mine'
            ]);
    }

    public function showShared() {
        $projectsWithMemberships = $this->projectService->getSharedProjects();
        return view('workspace.projects.index', [
            'projects' => $projectsWithMemberships,
            'user' => Auth::user(),
            'type' => 'shared'
            ]);
    }

    public function viewProjectSettings(Request $req, $projectId) {
        $permissionStatus = $this->memberService->checkPermission($projectId, 'o', Auth::user());
        if (!$permissionStatus->isOK()) {
            return $permissionStatus->asRedirect('workspace');
        }

        $projectStatus = $this->projectService->get($projectId);
        if (!$projectStatus->isOK()) {
            return $projectStatus->asRedirect('workspace');
        }

        $sharedUsersStatus = $this->projectService->getSharedUsers($projectId);
        if (!$sharedUsersStatus->isOK()) return $sharedUsersStatus->asRedirect('workspace');

        return view('workspace.projects.settings', [
            'project' => $projectStatus->getResult(),
            'sharedUsers' => $sharedUsersStatus->getResult(),
            'user' => Auth::user(),
            ]);
    }

    public function viewProject(Request $req, $projectId) {
        $permissionStatus = $this->memberService->checkPermission($projectId, 'w', Auth::user());
        if (!$permissionStatus->isOK()) return $permissionStatus->asRedirect('workspace');

        $projectStatus = $this->projectService->get($projectId);
        if (!$projectStatus->isOK()) return $projectStatus->asRedirect('workspace');

        $sharedUsersStatus = $this->projectService->getSharedUsers($projectId);
        if (!$sharedUsersStatus->isOK()) return $sharedUsersStatus->asRedirect('workspace');

        $requestsStatus = $this->requestService->getMultiple(['project_id' => $projectId]);
        if (!$requestsStatus->isOK()) return $requestsStatus->asRedirect('workspace');

        $connectionsStatus = $this->connectionService->getMultiple(['project_id' => $projectId]);
        if (!$connectionsStatus->isOK()) return $connectionsStatus->asRedirect('workspace');

        $answersStatus = $this->answerService->getMultiple(['project_id' => $projectId]);
        if (!$answersStatus->isOK()) return $answersStatus->asRedirect('workspace');

        $questionsStatus = $this->questionService->getMultiple(['project_id' => $projectId]);
        if (!$questionsStatus->isOK()) return $questionsStatus->asRedirect('workspace');

        $timeLeft = $this->projectService->getTimeLeft($projectId);
        $numUnanswered = $this->answerService->getNumUnanswered($projectId);
        $project = $projectStatus->getResult();

        if ($timeLeft == 0 || $numUnanswered == 0 || $project->state == 'finished') return redirect('/workspace/end');

        if (!$project->active) return redirect('/workspace/instructions');

        return view('workspace.projects.view', [
            'project' => $project,
            'permission' => $permissionStatus->getResult(),
            'user' => Auth::user(),
            'userScore' => $this->scoreService->get($projectId),
            'sharedUsers' => $sharedUsersStatus->getResult(),
            'requests' => $requestsStatus->getResult(),
            'connections' => $connectionsStatus->getResult(),
            'answers' => $answersStatus->getResult(),
            'timeLeft' => $timeLeft,
            'place' => $this->scoreService->getPlace($projectId),
            'questions' => $questionsStatus->getResult()
            ]);
    }

    public function createProject(Request $req) {
        $private = $req->input('visibility') == 'private';
        $args = array_merge($req->all(), ['private' => $private]);
        $projectStatus = $this->projectService->create($args);
        $project = $projectStatus->getResult();
        return $projectStatus->asRedirect(
            'workspace/projects/' . $project->id . '/settings', 
            ['Project ' . $project->name . ' created']);
    }

    public function updateProject(){}
    
    public function deleteProject(Request $req, $projectId) {
        $status = $this->projectService->delete(['id' => $projectId]);
        return $status->asRedirect('workspace');
    }

    public function showUserSettings(Request $req) {
        return view('workspace.usersettings', [
            'user' => Auth::user()
            ]);
    }

    public function updateUserSettings(Request $req) {
        $validator = Validator::make($req->all(), [
            'email' => 'required|email',
            'name' => 'required|string',
            'avatar' => 'sometimes|image'
            ]);

        if ($validator->fails()) {
            return Status::fromValidator($validator)->asRedirect('workspace/user/settings');
        }

        $user = Auth::user();
        // Make sure the email is not taken by any other user.
        $others = User::where('email', $req->input('email'))->where('id', '!=', $user->id)->get();
        if (count($others) > 0) {
            return Status::fromError('This email is taken by another user.')
                ->asRedirect('workspace/user/settings');
        }

        $user->email = $req->input('email');
        $user->name = $req->input('name');

        if ($req->hasFile('avatar')) {
            $avatar = $req->file('avatar');

            // Guess the extension, since we should not trust the user provided extension.
            $extension = strtolower($avatar->guessExtension());
            if (!$extension) {
                return Status::fromError('Could not process uploaded image.')
                    ->asRedirect('workspace/user/settings');
            }

            // Create gd image resource from uploaded image.
            $imageIn = null;
            switch ($extension) {
                case 'png':
                    $imageIn = imagecreatefrompng($avatar->getRealPath());
                    break;
                case 'jpg':
                case 'jpeg':
                    $imageIn = imagecreatefromjpeg($avatar->getRealPath());
                    break;
                case 'gif':
                    $imageIn = imagecreatefromgif($avatar->getRealPath());
                    break;
                default:
                    return Status::fromError('Image type unrecognized.')
                        ->asRedirect('workspace/user/settings');
            }

            // Resize to 200x200.
            $outSize = 200;
            list($origWidth, $origHeight) = getimagesize($avatar->getRealPath());

            $imageOut = imagecreatetruecolor($outSize, $outSize);
            imagecopyresampled(
                $imageOut, $imageIn, 0, 0, 0, 0, $outSize, $outSize, $origWidth, $origHeight);

            // Save as a png with highest quality.
            $pathOut = 'images/users/' . $user->id . '.png';
            imagepng($imageOut, $pathOut, 0);

            $user->avatar = true;
        }

        $user->save();
        return Status::OK()->asRedirect('workspace/user/settings');
    }

    public function viewPanel() {
        $projects = $this->projectService->getMyProjects();
        $sharedProjects = $this->projectService->getSharedProjects();
        return view('workspace.panel', [
            'user' => Auth::user(),
            'projectCount' => count($projects),
            'sharedProjectCount' => count($sharedProjects)
            ]);
    }

    public function showInstructions() {
        $currentProject = $this->projectService->getCurrentStudyProject();
        $nextProject = $this->projectService->getNextStudyProject();
        if (!$currentProject && !$nextProject) {
            return redirect('/workspace/end');
        }
        return view('workspace.instructions', [
            'user' => Auth::user(),
            'project' => $currentProject ? $currentProject : $nextProject,
            ]);
    }

    public function showEnd() {

        $currentProject = $this->projectService->getCurrentStudyProject();
        $nextProject = $this->projectService->getNextStudyProject();

        // Check if the user should go to the current project (i.e. the transition has occured).
        if ($currentProject) {
            $timeLeft = $this->projectService->getTimeLeft($currentProject->id);
            $numUnanswered = $this->answerService->getNumUnanswered($currentProject->id);
            if ($timeLeft == 0 || $numUnanswered == 0) {
                // User finished this scenario, but the scenario is still active.
            } else {
                // The transition has been made,
                $nextProject = $currentProject;
            }
        }

        return view('workspace.end', [
            'user' => Auth::user(),
            'project' => $nextProject
            ]);   
    }

}
