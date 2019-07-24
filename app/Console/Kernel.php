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
        \App\Console\Commands\TenancyRun::class,
        \App\Console\Commands\staMonthUnitsGrade::class,
        \App\Console\Commands\staProjectGrade::class,
        \App\Console\Commands\staOverdueGrade::class,
        \App\Console\Commands\staProgressGrade::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('sta:overdue --force')->everyMinute(); //每分钟执行一次
        $schedule->command('sta:project --force')->dailyAt('10:30'); //项目评分
        $schedule->command('sta:project --force')->dailyAt('16:30'); //项目评分
        $schedule->command('sta:progress --force')->dailyAt('22:00'); //每天晚上10点刷新.更新进度百分比
        $schedule->command('sta:units --force')->monthlyOn(1, '00:00');//每个月1号 执行一次
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
