<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <x-jet-banner />

        <div class="flex flex-col">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="w-full">
                    <div class="mx-auto pt-4 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif
            
             @if(session()->has('success'))
            <div class="bg-green-400 text-white px-8 py-2 my-2 rounded-sm text-center font-semibold z-40">
                {{ session()->get('success') }}
            </div>
            @endif

            @if(session()->has('error'))
            <div class="bg-red-400 text-white px-8 py-2 my-2 rounded-sm text-center font-semibold z-40">
                {{ session()->get('error') }}
            </div>
            @endif

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <div class="bg-red-400 text-white px-8 py-2 my-2 rounded-sm text-center font-semibold z-40">
                    {{ $error }}
                    </div>
                @endforeach
            @endif
            <!-- Page Content -->
            <main class="w-full py-6 px-4 lg:px-8">
                {{ $slot }}
            </main>
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
