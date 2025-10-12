@extends('layouts.app')

@section('content')
    <div class="relative w-full min-h-screen overflow-hidden text-white">

        {{-- 🔹 Fondo --}}
        <img src="{{ $meta['background'] ?? '' }}" class="absolute inset-0 w-full h-full object-cover opacity-40 blur">
        <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/60 to-transparent"></div>

        <div class="relative z-10 flex flex-col lg:flex-row h-full">
            {{-- 🧾 Info principal --}}
            <div class="flex-1 p-10 space-y-6">
                <div>
                    <h1 class="text-5xl lg:text-6xl font-extrabold leading-tight">{{ $meta['name'] ?? '' }}</h1>
                    <p class="text-gray-400 mt-2">
                        {{ $meta['runtime'] ?? 'N/A' }} · {{ $meta['year'] ?? '' }}
                        @if(!empty($meta['imdbRating']))
                            · ⭐ {{ $meta['imdbRating'] }}
                        @endif
                    </p>
                </div>

                {{-- 🎭 Géneros --}}
                @if(!empty($meta['genres']))
                    <div class="flex flex-wrap gap-2">
                        @foreach($meta['genres'] as $g)
                            <span class="bg-white/10 px-3 py-1 rounded-full text-sm">{{ $g }}</span>
                        @endforeach
                    </div>
                @endif


                {{-- 🧩 Descripción --}}
                @if(!empty($meta['description']))
                    <p class="text-gray-300 max-w-2xl leading-relaxed">
                        {{ $meta['description'] }}
                    </p>
                @endif

                {{-- 👥 Reparto --}}
                @if(!empty($meta['cast']))
                    <div>
                        <h3 class="text-indigo-300 font-semibold mb-2">Reparto</h3>
                        <div class="flex flex-wrap gap-2 text-sm text-gray-300">
                            @foreach($meta['cast'] as $actor)
                                <span class="bg-white/10 px-3 py-1 rounded-full">{{ $actor }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- 🔗 Streams --}}
            <div class="w-full lg:w-[400px] bg-black/60 backdrop-blur-sm p-6 space-y-3 
                border-t lg:border-t-0 lg:border-l border-indigo-900/50
                lg:h-screen lg:overflow-y-auto lg:sticky lg:top-0">

                <h2 class="text-lg font-semibold text-indigo-300 mb-2">Fuentes disponibles</h2>

                @forelse ($streams as $i => $s)
                    <a href="{{ route('watch.play', ['type' => $type, 'id' => $id, 'index' => $i]) }}"
                        class="block bg-[#121426]/60 border border-indigo-900/40 rounded-lg p-3 hover:border-indigo-600/60 transition text-sm">
                        <div class="font-semibold text-gray-200 truncate">{{ $s['name'] ?? 'Fuente' }}</div>
                        <div class="text-xs text-gray-400 mt-1 space-y-0.5">
                            <div>Quality: {{ $s['quality'] ?? 'N/A' }}</div>
                            <div>Language: {{ $s['language'] ?? 'N/A' }}</div>
                            <div>Size: {{ $s['size'] ?? 'Desconocido' }}</div>
                            <div>Type: {{ $s['type'] ?? 'N/A' }}</div>
                        </div>
                    </a>
                @empty
                    <div class="text-gray-500 text-sm">⚠️ No hay streams disponibles.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection