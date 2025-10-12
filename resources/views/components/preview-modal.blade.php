{{-- 🧩 Componente de previsualización reutilizable --}}
<div x-data="previewModal({ 
        addonBase: '{{ rtrim(config('stream.base'), '/') }}', 
        manifestId: '713e3b0.top' 
    })" x-cloak x-init="
        // Registrar escucha global cuando Alpine ya está inicializado
        window.addEventListener('open-preview', (e) => show(e.detail));
    ">
    {{-- 🎬 Modal --}}
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4"
        @click.self="close()">
        <template x-if="data">
            <div class="relative bg-[#1a1f35] rounded-2xl shadow-xl overflow-hidden w-full max-w-4xl">
                <div class="relative">
                    <img :src="data.background" class="w-full h-64 object-cover opacity-70">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0b0d17]/90 via-transparent"></div>
                    <h2 class="absolute bottom-5 left-6 text-3xl font-bold text-white" x-text="data.name"></h2>
                </div>

                <div class="p-6 text-gray-200 space-y-5">
                    <div class="flex flex-wrap gap-3 text-sm text-gray-400">
                        <template x-if="data.releaseInfo">
                            <span class="px-2 py-1 rounded bg-indigo-900/40 text-indigo-300 font-semibold">
                                📅 <span x-text="data.releaseInfo"></span>
                            </span>
                        </template>
                        <template x-if="data.imdbRating">
                            <span class="px-2 py-1 rounded bg-yellow-600/30 text-yellow-300 font-semibold">
                                ⭐ IMDb: <span x-text="data.imdbRating"></span>
                            </span>
                        </template>
                        <template x-if="data.genres && data.genres.length">
                            <span class="px-2 py-1 rounded bg-slate-700/50" x-text="data.genres.join(', ')"></span>
                        </template>
                    </div>

                    <template x-if="data.director && data.director.length">
                        <div class="text-sm text-gray-400">
                            🎬 <span class="font-semibold text-gray-300">Director:</span>
                            <span x-text="data.director.join(', ')"></span>
                        </div>
                    </template>

                    <template x-if="data.cast && data.cast.length">
                        <div class="text-sm text-gray-400">
                            🎭 <span class="font-semibold text-gray-300">Reparto:</span>
                            <span
                                x-text="data.cast.slice(0, 5).join(', ') + (data.cast.length > 5 ? '...' : '')"></span>
                        </div>
                    </template>

                    <div class="flex justify-end mb-2">
                        <select x-model="lang" @change="translate()"
                            class="bg-[#121426] border border-indigo-900 text-gray-300 rounded-md px-2 py-1 text-sm">
                            <option value="en">English</option>
                            <option value="es">Español</option>
                        </select>
                    </div>

                    <p class="leading-relaxed" x-text="translatedDescription || 'Sin descripción disponible.'"></p>

                    <template x-if="data.trailer">
                        <iframe :src="`https://www.youtube.com/embed/${data.trailer}`"
                            class="w-full h-64 rounded-lg border border-indigo-900"></iframe>
                    </template>

                    <div class="flex justify-end gap-3 pt-4 border-t border-indigo-900/40 mt-6">
                        <button @click="close()"
                            class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-600 text-gray-400 hover:text-gray-200 hover:border-gray-400 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Cerrar
                        </button>

                        <template x-if="data.type === 'movie'">
                            <a :href="`/watch/movie/${data.id}`"
                                class="flex items-center gap-2 border border-indigo-600 hover:border-indigo-400 text-indigo-400 hover:text-indigo-200 px-4 py-2 rounded-md font-semibold transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14.752 11.168l-6.518-3.759A1 1 0 007 8.24v7.52a1 1 0 001.234.97l6.518-3.76a1 1 0 000-1.74z" />
                                </svg>
                                Ver película
                            </a>
                        </template>

                        <template x-if="data.type === 'series'">
                            <button @click="showEpisodes(data.id)"
                                class="flex items-center gap-2 border border-purple-600 hover:border-purple-400 text-purple-400 hover:text-purple-200 px-4 py-2 rounded-md font-semibold transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9.75 9.75L14.25 12L9.75 14.25V9.75Z" />
                                </svg>
                                Ver episodios
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- ⚙️ Alpine lógica --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('previewModal', ({ addonBase, manifestId }) => ({
                open: false,
                data: null,
                lang: 'en',
                translatedDescription: '',

                show(item) {
                    this.data = structuredClone(item);
                    this.open = true;
                    this.translatedDescription = item.description || '';
                    this.enrich(item);
                },

                close() { this.open = false; this.data = null; },

                async enrich(item) {
                    try {
                        const res = await fetch(`${addonBase}/catalog/${item.type}/${manifestId}.json`);
                        const json = await res.json();
                        const found = json.metas?.find(m => m.id === item.id);
                        if (!found) return;
                        this.data = {
                            ...this.data,
                            imdbRating: found.imdbRating || '',
                            releaseInfo: found.releaseInfo || found.year || '',
                            genres: found.genres || found.genre || [],
                            director: found.director || [],
                            cast: found.cast || [],
                            trailer: found.trailers?.[0]?.source || found.trailerStreams?.[0]?.ytId || '',
                            background: found.background || item.background,
                            description: found.description || item.description
                        };
                    } catch (e) {
                        console.error('❌ Error enriqueciendo datos:', e);
                    }
                },

                async translate() {
                    if (!this.data?.description) return this.translatedDescription = 'Sin descripción disponible.';
                    if (this.lang === 'en') return this.translatedDescription = this.data.description;
                    try {
                        const res = await fetch(`https://api.mymemory.translated.net/get?q=${encodeURIComponent(this.data.description)}&langpair=en|es`);
                        const json = await res.json();
                        this.translatedDescription = json.responseData.translatedText || this.data.description;
                    } catch {
                        this.translatedDescription = this.data.description;
                    }
                },

                async showEpisodes(id) {
                    try {
                        const url = `${addonBase}/meta/series/${id}.json`;
                        const res = await fetch(url);
                        const json = await res.json();

                        if (!json?.meta?.videos?.length) {
                            alert('No se encontraron episodios disponibles.');
                            return;
                        }

                        const eps = json.meta.videos;
                        let list = eps.map(ep => `
                            <li class="border-b border-indigo-900/30 py-2 hover:bg-indigo-900/20 cursor-pointer px-3 rounded-md"
                                onclick="window.location.href='/watch/series/${id}?ep=${ep.id}'">
                                <span class='font-semibold text-indigo-300'>E${ep.episode || '?'}</span> — 
                                <span class='text-gray-200'>${ep.name || 'Sin título'}</span>
                            </li>
                        `).join('');

                        const modal = document.createElement('div');
                        modal.className = 'fixed inset-0 bg-black/80 z-[60] flex items-center justify-center p-4';
                        modal.innerHTML = `
                            <div class='bg-[#121426] rounded-xl w-full max-w-2xl p-6 overflow-y-auto max-h-[80vh]'>
                                <h2 class='text-xl font-semibold text-indigo-400 mb-4'>Episodios</h2>
                                <ul class='space-y-2'>${list}</ul>
                                <div class='mt-6 text-right'>
                                    <button class='px-4 py-2 border border-gray-600 rounded-md text-gray-400 hover:text-gray-200 hover:border-gray-400 transition'>Cerrar</button>
                                </div>
                            </div>`;
                        document.body.appendChild(modal);
                        modal.querySelector('button').addEventListener('click', () => modal.remove());
                    } catch (err) {
                        console.error('❌ Error cargando episodios:', err);
                    }
                },
            }));
        });
    </script>
</div>