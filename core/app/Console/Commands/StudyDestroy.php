<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Connection;
use App\Models\Answer;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Request;
use App\Services\ConnectionService;
use App\Services\RequestService;
use App\Models\User;
use App\Models\Score;

class StudyDestroy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'study:destroy {--name=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Destroys a study with all of its data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function usage() {
        printf("Usage: php artisan study:destroy --name=\"Project Name\"\n");
        printf("--name corresponds to the projectName given in the input file\n");
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        printf("This will destroy all users and data associated with this project\n");
        $answer = $this->ask("Are you sure you wish to continue (y/n)?");
        if ($answer != "y" && $answer != "yes" && $answer != "Y") return printf("Exiting\n");

        $studyName = $this->option('name');
        if (!$studyName) return $this->usage();
        $projects = Project::where('title', $studyName)->get();
        if ($projects->count() == 0) {
            exit(sprintf("No project with name \"%s\" found\n", $studyName));
        }
        foreach ($projects as $project) {
            // Delete all users of this.
            $memberships = Membership::where('project_id', $project->id)->get();
            $users = User::whereIn('id', $memberships->pluck('user_id'))->get();

            Connection::whereIn('recipient_id', $users->pluck('id'))->delete();
            Connection::whereIn('initiator_id', $users->pluck('id'))->delete();
            Request::whereIn('recipient_id', $users->pluck('id'))->delete();
            Request::whereIn('initiator_id', $users->pluck('id'))->delete();
            Membership::whereIn('user_id', $users->pluck('id'))->delete();
            Answer::whereIn('user_id', $users->pluck('id'))->delete();
            Score::whereIn('user_id', $users->pluck('id'))->delete();
            User::whereIn('id', $users->pluck('id'))->delete();
            Membership::whereIn('id', $memberships->pluck('id'));

            $project->delete();
        }
        printf("Project and data deleted\n");
    }
}
