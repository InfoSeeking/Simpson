<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\Inspire::class,
        \App\Console\Commands\GenerateApiDocumentation::class,
        // \App\Console\Commands\DemoCreate::class,
        // \App\Console\Commands\DemoDestroy::class,
        \App\Console\Commands\StudyCreate::class,
        \App\Console\Commands\StudyDestroy::class,
        \App\Console\Commands\StudyActivate::class,
        \App\Console\Commands\StudyDeactivate::class,
        \App\Console\Commands\StudyInfo::class,
        \App\Console\Commands\StudyAdvance::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {}
}
