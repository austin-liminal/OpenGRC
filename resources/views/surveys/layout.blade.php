<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="application-name" content="{{ config('app.name') }}"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>

    <title>@yield('title', 'Survey') - {{ config('app.name') }}</title>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @filamentStyles
    @vite('resources/css/app.css')
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-3xl mx-auto py-8 px-4">
        <div class="flex justify-center mb-6">
            <img src="{{ asset('/img/logo.png') }}" width="120" alt="OpenGRC Logo">
        </div>

        @yield('content')
    </div>

    @livewire('notifications')
    @filamentScripts
    @vite('resources/js/app.js')
</body>
</html>
