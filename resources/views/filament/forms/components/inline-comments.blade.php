@php
    $mentionableUsers = \App\Models\User::all();
@endphp

<div>
    @if($getRecord())
        <livewire:commentions::comments
            :record="$getRecord()"
            :mentionables="$mentionableUsers"
            :sidebar-enabled="true"
        />
    @else
        <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Comments will be available after saving this record.') }}
        </div>
    @endif
</div>
