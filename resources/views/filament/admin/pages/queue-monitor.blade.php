<x-filament-panels::page>
    @php
        $stats = $this->getQueueStats();
        $failedJobs = $this->getFailedJobs();
    @endphp

    <div class="space-y-6">
        {{-- Queue Statistics --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-gray-100 p-2 dark:bg-gray-800">
                        <x-heroicon-o-clock class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['pending'] }}</p>
                    </div>
                </div>
            </div>

            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-warning-100 p-2 dark:bg-warning-900/20">
                        <x-heroicon-o-arrow-path class="h-5 w-5 text-warning-600 dark:text-warning-400 animate-spin" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Processing</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['processing'] }}</p>
                    </div>
                </div>
            </div>

            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-primary-100 p-2 dark:bg-primary-900/20">
                        <x-heroicon-o-queue-list class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total in Queue</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>

            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg {{ $stats['failed'] > 0 ? 'bg-danger-100 dark:bg-danger-900/20' : 'bg-success-100 dark:bg-success-900/20' }} p-2">
                        @if($stats['failed'] > 0)
                            <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-danger-600 dark:text-danger-400" />
                        @else
                            <x-heroicon-o-check-circle class="h-5 w-5 text-success-600 dark:text-success-400" />
                        @endif
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Failed</p>
                        <p class="text-2xl font-semibold {{ $stats['failed'] > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-900 dark:text-white' }}">{{ $stats['failed'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Queued Jobs Table --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header-wrapper px-6 py-4 border-b border-gray-200 dark:border-white/10">
                <div class="fi-section-header flex items-center justify-between">
                    <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Queued Jobs
                    </h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Auto-refreshes every 5 seconds</span>
                </div>
            </div>

            <div class="fi-section-content">
                {{ $this->table }}
            </div>
        </div>

        {{-- Failed Jobs Section --}}
        @if(count($failedJobs) > 0)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-header-wrapper px-6 py-4 border-b border-gray-200 dark:border-white/10">
                <div class="fi-section-header">
                    <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Recent Failed Jobs
                    </h3>
                    <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                        Showing the 10 most recent failed jobs
                    </p>
                </div>
            </div>

            <div class="fi-section-content p-6">
                <div class="space-y-4">
                    @foreach($failedJobs as $job)
                    <div class="rounded-lg border border-danger-200 bg-danger-50 p-4 dark:border-danger-800 dark:bg-danger-900/20">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="font-mono text-sm font-medium text-danger-700 dark:text-danger-400">
                                        {{ $job['job'] }}
                                    </span>
                                    <span class="inline-flex items-center rounded-md bg-danger-100 px-2 py-0.5 text-xs font-medium text-danger-700 dark:bg-danger-400/10 dark:text-danger-400">
                                        {{ $job['queue'] }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                    Failed at: {{ $job['failed_at'] }}
                                </p>
                                <pre class="text-xs text-danger-600 dark:text-danger-400 whitespace-pre-wrap bg-danger-100/50 dark:bg-danger-950/50 rounded p-2 overflow-x-auto">{{ $job['exception'] }}</pre>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
