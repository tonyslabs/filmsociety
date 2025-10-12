<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'StreamDeck') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-gray-100 
             bg-gradient-to-br from-[#0b0d17] via-[#141a29] to-[#1b1033] 
             min-h-screen bg-fixed selection:bg-indigo-500/30 selection:text-white">

    <!-- HEADER -->
    <header class="sticky top-0 z-50 bg-[#0b0d17]/70 backdrop-blur-md border-b border-indigo-900/30">
        <div class="max-w-7xl mx-auto flex items-center justify-between px-6 py-3">

            <!-- LOGO -->
            <div class="flex items-center space-x-2">

                <span class="text-xl font-semibold tracking-tight text-indigo-300">
                    Film<span class="text-purple-400">Societyyyyy
                </span>
            </div>

            <!-- NAV -->
            <nav class="hidden md:flex items-center space-x-6 text-sm">
                <a href="{{ route('home') }}" class="hover:text-indigo-400 transition">Inicio</a>
                <a href="{{ route('explorar') }}" class="hover:text-indigo-400 transition">Explorar</a>
                <a href="{{ route('series') }}" class="hover:text-indigo-400 transition">Series</a>
                <a href="{{ route('peliculas') }}" class="hover:text-indigo-400 transition">Películas</a>
            </nav>

        </div>
    </header>

    <!-- MAIN -->
    <main class="relative z-10">
        @yield('content')
    </main>

    <!-- OVERLAY / GLOW -->
    <div class="fixed inset-0 -z-10 bg-gradient-to-t from-purple-900/20 via-transparent to-indigo-900/10 blur-3xl">
    </div>

    <div class="fixed inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_left,_var(--tw-gradient-stops))] 
            from-indigo-950/20 via-transparent to-purple-900/10 blur-3xl"></div>


</body>

</html>