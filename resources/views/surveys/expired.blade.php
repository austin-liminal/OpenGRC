@extends('surveys.layout')

@section('title', 'Survey Expired')

@section('content')
<div class="bg-white rounded-lg shadow-md p-8 text-center">
    <div class="mb-6">
        <svg class="mx-auto h-16 w-16 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-4">Survey Link Expired</h1>

    <p class="text-gray-600 mb-6">
        This survey link is no longer valid. The survey expired on
        <strong>{{ $survey->expiration_date->format('F j, Y') }}</strong>.
    </p>

    <p class="text-gray-500 text-sm">
        If you believe this is an error, please contact the person who sent you this survey.
    </p>
</div>
@endsection
