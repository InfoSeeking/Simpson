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
use App\Models\Question;
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

    private function createAnswers($project, $userMap, $json) {
        $showTopics = array_key_exists('showTopics', $json) ? $json['showTopics'] : false;

        // Create questions in database.
        $questions = $json['questions'];
        $numTotalAnswers = 0;

        foreach ($questions as $index => $question) {
            $questions[$index]['id'] = Question::create([
                'text' => $question['question'],
                'project_id' => $project->id,
                'num_answers' => count($question['answers']),
                'topic' => $showTopics ? $question['topic'] : null
            ])->id;
            $numTotalAnswers += count($question['answers']);
        }

        $answersPerUser = $json['answersPerUser'];
        if ($answersPerUser * count($userMap) < $numTotalAnswers)
            exit("Not enough answers per user to give answers to every question\n");

        // Create answers for every user, all initially unanswered.
        $userArray = array_values($userMap);
        $answerArray = [];
        foreach ($questions as $question) {
            foreach ($question["answers"] as $position => $answerText) {
                array_push($answerArray, ['position' => $position, 'question_id' => $question['id']]);
                foreach ($userArray as $user) {
                    Answer::create([
                        'name' => $answerText,
                        'project_id' => $project->id,
                        'user_id' => $user->id,
                        'answered' => 0,
                        'position' => $position,
                        'question_id' => $question['id']
                        ]);
                }
            }
        }

        shuffle($answerArray);
        $i = 0;
        foreach($answerArray as $answerKey) {
            $user = $userArray[$i % count($userArray)];
            $answer = Answer::where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->where('position', $answerKey['position'])
                ->where('question_id', $answerKey['question_id'])
                ->first();
            $answer->answered = 1;
            $answer->save();
            $i++;
        }


        // Now assign remaining allotted answers randomly per user.
        foreach ($userArray as $user) {
            // Get remaining unanswered questions.
            $unanswered = Answer::where('project_id', $project->id)->where('user_id', $user->id)->where('answered', 0)->get();
            $numAnswered = $numTotalAnswers - $unanswered->count();
            $numLeft = $answersPerUser - $numAnswered;
            $unanswered = $unanswered->shuffle();
            for ($i = 0; $i < $numLeft; $i++) {
                $ans = $unanswered->offsetGet($i);
                $ans->answered = 1;
                $ans->save();
            }
        }
    }

    private function createScenarios($projectName, $initialScore, $userMap, $scenarioJson) {
        $prevScenario = null;
        foreach($scenarioJson as $json) {
            // Create project.
            $project = new Project(['title' => $projectName]);
            $project->creator_id = 0;
            if (array_key_exists('scenarioName', $json)) $project->scenario_name = $json['scenarioName'];
            if (array_key_exists('timeout', $json)) $project->timeout = $json['timeout'];
            if (array_key_exists('description', $json)) $project->description = $json['description'];
            if (array_key_exists('showTopics', $json)) $project->show_topics = $json['showTopics'];

            if ($prevScenario == null) {
                // This is the first project.
                $project->state = "in_queue";
            } else {
                $project->state = "unstarted";
                $project->prevProject = $prevScenario->id;
            }

            $project->save();

            if ($prevScenario != null) {
                $prevScenario->nextProject = $project->id;
                $prevScenario->save();
            }

            foreach ($userMap as $key => $user) {
                // Make all users a member of this project.
                $membership = Membership::create([
                        'user_id' => $user->id,
                        'project_id' => $project->id,
                        'level' => 'w'
                        ]);
                // Create a score for each user. Scores will be propagated through the scenarios during advancement.
                $score = Score::create([
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'score' => $initialScore
                    ]);
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

            $this->createAnswers($project, $userMap, $json);
            $prevScenario = $project;
        }
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
        if (!$filename) exit($this->usage());
        $contents = @file_get_contents($filename);
        if (!$contents) exit(sprintf("Cannot read file %s\n", $filename));
        $json = json_decode($contents, true);
        if (!$json) exit("Input file not valid JSON\n");

        // Ensure project with same name does not exist already.
        $project = Project::where('title', $json['projectName'])->first();
        if (!is_null($project)) 
            exit(sprintf("Project with name \"%s\" already exists\n", $json['projectName']));

        if (stristr($json['projectName'], ' ')) exit("Project name cannot have spaces\n");

        if (!array_key_exists('scenarios', $json)) exit("Scenarios array not found.\n");

        $shuffle = false;
        if (array_key_exists('shuffleScenarioOrder', $json)) $shuffle = $json['shuffleScenarioOrder'];
        if ($shuffle) shuffle($json['scenarios']);

        $userMap = [];
        // Create users.
        foreach ($json['users'] as &$userJSON) {
            $faker = Faker\Factory::create();
            $id = $userJSON['identifier'];
            $userJSON['email'] = $id . '-' . $json['projectName'] . '@simpson-study.org';
            if ($userJSON['name'] == "?") $userJSON['name'] = $faker->name;
            if ($userJSON['password'] == "?") $userJSON['password'] = str_random(6);

            $user = User::create([
                'name' => $userJSON['name'],
                'password' => bcrypt($userJSON['password']),
                'email' => $userJSON['email']
                ]);

            // Add to temporary map.
            $userMap[$id] = $user;
        }

        $this->createScenarios($json['projectName'], $json['initialScore'], $userMap, $json['scenarios']);

        $outfile = $filename . ".out";
        file_put_contents($outfile, json_encode($json, JSON_PRETTY_PRINT));
        printf("Information written to %s\n", $outfile);

    }
}
