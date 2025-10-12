@extends('layouts.app')

@section('content')
    <div class="px-6 sm:px-10 lg:px-16 xl:px-20 py-8 space-y-12">

        {{-- 🎞️ Hero Carousel --}}
        <div x-data="heroCarousel({ total: {{ count($banners) }} })" x-init="startAuto()"
            class="relative w-full h-[70vh] max-h-[700px] overflow-hidden rounded-2xl shadow-2xl mb-12">

            <template x-for="(banner, index) in {{ Js::from($banners) }}" :key="index">
                <div x-show="active === index" x-transition.opacity
                    class="absolute inset-0 transition-all duration-700 ease-in-out">
                    <div class="absolute inset-0 overflow-hidden">
                        <img :src="banner.background" alt=""
                            class="w-full h-full object-cover blur-xl brightness-90 contrast-110 scale-110 opacity-80 transition duration-700">
                        <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/50 to-transparent"></div>
                    </div>

                    <div class="relative z-10 flex items-center justify-start h-full px-12">
                        <div class="flex flex-col md:flex-row items-center md:items-end gap-10 w-full">
                            <img :src="banner.poster"
                                class="w-56 md:w-64 rounded-xl shadow-lg transition-transform duration-700 hover:scale-105"
                                alt="">
                            <div class="text-white max-w-2xl md:ml-8">
                                <h2 class="text-3xl font-extrabold drop-shadow-md mb-3" x-text="banner.name"></h2>
                                <p class="text-gray-300 text-base leading-relaxed line-clamp-3" x-text="banner.description">
                                </p>

                                {{-- 🔘 Botón Ver más que abre el modal --}}
                                <button @click="window.dispatchEvent(new CustomEvent('open-preview', {
                                        detail: JSON.parse(JSON.stringify(banner))
                                    }))"
                                    class="inline-block mt-5 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 rounded-lg font-semibold text-sm transition">
                                    Ver más
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <button @click="prev()"
                class="absolute left-5 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white p-3 rounded-full">‹</button>
            <button @click="next()"
                class="absolute right-5 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white p-3 rounded-full">›</button>

            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex space-x-2 z-20">
                <template x-for="i in total">
                    <button @click="active = i - 1" :class="active === i - 1 ? 'bg-indigo-400 scale-110' : 'bg-gray-400/60'"
                        class="w-3.5 h-3.5 rounded-full transition-all duration-300">
                    </button>
                </template>
            </div>
        </div>

        {{-- 📺 Catálogos dinámicos --}}
        @foreach($catalogs as $cat)
            <section
                class="rounded-2xl bg-[#1a1f35]/40 backdrop-blur-sm border border-indigo-900/30 shadow-lg p-5 transition duration-300">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold text-indigo-300">{{ $cat['meta']['name'] }}</h2>
                </div>

                <div class="flex space-x-5 overflow-x-auto scrollbar-thin scrollbar-thumb-indigo-700/50 pb-4">
                    @foreach($cat['items'] as $item)
                        @php
                            // ✅ Evitar errores por comillas o nulos
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

                        <div class="flex-shrink-0 w-44 md:w-48 lg:w-52 group">
                            <div class="preview-item block relative rounded-xl overflow-hidden bg-[#20243b]/70 
                                               border border-indigo-900/30 shadow-md transform transition duration-300 
                                               group-hover:scale-105 cursor-pointer" data-preview='@json($previewData)'>
                                <div class="aspect-[2/3] w-full overflow-hidden rounded-xl">
                                    <img src="{{ $item['poster'] ?? asset('img/no-poster.png') }}" loading="lazy"
                                        class="w-full h-full object-cover transition duration-700 ease-in-out"
                                        alt="{{ $item['name'] ?? '' }}">
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
                interval: null,
                startAuto() { this.interval = setInterval(() => this.next(), 6000) },
                stopAuto() { clearInterval(this.interval) },
                next() { this.active = (this.active + 1) % this.total },
                prev() { this.active = (this.active - 1 + this.total) % this.total }
            }));
        });

        // 🎬 Escucha global para los items de catálogo
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.preview-item').forEach(el => {
                el.addEventListener('click', () => {
                    try {
                        const data = JSON.parse(el.getAttribute('data-preview'));
                        window.dispatchEvent(new CustomEvent('open-preview', { detail: data }));
                    } catch (e) {
                        console.error('❌ Error parseando preview:', e);
                    }
                });
            });
        });
    </script>
@endsection