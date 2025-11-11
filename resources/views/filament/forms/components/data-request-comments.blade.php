@php
    $mentionableUsers = \App\Models\User::all();
    // Get the first (and only) DataRequestResponse for this DataRequest
    $response = $getRecord()?->responses?->first();
@endphp

<div>
    @if($response)
        <livewire:commentions::comments
            :record="$response"
            :mentionables="$mentionableUsers"
            :sidebar-enabled="true"
        />
    @else
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('No response available yet. Comments will be available after a response is created.') }}
        </div>
    @endif
</div>
