<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\GenerateRepeatingBills;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(new GenerateRepeatingBills)->dailyAt('5:00')->weekDays();
        $schedule->call(function() {
            activity('system_heartbeat')->log('system heartbeat');
        })->everyTenMinutes();
        // $schedule->call(function() {
        //     $generateRepeatingBills = new GenerateRepeatingBills;
        //     $generateRepeatingBills('monthly');
        // })->dailyAt('5:00')->when(function() {
        //     $today = new \DateTime();
        //     $firstWeekdayOfMonth = new \DateTime('+0 weekday ' . $today->format('F Y'));
        //     if($today->format('Y-m-d') === $firstWeekdayOfMonth->format('Y-m-d'))
        //         return true;
        //     return false;
        // });
    }
}
