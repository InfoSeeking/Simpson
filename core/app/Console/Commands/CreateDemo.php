<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Connection;
use App\Services\ConnectionService;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Request;
use App\Services\RequestService;
use App\Models\User;

class CreateDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up a demo with 20 users.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ConnectionService $connectionService,
        RequestService $requestService)
    {
        parent::__construct();
        $this->connectionService = $connectionService;
        $this->requestService = $requestService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $demoUser = User::where('email', 'simpson_demo@demo.demo')->first();

        $project = new Project();
        $project->title = 'SIMPSON Demo Project';
        $project->creator_id = $demoUser->id;
        $project->save();

        $membership = new Membership();
        $membership->user_id = $demoUser->id;
        $membership->project_id = $project->id;
        $membership->level = 'o';
        $membership->save();

        $users = [];

        // Create 20 other users for project.
        for ($i = 0; $i < 20; $i++) {
            $user = new User();
            $user->name = str_random(10);
            $user->email = 'email' . $i . '@demo-temp.demo';
            $user->password = bcrypt('test');
            $user->save();

            array_push($users, $user);

            $membership = new Membership();
            $membership->user_id = $user->id;
            $membership->project_id = $project->id;
            $membership->level = 'w';
            $membership->save();
        }

        shuffle($users);
        // Create a few connections to start and some requests to simpson demo.
        for ($i = 0; $i < 10; $i += 3) {
            $connection = new Connection([
                'initiator_id' => $users[$i]->id,
                'recipient_id' => $users[$i+1]->id,
                'project_id' => $project->id
                ]);
            $connection->save();

            $request = new Request([
                    'initiator_id' => $users[$i+9]->id,
                    'recipient_id' => $demoUser->id,
                    'type' => 'connection',
                    'project_id' => $project->id
                ]);
            $request->save();
        }
    }
}
