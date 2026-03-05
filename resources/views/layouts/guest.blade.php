<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div
            class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 {{ request()->routeIs('login') ? 'relative bg-cover bg-center bg-no-repeat' : 'bg-gray-100' }}"
            @if(request()->routeIs('login')) style="background-image: url('{{ asset('images/bobo.jpg') }}');" @endif
        >
            @if(request()->routeIs('login'))
                <div class="absolute inset-0 bg-black/35"></div>
            @endif

            <div class="w-full mt-6 overflow-hidden sm:rounded-lg {{ request()->routeIs('login') ? 'max-[500px]:w-[92%] max-[500px]:mx-auto sm:max-w-[34rem] px-8 max-[500px]:px-5 py-6 relative z-10 bg-white border-2 border-black shadow-2xl' : 'sm:max-w-md px-6 py-4 bg-white shadow-md' }}">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
