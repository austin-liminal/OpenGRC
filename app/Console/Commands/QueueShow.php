<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class QueueShow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:show
                            {--connection= : The queue connection to use}
                            {--queue= : The queue name to filter by}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display all queues, pending jobs, and failed jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = $this->option('connection') ?? config('queue.default');
        $queueFilter = $this->option('queue');

        $this->info('Queue Status Overview');
        $this->info("Connection: {$connection}");
        $this->newLine();

        // Display queue configuration
        $this->displayQueueConfiguration($connection);

        // Display pending jobs
        $this->displayPendingJobs($connection, $queueFilter);

        // Display failed jobs
        $this->displayFailedJobs();

        return Command::SUCCESS;
    }

    /**
     * Display the queue configuration
     */
    protected function displayQueueConfiguration(string $connection): void
    {
        $this->line('<fg=cyan>Queue Configuration:</>');

        $config = config("queue.connections.{$connection}");

        if (! $config) {
            $this->error("Connection '{$connection}' not found in configuration.");

            return;
        }

        $this->table(
            ['Setting', 'Value'],
            [
                ['Driver', $config['driver'] ?? 'N/A'],
                ['Default Queue', $config['queue'] ?? 'default'],
                ['Retry After', ($config['retry_after'] ?? 'N/A').' seconds'],
            ]
        );

        $this->newLine();
    }

    /**
     * Display pending jobs in the queue
     */
    protected function displayPendingJobs(string $connection, ?string $queueFilter): void
    {
        $this->line('<fg=cyan>Pending Jobs:</>');

        $driver = config("queue.connections.{$connection}.driver");

        if ($driver === 'database') {
            $this->displayDatabaseQueueJobs($queueFilter);
        } elseif ($driver === 'redis') {
            $this->displayRedisQueueJobs($connection, $queueFilter);
        } elseif ($driver === 'sync') {
            $this->info('Sync driver does not queue jobs (executes immediately).');
        } else {
            $this->warn("Pending jobs display not implemented for '{$driver}' driver.");
        }

        $this->newLine();
    }

    /**
     * Display jobs from database queue
     */
    protected function displayDatabaseQueueJobs(?string $queueFilter): void
    {
        try {
            $query = DB::table('jobs')
                ->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at')
                ->orderBy('id');

            if ($queueFilter) {
                $query->where('queue', $queueFilter);
            }

            $jobs = $query->get();

            if ($jobs->isEmpty()) {
                $this->info('No pending jobs in the queue.');

                return;
            }

            $tableData = [];
            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);
                $jobName = $payload['displayName'] ?? $payload['job'] ?? 'Unknown';

                $tableData[] = [
                    $job->id,
                    $job->queue,
                    $jobName,
                    $job->attempts,
                    $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : 'Not reserved',
                    date('Y-m-d H:i:s', $job->available_at),
                ];
            }

            $this->table(
                ['ID', 'Queue', 'Job', 'Attempts', 'Reserved At', 'Available At'],
                $tableData
            );

            $this->info('Total pending jobs: '.count($tableData));
        } catch (\Exception $e) {
            $this->warn('Could not fetch database queue jobs: '.$e->getMessage());
        }
    }

    /**
     * Display jobs from Redis queue
     */
    protected function displayRedisQueueJobs(string $connection, ?string $queueFilter): void
    {
        try {
            $redis = app('redis')->connection(config("queue.connections.{$connection}.connection"));
            $queueName = $queueFilter ?? config("queue.connections.{$connection}.queue", 'default');
            $queueKey = 'queues:'.$queueName;

            $jobCount = $redis->llen($queueKey);

            if ($jobCount === 0) {
                $this->info("No pending jobs in queue '{$queueName}'.");

                return;
            }

            $this->info("Queue '{$queueName}' has {$jobCount} pending job(s).");

            // Get first 10 jobs as sample
            $jobs = $redis->lrange($queueKey, 0, 9);

            if (! empty($jobs)) {
                $tableData = [];
                foreach ($jobs as $index => $job) {
                    $payload = json_decode($job, true);
                    $jobName = $payload['displayName'] ?? $payload['job'] ?? 'Unknown';

                    $tableData[] = [
                        $index + 1,
                        $jobName,
                        $payload['attempts'] ?? 0,
                    ];
                }

                $this->table(
                    ['Position', 'Job', 'Attempts'],
                    $tableData
                );

                if ($jobCount > 10) {
                    $this->info("Showing first 10 jobs. Total: {$jobCount}");
                }
            }
        } catch (\Exception $e) {
            $this->warn('Could not fetch Redis queue jobs: '.$e->getMessage());
        }
    }

    /**
     * Display failed jobs
     */
    protected function displayFailedJobs(): void
    {
        $this->line('<fg=cyan>Failed Jobs:</>');

        try {
            $failedJobs = DB::table('failed_jobs')
                ->select('id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at')
                ->orderBy('failed_at', 'desc')
                ->limit(20)
                ->get();

            if ($failedJobs->isEmpty()) {
                $this->info('No failed jobs.');

                return;
            }

            $tableData = [];
            foreach ($failedJobs as $job) {
                $payload = json_decode($job->payload, true);
                $jobName = $payload['displayName'] ?? $payload['job'] ?? 'Unknown';

                // Get first line of exception
                $exceptionLines = explode("\n", $job->exception);
                $exceptionPreview = substr($exceptionLines[0], 0, 50).'...';

                $tableData[] = [
                    $job->id,
                    $job->uuid,
                    $job->queue,
                    $jobName,
                    $exceptionPreview,
                    $job->failed_at,
                ];
            }

            $this->table(
                ['ID', 'UUID', 'Queue', 'Job', 'Exception', 'Failed At'],
                $tableData
            );

            $totalFailed = DB::table('failed_jobs')->count();
            $this->info("Total failed jobs: {$totalFailed}");

            if ($totalFailed > 20) {
                $this->info('Showing most recent 20 failed jobs.');
            }
        } catch (\Exception $e) {
            $this->warn('Could not fetch failed jobs: '.$e->getMessage());
        }
    }
}
