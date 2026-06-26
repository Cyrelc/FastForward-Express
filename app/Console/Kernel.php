<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\GenerateRepeatingBills::class,
        Commands\QueueHealth::class,
        Commands\StoreWorkerHealth::class,
        Commands\WaitForDatabase::class,
        Commands\WorkerHealthCheck::class,
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
        $schedule->command(Commands\GenerateRepeatingBills::class)->dailyAt('5:00')->weekDays();

        // Worker health monitoring - runs every 10 minutes
        $schedule->command(Commands\WorkerHealthCheck::class)->everyTenMinutes();

        // Preventative daily worker restart at 3 AM.
        // Use the graceful queue:restart signal (cache-based) rather than a hard
        // `supervisorctl restart`: workers finish their current job, exit, and are
        // relaunched by Supervisor via tools/run-worker.sh (which waits for the DB).
        // This needs no sudo and works regardless of how workers are supervised.
        // NOTE: requires a non-database cache driver so the signal still works when
        // MySQL is the thing being recovered.
        $schedule->call(function() {
            try {
                \Artisan::call('queue:restart');
                activity('worker_maintenance')->log('Daily preventative worker restart signalled (queue:restart)');
            } catch (\Throwable $e) {
                activity('worker_maintenance')
                    ->event('error')
                    ->withProperties(['error' => $e->getMessage()])
                    ->log('Daily preventative worker restart FAILED to signal');
            }
        })->dailyAt('03:00');

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
