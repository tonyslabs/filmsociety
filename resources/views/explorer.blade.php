@extends('layouts.app')

@section('content')
    <div x-data="explorePage({ 
                baseUrl: '{{ route('explorar.loadmore') }}', 
                type: '{{ $type }}',
                genre: '{{ $genre }}',
                year: '{{ $year }}',
                addonBase: '{{ rtrim(config('stream.base'), '/') }}',
                manifestId: '713e3b0.top'
            })" x-init="init()" class="px-6 sm:px-10 lg:px-16 xl:px-20 py-8 space-y-10">

        <h1 class="text-3xl font-bold text-indigo-300">
            {{ $type === 'movie' ? 'Películas' : ($type === 'series' ? 'Series' : 'Explorar') }}
        </h1>

        {{-- 🧱 Grid --}}
        <div id="exploreGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
            @foreach($results as $item)
                <div class="group relative rounded-xl overflow-hidden bg-[#20243b]/70 border border-indigo-900/30 
                                shadow-md shadow-indigo-900/30 transform transition duration-300 hover:scale-105 cursor-pointer"
                    @click="window.dispatchEvent(new CustomEvent('open-preview', {
                                    detail: {
                                        id: '{{ $item['id'] }}',
                                        type: '{{ $item['type'] }}',
                                        name: '{{ $item['name'] }}',
                                        description: `{{ $item['description'] ?? '' }}`,
                                        background: '{{ $item['background'] ?? $item['poster'] }}',
                                        trailer: '{{ $item['trailers'][0]['source'] ?? '' }}'
                                    }
                                }))">
                    <div class="aspect-[2/3] w-full overflow-hidden rounded-xl">
                        <img src="{{ $item['poster'] ?? asset('img/no-poster.png') }}" loading="lazy"
                            class="w-full h-full object-cover transition duration-700 ease-in-out"
                            alt="{{ $item['name'] ?? '' }}">
                    </div>

                    <div
                        class="absolute inset-0 bg-gradient-to-t from-[#0b0d17]/90 via-transparent opacity-0 group-hover:opacity-100 transition">
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 🧩 Modal reutilizable --}}
        @include('components.preview-modal')
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('explorePage', ({ baseUrl, type, genre, year, addonBase, manifestId }) => ({
                baseUrl, type, genre, year, addonBase, manifestId,
                skip: 30,
                loading: false,
                done: false,

                init() {
                    window.addEventListener('scroll', () => {
                        const bottom = window.innerHeight + window.scrollY >= document.body.offsetHeight - 300;
                        if (bottom && !this.loading && !this.done) this.loadMore();
                    });
                },

                async loadMore() {
                    if (this.loading || this.done) return;
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({
                            type: this.type,
                            skip: this.skip,
                            ...(this.genre ? { genre: this.genre } : {}),
                            ...(this.year ? { year: this.year } : {})
                        });

                        const res = await fetch(`${this.baseUrl}?${params.toString()}`);
                        const data = await res.json();
                        if (!data.items || data.items.length === 0) {
                            this.done = true;
                            return;
                        }

                        const grid = document.querySelector('#exploreGrid');
                        data.items.forEach(item => {
                            const el = document.createElement('div');
                            el.className = `group relative rounded-xl overflow-hidden bg-[#20243b]/70 
                                border border-indigo-900/30 shadow-md shadow-indigo-900/30 transform transition duration-300 
                                hover:scale-105 cursor-pointer`;
                            el.innerHTML = `
                                <img src="${item.poster}" loading="lazy" class="w-full h-72 object-cover" alt="${item.name}">
                                <div class="absolute inset-0 bg-gradient-to-t from-[#0b0d17]/90 via-transparent opacity-0 group-hover:opacity-100 transition"></div>
                            `;
                            el.addEventListener('click', () => {
                                window.dispatchEvent(new CustomEvent('open-preview', {
                                    detail: {
                                        id: item.id,
                                        type: item.type,
                                        name: item.name,
                                        description: item.description || '',
                                        background: item.background || item.poster,
                                        trailer: item.trailers?.[0]?.source || ''
                                    }
                                }));
                            });
                            grid.appendChild(el);
                        });

                        this.skip += data.items.length;
                    } catch (err) {
                        console.error('❌ Error cargando más:', err);
                    } finally {
                        this.loading = false;
                    }
                },
            }));
        });
    </script>
@endsection