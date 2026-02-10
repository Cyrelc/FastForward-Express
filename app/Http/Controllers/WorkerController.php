<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class WorkerController extends Controller
{
    /**
     * Get current worker health status
     *
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus(Request $req)
    {
        // Check if user is authenticated and has appSettings permission
        if (!$req->user() || $req->user()->cannot('appSettings.edit.*.*')) {
            abort(403, 'Insufficient permissions to view worker status');
        }

        // Get cached health status
        $healthStatus = Cache::get('worker:health:status', [
            'status' => 'unknown',
            'message' => 'No recent health check data available',
            'checked_at' => null,
            'checked_at_human' => null,
        ]);

        // Check if data is stale (older than 15 minutes)
        $isStale = false;
        if ($healthStatus['checked_at']) {
            $ageInMinutes = (time() - $healthStatus['checked_at']) / 60;
            $isStale = $ageInMinutes > 15;
        } else {
            $isStale = true;
        }

        // Get supervisor status for all workers
        $supervisorStatus = $this->getSupervisorStatus();

        // Determine overall health
        $overallHealth = $this->determineOverallHealth($healthStatus, $supervisorStatus, $isStale);

        return response()->json([
            'health' => $healthStatus,
            'supervisor' => $supervisorStatus,
            'overall' => $overallHealth,
            'is_stale' => $isStale,
        ]);
    }

    /**
     * Manually restart workers
     *
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function restart(Request $req)
    {
        // Check if user is authenticated and has appSettings permission
        if (!$req->user() || $req->user()->cannot('appSettings.edit.*.*')) {
            abort(403, 'Insufficient permissions to restart workers');
        }

        $reason = $req->input('reason', 'Manual restart by administrator');

        // Execute restart via supervisorctl
        $workerGroup = env('LARAVEL_WORKER_GROUP', 'laravel-queue');
        exec("sudo supervisorctl restart {$workerGroup}: 2>&1", $output, $exitCode);

        if ($exitCode === 0) {
            // Log the restart
            activity('worker_maintenance')
                ->causedBy($req->user())
                ->withProperties([
                    'reason' => $reason,
                    'output' => $output,
                ])
                ->log('Workers manually restarted');

            // Update health status
            Artisan::call('worker:health-store', [
                '--status' => 'restarted',
                '--message' => "Manual restart: {$reason}",
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Workers restarted successfully',
                'output' => $output,
            ]);
        } else {
            activity('worker_maintenance')
                ->causedBy($req->user())
                ->event('error')
                ->withProperties([
                    'reason' => $reason,
                    'output' => $output,
                    'exit_code' => $exitCode,
                ])
                ->log('Worker restart failed');

            return response()->json([
                'success' => false,
                'message' => 'Failed to restart workers',
                'output' => $output,
                'exit_code' => $exitCode,
            ], 500);
        }
    }

    /**
     * Get detailed queue statistics
     *
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQueueStats(Request $req)
    {
        // Check if user is authenticated and has admin permission
        if (!$req->user() || $req->user()->cannot('viewAny', \App\Models\User::class)) {
            abort(403, 'Only administrators can view queue statistics');
        }

        $pendingJobs = \DB::table('jobs')->count();
        $failedJobs = \DB::table('failed_jobs')->count();
        
        $oldestJob = \DB::table('jobs')
            ->orderBy('created_at', 'asc')
            ->first();
        
        $oldestJobAge = $oldestJob ? (time() - $oldestJob->created_at) : 0;

        return response()->json([
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs,
            'oldest_job_age_seconds' => $oldestJobAge,
            'oldest_job_age_human' => $oldestJobAge > 0 ? $this->formatDuration($oldestJobAge) : 'N/A',
        ]);
    }

    /**
     * Get supervisor status for all workers
     *
     * @return array
     */
    private function getSupervisorStatus()
    {
        $workerGroup = env('LARAVEL_WORKER_GROUP', 'laravel-queue');
        exec("sudo supervisorctl status {$workerGroup}: 2>&1", $output, $exitCode);
        
        $workers = [];
        foreach ($output as $line) {
            if (preg_match('/^([\w-]+)\s+(RUNNING|STOPPED|STARTING|BACKOFF|FATAL|EXITED|UNKNOWN)/', $line, $matches)) {
                $workers[] = [
                    'name' => $matches[1],
                    'status' => $matches[2],
                    'is_healthy' => in_array($matches[2], ['RUNNING', 'STARTING']),
                    'raw' => $line,
                ];
            }
        }

        return [
            'workers' => $workers,
            'total_count' => count($workers),
            'healthy_count' => count(array_filter($workers, fn($w) => $w['is_healthy'])),
        ];
    }

    /**
     * Determine overall system health
     *
     * @param array $healthStatus
     * @param array $supervisorStatus
     * @param bool $isStale
     * @return array
     */
    private function determineOverallHealth($healthStatus, $supervisorStatus, $isStale)
    {
        if ($isStale) {
            return [
                'status' => 'unknown',
                'color' => 'warning',
                'icon' => 'fa-question-circle',
                'message' => 'Health check data is stale',
            ];
        }

        $allWorkersHealthy = $supervisorStatus['healthy_count'] === $supervisorStatus['total_count'];

        if ($healthStatus['status'] === 'up' && $allWorkersHealthy) {
            return [
                'status' => 'healthy',
                'color' => 'success',
                'icon' => 'fa-check-circle',
                'message' => 'All systems operational',
            ];
        } elseif ($healthStatus['status'] === 'recovered') {
            return [
                'status' => 'recovered',
                'color' => 'info',
                'icon' => 'fa-sync',
                'message' => 'Recently recovered from failure',
            ];
        } else {
            return [
                'status' => 'unhealthy',
                'color' => 'danger',
                'icon' => 'fa-exclamation-triangle',
                'message' => $healthStatus['message'] ?? 'Workers are experiencing issues',
            ];
        }
    }

    /**
     * Format duration in human-readable form
     *
     * @param int $seconds
     * @return string
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "{$minutes}m";
        } elseif ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return "{$hours}h {$minutes}m";
        } else {
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            return "{$days}d {$hours}h";
        }
    }
}
