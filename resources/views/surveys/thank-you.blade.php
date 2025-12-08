@extends('surveys.layout')

@section('title', 'Thank You')

@section('content')
<div class="bg-white rounded-lg shadow-md p-8 text-center">
    <div class="mb-6">
        <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-4">Thank You!</h1>

    <p class="text-gray-600 mb-6">
        Your survey response has been submitted successfully.
    </p>

    <div class="bg-gray-50 rounded-lg p-4 text-left">
        <h2 class="font-medium text-gray-900 mb-2">Survey Details</h2>
        <dl class="text-sm text-gray-600 space-y-1">
            <div class="flex justify-between">
                <dt>Survey:</dt>
                <dd class="font-medium">{{ $survey->display_title }}</dd>
            </div>
            <div class="flex justify-between">
                <dt>Submitted:</dt>
                <dd class="font-medium">{{ now()->format('F j, Y \a\t g:i A') }}</dd>
            </div>
        </dl>
    </div>

    <p class="text-gray-500 text-sm mt-6">
        You may close this window.
    </p>
</div>
@endsection
