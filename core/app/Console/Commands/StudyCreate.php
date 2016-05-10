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
use Faker;

class StudyCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'study:create {--infile=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a study from an input file';

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
        printf("Usage: php artisan study:create --infile myfile.json\n");
        printf("myfile.json must be a JSON file with users and connections specified\n");
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Read JSON from file.
        $filename = $this->option("infile");
        if (!$filename) return $this->usage();
        $contents = @file_get_contents($filename);
        if (!$contents) return printf("Cannot read file %s\n", $filename);
        $json = json_decode($contents, true);
        if (!$json) return printf("Input file not valid JSON\n");

        // Ensure project with same name does not exist.
        $project = Project::where('title', $json['projectName'])->first();
        if (!is_null($project)) 
            return printf("Project with name \"%s\" already exists\n", $json['projectName']);

        // Create project.
        $project = new Project(['title' => $json['projectName']]);
        $project->creator_id = 0;
        $project->save();
        
        $userMap = [];
        // Create users with membership to that project.
        foreach ($json['users'] as &$userJSON) {
            $faker = Faker\Factory::create();
            $id = $userJSON['identifier'];
            $userJSON['email'] = $id . '-' . $project->title . '@simpson-study.org';
            if ($userJSON['name'] == "?") $userJSON['name'] = $faker->name;
            if ($userJSON['password'] == "?") $userJSON['password'] = str_random(6);

            $user = User::create([
                'name' => $userJSON['name'],
                'password' => bcrypt($userJSON['password']),
                'email' => $userJSON['email']
                ]);

            $membership = Membership::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'level' => 'w'
                ]);

            $score = Score::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'score' => $json['initialScore']
                ]);

            // Add to temporary map.
            $userMap[$id] = $user;
        }

        // Create connections.
        foreach ($json['connections'] as $connectionJSON) {
            $userA = $userMap[$connectionJSON[0]];
            $userB = $userMap[$connectionJSON[1]];

            Connection::create([
                'initiator_id' => $userA->id,
                'recipient_id' => $userB->id,
                'project_id' => $project->id
                ]);
        }

        // Create answers.
        $numQuestions = $json['numberQuestions'];
        $answersPerUser = $json['answersPerUser'];

        if ($answersPerUser * count($userMap) < $numQuestions)
            return printf("Not enough answers per user to give answers to every question\n");

        $userArray = array_values($userMap);
        $qIds = [];
        for ($i = 1; $i <= $numQuestions; $i++) {
            foreach ($userArray as $user) {
                Answer::create([
                    'name' => "" . $i,
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'answered' => false
                    ]);
            }
            array_push($qIds, "" . $i);
        }

        shuffle($qIds);
        for ($i = 0; $i < count($qIds); $i++) {
            $user = $userArray[$i % count($userArray)];
            $answer = Answer::where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->where('name', $qIds[$i])
                ->first();
            $answer->answered = true;
            $answer->save();
        }

        // Now assign remaining allotted answers randomly per user.

        foreach ($userArray as $user) {
            // Get remaining unanswered questions.
            $unanswered = Answer::where('project_id', $project->id)->where('user_id', $user->id)->where('answered', false)->get();
            $numAnswered = $numQuestions - $unanswered->count();
            $numLeft = $answersPerUser - $numAnswered;
            $unanswered = $unanswered->shuffle();
            for ($i = 0; $i < $numLeft; $i++) {
                $ans = $unanswered->offsetGet($i);
                $ans->answered = true;
                $ans->save();
            }
        }

        $outfile = $filename . ".out";
        file_put_contents($outfile, json_encode($json, JSON_PRETTY_PRINT));
        printf("Information written to %s\n", $outfile);

    }
}