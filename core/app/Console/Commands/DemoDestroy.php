<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Answer;
use App\Models\Connection;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Request;
use App\Models\User;
use App\Models\Score;

class DemoDestroy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:destroy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove demo data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $demoUser = User::where('email', 'simpson_demo@demo.demo')->first();
        Project::where('title', 'SIMPSON Demo Study')->where('creator_id', $demoUser->id)->delete();

        $users = User::where('email', 'like', '%demo-temp.demo')->get();
        Connection::whereIn('recipient_id', $users->pluck('id'))->delete();
        Connection::whereIn('initiator_id', $users->pluck('id'))->delete();
        Request::whereIn('recipient_id', $users->pluck('id'))->delete();
        Request::whereIn('initiator_id', $users->pluck('id'))->delete();
        Membership::whereIn('user_id', $users->pluck('id'))->delete();
        Answer::whereIn('user_id', $users->pluck('id'))->delete();
        Score::whereIn('user_id', $users->pluck('id'))->delete();

        User::where('email', 'like', '%demo-temp.demo')->delete();
    }
}
