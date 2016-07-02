<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Project;

class StudyInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'study:info {--name=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get info on a specific study';

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
        printf("Usage: php artisan study:info --name=\"Project Name\"\n");
        printf("--name corresponds to the projectName given in the input file\n");
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
        } else {
            printf("Listing scenarios for project \"%s\"\n", $studyName);
            foreach ($projects as $project) {
                $nextProjectName = "<none>";
                if ($project->nextProject) {
                    $nextProjectName = Project::find($project->nextProject)->scenario_name;
                }
                printf("Name: %s, State: %s, Next Scenario: %s\n", $project->scenario_name, $project->state, $nextProjectName);
            }
            printf("\nThe states for the scenarios are as follows:\n");
            printf("  unstarted: The scenario has not been started and is not the next scenario.\n");
            printf("  in_queue: The scenario is the next to be started.\n");
            printf("  started: The scenario is currently active. A max of one scenario may be in this state.\n");
            printf("  finished: The scenario is has already run.\n");
            printf("The transitions go from unstarted -> in_queue -> started -> finished on advancing.\n");
        }
    }
}
