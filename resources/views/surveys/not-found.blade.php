@extends('surveys.layout')

@section('title', 'Survey Not Found')

@section('content')
<div class="bg-white rounded-lg shadow-md p-8 text-center">
    <div class="mb-6">
        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-4">Survey Not Found</h1>

    <p class="text-gray-600 mb-6">
        The survey you're looking for doesn't exist or the link is invalid.
    </p>

    <p class="text-gray-500 text-sm">
        Please check the URL and try again, or contact the person who sent you this survey.
    </p>
</div>
@endsection
