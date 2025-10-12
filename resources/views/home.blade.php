@extends('layouts.app')

@section('content')
    <style>
        /* 🔹 Fondo animado global */
        body {
            background: linear-gradient(120deg, #0b0d17, #141a29, #1b1033);
            background-size: 200% 200%;
            animation: bgShift 30s ease infinite;
        }

        @keyframes bgShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        /* 🔹 Glow sutil de pósters */
        .preview-item::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at center, rgba(99, 102, 241, 0.25), transparent 70%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .preview-item:hover::after {
            opacity: 1;
        }

        /* 🔹 Fondo con blur */
        .banner-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: blur(25px) brightness(0.8) contrast(1.1);
            transform: scale(1.1);
            opacity: 0.8;
            transition: opacity 0.8s ease;
        }

        /* 🔹 Vista móvil del carrusel */
        @media (max-width: 768px) {
            .hero-slide {
                flex-direction: column !important;
                text-align: center !important;
                justify-content: center !important;
                gap: 1.25rem !important;
            }

            .hero-slide img.poster {
                width: 75% !important;
                max-width: 260px !important;
                height: auto !important;
                margin: 0 auto;
            }

            .hero-slide .text-block {
                margin: 0 auto !important;
                text-align: center !important;
                max-width: 90% !important;
            }

            .hero-slide .play-btn {
                display: none !important;
            }

            .carousel-dots {
                display: none !important;
            }
        }
    </style>

    <div class="px-6 sm:px-10 lg:px-16 xl:px-20 py-8 space-y-12">

        {{-- 🎞️ Hero Carousel --}}
        <div x-data="heroCarousel({ total: {{ count($banners) }} })" x-init="startAuto()"
            class="relative w-full h-[70vh] max-h-[700px] overflow-hidden rounded-2xl shadow-2xl mb-12 select-none">

            {{-- 🔹 Fondo dinámico con blur --}}
            <template x-if="banners.length">
                <img :src="banners[active].background || banners[active].poster" class="banner-bg"
                    :alt="banners[active].name">
            </template>
            <div class="absolute inset-0 bg-gradient-to-r from-black/85 via-black/60 to-transparent z-[1]"></div>


            {{-- 🔹 Banner activo con fade --}}
            <template x-for="(banner, index) in banners" :key="index">
                <div x-show="active === index" x-transition.opacity.duration.1000
                    class="absolute inset-0 z-[2] flex items-center justify-center px-8 md:px-12 hero-slide">
                    <div class="flex flex-col md:flex-row items-center gap-10 w-full max-w-6xl">
                        <img :src="banner.poster"
                            class="poster w-52 sm:w-56 md:w-64 rounded-xl shadow-lg transition-transform duration-700 hover:scale-105"
                            alt="">
                        <div class="text-block text-white max-w-2xl md:ml-8 text-center md:text-left">
                            <h2 class="text-3xl font-extrabold drop-shadow-md mb-3" x-text="banner.name"></h2>
                            <p class="text-gray-300 text-base leading-relaxed line-clamp-3" x-text="banner.description"></p>

                            {{-- 🔘 Botón circular minimalista (solo desktop) --}}
                            <div class="mt-5 flex justify-center md:justify-start play-btn">
                                <button @click="window.dispatchEvent(new CustomEvent('open-preview', {
                                            detail: JSON.parse(JSON.stringify(banner))
                                        }))" class="flex items-center justify-center w-11 h-11 rounded-full 
                                               bg-indigo-500/20 hover:bg-indigo-500/40 
                                               text-indigo-200 hover:text-white transition-all duration-300 
                                               focus:outline-none focus:ring-2 focus:ring-indigo-400/50 backdrop-blur-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M14.752 11.168l-6.518-3.759A1 1 0 007 8.24v7.52a1 1 0 001.234.97l6.518-3.76a1 1 0 000-1.74z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Controles --}}
            <button @click="prev()"
                class="absolute left-5 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white p-3 rounded-full backdrop-blur-sm z-[4]">
                ‹
            </button>
            <button @click="next()"
                class="absolute right-5 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white p-3 rounded-full backdrop-blur-sm z-[4]">
                ›
            </button>

            {{-- Indicadores --}}
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex space-x-2 z-[4] carousel-dots">
                <template x-for="i in total">
                    <button @click="active = i - 1" :class="active === i - 1 ? 'bg-indigo-400 scale-110' : 'bg-gray-400/60'"
                        class="w-3.5 h-3.5 rounded-full transition-all duration-300">
                    </button>
                </template>
            </div>
        </div>

        {{-- 📺 Catálogos dinámicos --}}
        @foreach($catalogs as $cat)
            <section class="rounded-2xl bg-[#1a1f35]/40 backdrop-blur-sm border border-indigo-900/30 shadow-lg 
                                       p-5 transition duration-300 space-y-5">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold text-indigo-300">{{ $cat['meta']['name'] }}</h2>
                </div>

                <div class="flex space-x-6 overflow-x-auto scrollbar-thin scrollbar-thumb-indigo-700/50 pb-5">
                    @foreach($cat['items'] as $item)
                        @php
                            $previewData = [
                                'id' => $item['id'] ?? '',
                                'type' => $item['type'] ?? '',
                                'name' => $item['name'] ?? '',
                                'description' => $item['description'] ?? '',
                                'background' => $item['background'] ?? ($item['poster'] ?? ''),
                                'poster' => $item['poster'] ?? '',
                                'trailer' => $item['trailers'][0]['source'] ?? '',
                            ];
                        @endphp

                        <div class="flex-shrink-0 w-44 md:w-48 lg:w-52 group relative">
                            <div class="preview-item block relative rounded-xl overflow-hidden bg-[#20243b]/70 
                                                            border border-indigo-900/30 shadow-md transform transition-all duration-300 
                                                            group-hover:scale-105 cursor-pointer"
                                data-preview='@json($previewData)'>
                                <div class="aspect-[2/3] w-full overflow-hidden rounded-xl relative">
                                    <img src="{{ $item['poster'] ?? asset('img/no-poster.png') }}" loading="lazy"
                                        class="w-full h-full object-cover transition duration-700 ease-in-out"
                                        alt="{{ $item['name'] ?? '' }}">

                                    {{-- 🎬 Acciones flotantes --}}
                                    <div
                                        class="absolute top-2 right-2 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition">
                                        <button
                                            class="p-1.5 bg-black/50 rounded-full hover:bg-black/70 text-gray-200 hover:text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                        <button
                                            class="p-1.5 bg-black/50 rounded-full hover:bg-black/70 text-gray-200 hover:text-white"
                                            @click="window.dispatchEvent(new CustomEvent('open-preview', {detail: {{ Js::from($previewData) }}}))">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14.752 11.168l-6.518-3.759A1 1 0 007 8.24v7.52a1 1 0 001.234.97l6.518-3.76a1 1 0 000-1.74z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        {{-- 🧩 Modal reutilizable --}}
        @include('components.preview-modal')
    </div>

    {{-- ⚙️ Alpine Hero Carousel --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('heroCarousel', ({ total }) => ({
                active: 0,
                total,
                banners: {{ Js::from($banners) }},
                interval: null,

                startAuto() {
                    this.interval = setInterval(() => this.next(), 6000);
                },
                stopAuto() { clearInterval(this.interval); },

                next() { this.active = (this.active + 1) % this.total; },
                prev() { this.active = (this.active - 1 + this.total) % this.total; }
            }));
        });
    </script>
@endsection