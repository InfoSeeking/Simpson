<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Project;
use App\Models\Score;
use App\Models\Membership;

class StudyAdvance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'study:advance {--name=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Advances the study to the next scenario';

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
        printf("Usage: php artisan study:advance --name=\"Project Name\"\n");
        printf("--name corresponds to the projectName given in the input file\n");
    }

    private function checkContinue() {
        $answer = $this->ask("Are you sure you wish to continue (y/n)?");
        if ($answer != "y" && $answer != "yes" && $answer != "Y") exit("Exiting\n");
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $studyName = $this->option('name');
        if (!$studyName) return $this->usage();
        $projects = Project::where('title', $studyName)->get();
        if ($projects->count() == 0) {
            exit(sprintf("No project with name \"%s\" found\n", $studyName));
        }

        // Get the project which is in state in_queue
        $currentProject = Project::where('title', $studyName)->where('state', 'started')->first();
        $nextProject = Project::where('title', $studyName)->where('state', 'in_queue')->first();
        $followingProject = null;

        if (!$currentProject && !$nextProject) {
            exit("Project is already finished.\n");
        }

        if ($currentProject) {
            printf("This will end scenario %s.\n", $currentProject->scenario_name);
        } else if ($nextProject) {
            printf("This will start scenario %s.\n", $nextProject->scenario_name);
        }
        $this->checkContinue();
        


        if ($nextProject && $nextProject->nextProject) {
            $followingProject = Project::find($nextProject->nextProject);
            if (!$followingProject) {
                exit(sprintf("Scenario %s refers to non existing next scenario.\n", $nextProject->scenario_name));
            }
        }

        if ($currentProject) {
            printf("Transitioning scenario %s from \"started\" to \"finished\".\n", $currentProject->scenario_name);
            // Check if the timers have expired yet.
            $membership = Membership::where('project_id', $currentProject->id)->first();
            $timeStarted = $membership->time_started;
            $endTime = $timeStarted + $currentProject->timeout;
            $timeLeft = ($endTime - time());

            if ($timeLeft > 0) {
                printf("There is still %s seconds left in this task.\n", $timeLeft);
                printf("Users who are still performing in the task will not be forced\n");
                printf("off of the page until the timer expires or they refresh.\n");
                $this->checkContinue();
            }
            
            $currentProject->state = 'finished';
            $currentProject->save();
        } else if ($nextProject) {
            printf("Transitioning scenario %s from \"in_queue\" to \"started\".\n", $nextProject->scenario_name);
            $nextProject->state = 'started';
            $nextProject->save();

            // Copy over scores if necessary.
            if ($nextProject->prevProject) {
                $previousProject = Project::find($nextProject->prevProject);
                $currentScores = Score::where('project_id', $previousProject->id)->get();
                $nextScores = Score::where('project_id', $nextProject->id)->get();
                foreach ($currentScores as $currentScore) {
                    Score::where('project_id', $nextProject->id)
                        ->where('user_id', $currentScore->user_id)
                        ->update(['score' => $currentScore->score]);
                }
                printf("  Scores copied from previos scenario.\n");
            }

            // Start timers.
            Membership::where('project_id', $nextProject->id)->update(['time_started' => time()]);
            printf("  Timers started.\n");

            // Enqueue the next project if necessary.
            if ($followingProject) {
                printf("Transitioning scenario %s from \"unstarted\" to \"in_queue\".\n", $followingProject->scenario_name);
                $followingProject->state = 'in_queue';
                $followingProject->save();
            }
        }
    }
}
