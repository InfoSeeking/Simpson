<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;

class StudyDeactivate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'study:deactivate {--name=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Makes project temporarily inaccessible to users';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function usage() {
        printf('Usage: php artisan study:deactivate --name="StudyName"');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $projectName = $this->option('name');
        $project = Project::where('title', $projectName)->first();
        if (is_null($project)) return printf("Project %s not found\n", $projectName);
        if (!$project->active) return printf("Project %s is already deactivated\n", $projectName);
        // Update all scenarios.
        Project::where('title', $projectName)->update(['active' => false]);
        printf("Project %s is now deactivated\n", $projectName);
    }
}
