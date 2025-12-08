@extends('surveys.layout')

@section('title', 'Survey Already Completed')

@section('content')
<div class="bg-white rounded-lg shadow-md p-8 text-center">
    <div class="mb-6">
        <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-4">Survey Already Completed</h1>

    <p class="text-gray-600 mb-6">
        This survey was already submitted on
        <strong>{{ $survey->completed_at->format('F j, Y \a\t g:i A') }}</strong>.
    </p>

    <p class="text-gray-500 text-sm">
        Thank you for your response. If you need to make changes, please contact the survey administrator.
    </p>
</div>
@endsection
