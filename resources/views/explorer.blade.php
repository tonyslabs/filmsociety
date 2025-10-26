@extends('layouts.app')

@section('content')
    <div x-data="explorePage({ 
            baseUrl: '{{ route('explorar.loadmore') }}', 
            type: '{{ $type }}',
            genre: '{{ $genre }}',
            year: '{{ $year }}',
            search: '{{ $search }}',
            catalogId: '{{ $catalogId }}'
        })" x-init="init()" class="px-6 sm:px-10 lg:px-16 xl:px-20 py-8 space-y-10">

        {{-- Filtros --}}
        <div
            class="sticky top-0 z-40 bg-[#0b0d17]/90 backdrop-blur-md border border-indigo-900/30 p-4 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="text-2xl font-bold text-indigo-300">
                {{ $type === 'series' ? 'Series' : ($type === 'movie' ? 'Películas' : 'Explorar') }}
            </h1>

            <div class="flex flex-wrap gap-3 items-center">
                <select x-model="type" @change="reload()"
                    class="bg-[#121426] border border-indigo-900 text-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    <option value="movie" {{ $type === 'movie' ? 'selected' : '' }}>Películas</option>
                    <option value="series" {{ $type === 'series' ? 'selected' : '' }}>Series</option>
                </select>

                <select x-model="genre" @change="reload()"
                    class="bg-[#121426] border border-indigo-900 text-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    <option value="">Género</option>
                    @foreach($genres as $g)
                        <option value="{{ $g }}" {{ $g == $genre ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>

                <select x-model="year" @change="reload()"
                    class="bg-[#121426] border border-indigo-900 text-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    <option value="">Año</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ (string) $y === (string) $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>

                <input x-model="search" @keydown.enter="reload()" type="text" placeholder="Buscar…"
                    class="bg-[#121426] border border-indigo-900 text-gray-300 rounded-lg px-3 py-1.5 text-sm w-52 sm:w-64 focus:ring-2 focus:ring-indigo-600">
            </div>
        </div>

        {{-- Grid --}}
        <div id="exploreGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-5">
            @foreach($results as $item)
                <div class="group relative rounded-xl overflow-hidden bg-[#20243b]/70 border border-indigo-900/30 
                                shadow-md shadow-indigo-900/30 transform transition duration-300 hover:scale-105 cursor-pointer"
                    data-preview='@json($item)'
                    onclick="try{const d=JSON.parse(this.dataset.preview);window.dispatchEvent(new CustomEvent('open-preview',{detail:d}));}catch(e){}">
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

        {{-- Loader y vacío dinámicos --}}
        <template x-if="loading">
            <div class="flex justify-center py-10">
                <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
            </div>
        </template>

        <template x-if="!loading && count===0">
            <div class="text-center text-gray-400 py-16 text-sm">No se encontraron resultados.</div>
        </template>

        @include('components.preview-modal')
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('explorePage', ({ baseUrl, type, genre, year, search, catalogId }) => ({
                baseUrl, type, genre, year, search, catalogId,
                skip: 30,
                loading: false,
                done: false,
                ids: new Set(), // para dedup
                count: {{ count($results) }},

                async reload() {
                    // recalcular catálogo localmente para que loadMore pegue al correcto
                    this.catalogId = (this.search && this.search.trim() !== '') ? '713e3b0.search'
                        : (this.year ? '713e3b0.year' : '713e3b0.top');

                    // reset
                    document.getElementById('exploreGrid').innerHTML = '';
                    this.skip = 0;
                    this.done = false;
                    this.loading = false;
                    this.ids = new Set();
                    this.count = 0;
                    await this.load(true);

                    // actualizar querystring
                    const q = new URLSearchParams({
                        ...(this.type ? { type: this.type } : {}),
                        ...(this.genre ? { genre: this.genre } : {}),
                        ...(this.year ? { year: this.year } : {}),
                        ...(this.search ? { search: this.search } : {}),
                    });
                    history.replaceState({}, '', `${location.pathname}${q.toString() ? '?' + q.toString() : ''}`);
                },

                async load() {
                    if (this.loading || this.done) return;
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({
                            type: this.type,
                            skip: this.skip,
                            ...(this.genre ? { genre: this.genre } : {}),
                            ...(this.year ? { year: this.year } : {}),
                            ...(this.search ? { search: this.search } : {}),
                        });

                        const res = await fetch(`${this.baseUrl}?${params.toString()}`);
                        const json = await res.json();
                        const items = Array.isArray(json.items) ? json.items : [];
                        if (!items.length) { this.done = true; return; }

                        const grid = document.getElementById('exploreGrid');
                        for (const item of items) {
                            if (this.ids.has(item.id)) continue;
                            this.ids.add(item.id);
                            this.count++;

                            const el = document.createElement('div');
                            el.className = `group relative rounded-xl overflow-hidden bg-[#20243b]/70 
                                        border border-indigo-900/30 shadow-md shadow-indigo-900/30 transform 
                                        transition duration-300 hover:scale-105 cursor-pointer`;
                            el.innerHTML = `
                            <div class="aspect-[2/3] w-full overflow-hidden rounded-xl">
                                <img src="${item.poster}" loading="lazy" class="w-full h-full object-cover" alt="${item.name || ''}">
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-[#0b0d17]/90 via-transparent opacity-0 group-hover:opacity-100 transition"></div>
                        `;
                            el.addEventListener('click', () => {
                                window.dispatchEvent(new CustomEvent('open-preview', { detail: item }));
                            });
                            grid.appendChild(el);
                        }

                        this.skip += items.length;
                    } catch (e) {
                        console.error('❌ Error cargando más:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                init() {
                    // scroll infinito
                    window.addEventListener('scroll', () => {
                        const bottom = window.innerHeight + window.scrollY >= document.body.offsetHeight - 300;
                        if (bottom && !this.loading && !this.done) this.load();
                    });
                },

                openPreview(item) {
                    window.dispatchEvent(new CustomEvent('open-preview', { detail: item }));
                }
            }));
        });
    </script>
@endsection
