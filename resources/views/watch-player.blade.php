@extends('layouts.app')

@section('title', $stream['behaviorHints']['filename'] ?? $stream['name'])

@section('content')
    <div x-data="{ infoOpen: false }" class="px-6 sm:px-10 lg:px-16 xl:px-20 py-6">
        <!-- Header: title + meta + info button -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-300">
                    ▶
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl md:text-2xl font-semibold text-gray-100">
                        {{ $stream['name'] ?? 'Stream' }}
                    </h1>
                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-gray-300/80">
                        @if(!empty($stream['quality']))
                            <span class="px-2 py-0.5 rounded bg-white/10 border border-white/10">{{ $stream['quality'] }}</span>
                        @endif
                        @if(!empty($stream['size']))
                            <span class="px-2 py-0.5 rounded bg-white/10 border border-white/10">{{ $stream['size'] }}</span>
                        @endif
                        @if(!empty($stream['type']))
                            <span class="px-2 py-0.5 rounded bg-white/10 border border-white/10">{{ $stream['type'] }}</span>
                        @endif
                        @if(!empty($stream['seeders']))
                            <span class="px-2 py-0.5 rounded bg-white/10 border border-white/10">Seeders: {{ $stream['seeders'] }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <button @click="infoOpen = true" class="inline-flex items-center justify-center w-10 h-10 rounded-full 
                            bg-white/5 hover:bg-white/10 border border-white/10 text-gray-200 transition"
                    title="Información">
                i
            </button>
        </div>

        <!-- Player Card -->
        <div id="playerRoot" class="rounded-2xl bg-[#0c0f1e]/80 border border-indigo-900/30 shadow-xl overflow-hidden">
            <div class="relative">
                @if ($stream['isEmbed'])
                    <div id="playArea" class="aspect-video w-full bg-black">
                        <iframe id="embedFrame" class="w-full h-full" allowfullscreen frameborder="0"
                                sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>
                    </div>
                @else
                    <div id="playArea" class="aspect-video w-full bg-black">
                        <video id="videoPlayer" class="w-full h-full" controls preload="none"></video>
                    </div>
                    <div class="px-4 pb-4">
                        <div class="mt-3 h-2 w-full rounded bg-white/10 overflow-hidden">
                            <div id="bufferBar" class="h-2 bg-gradient-to-r from-indigo-500 to-purple-500" style="width:0%"></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs text-gray-400">
                            <span id="bufferText">Buffer: 0%</span>
                            <span id="playText">Reproducción: 0%</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Info Modal -->
        <div x-show="infoOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4" @click.self="infoOpen=false">
            <div class="w-full max-w-2xl rounded-2xl bg-[#12172b] border border-indigo-900/40 shadow-2xl overflow-hidden">
                <div class="px-5 py-4 flex items-center justify-between border-b border-indigo-900/40">
                    <h3 class="text-lg font-semibold text-gray-100">Detalles del stream</h3>
                    <button @click="infoOpen=false" class="w-9 h-9 rounded-full bg-white/5 hover:bg-white/10 text-gray-200 flex items-center justify-center">✕</button>
                </div>
                <div class="p-5 space-y-4 text-sm text-gray-300">
                    <div>
                        <div class="text-gray-400 text-xs uppercase">Título</div>
                        <div class="font-medium text-gray-100">{{ $stream['name'] ?? 'Stream' }}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-gray-400 text-xs uppercase">Calidad</div>
                            <div>{{ $stream['quality'] ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-400 text-xs uppercase">Tamaño</div>
                            <div>{{ $stream['size'] ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-400 text-xs uppercase">Tipo</div>
                            <div>{{ $stream['type'] ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-400 text-xs uppercase">Seeders</div>
                            <div>{{ $stream['seeders'] ?? '—' }}</div>
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-400 text-xs uppercase mb-1">Descripción</div>
                        <div class="leading-relaxed">{!! nl2br(e($stream['description'] ?? 'Sin descripción')) !!}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <div class="text-gray-400 text-xs uppercase">Paso</div>
                            <div class="truncate">{{ $stream['debug']['step'] ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-400 text-xs uppercase">URL Origen</div>
                            <div class="truncate" title="{{ $stream['debug']['original_url'] ?? '—' }}">{{ $stream['debug']['original_url'] ?? '—' }}</div>
                        </div>
                        <div class="md:col-span-2">
                            <div class="text-gray-400 text-xs uppercase">URL Reproducción</div>
                            <div class="truncate" title="{{ $stream['url'] ?? '—' }}">{{ $stream['url'] ?? '—' }}</div>
                        </div>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-indigo-900/40 flex justify-end">
                    <button @click="infoOpen=false" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-500 transition">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Carga de scripts con fallback a múltiples CDNs
        function loadScriptSequential(urls, onLoad, onError){
            let i = 0;
            const tryNext = () => {
                if (i >= urls.length) { onError && onError(); return; }
                const s = document.createElement('script');
                s.src = urls[i++];
                s.async = true;
                s.onload = () => onLoad && onLoad();
                s.onerror = tryNext;
                document.head.appendChild(s);
            };
            tryNext();
        }
        // hls.js (mantener por si el navegador no soporta HLS nativo)
        (function addHlsScript(){
            const urls = [
                'https://cdn.jsdelivr.net/npm/hls.js@1.4.12/dist/hls.min.js',
                'https://unpkg.com/hls.js@1.4.12/dist/hls.min.js'
            ];
            loadScriptSequential(urls, null, null);
        })();
        document.addEventListener('DOMContentLoaded', () => {
            console.group('%cFreeWatch Debug', 'color:cyan');
            console.log('Stream type:', '{{ $stream['type'] ?? 'N/A' }}');
            console.log('Step:', '{{ $stream['debug']['step'] ?? 'none' }}');
            console.log('Original URL:', '{{ $stream['debug']['original_url'] ?? '' }}');
            console.log('Resolved URL:', '{{ $stream['url'] ?? '' }}');
            console.log('Is Embed:', {{ $stream['isEmbed'] ? 'true' : 'false' }});
            console.groupEnd();

            const streamUrl = @json($stream['url']);
            const magnet = @json($stream['webtorrent_magnet'] ?? null);
            const fallbackEmbed = @json($stream['fallback_embed'] ?? null);

            if ('{{ $stream['isEmbed'] ? 'true' : 'false' }}' === 'true') {
                document.getElementById('embedFrame').src = streamUrl;
                return;
            }

            const v = document.getElementById('videoPlayer');
            const bufferBar = document.getElementById('bufferBar');
            const bufferText = document.getElementById('bufferText');
            const playText = document.getElementById('playText');

            function playViaUrl(){
                const isHls = typeof streamUrl === 'string' && /\.m3u8(\?|$)/i.test(streamUrl);
                if (isHls && window.Hls && window.Hls.isSupported()) {
                    const hls = new window.Hls({
                        maxMaxBufferLength: 120,
                        fragLoadingRetryDelay: 1000,
                        enableWorker: true,
                    });
                    hls.loadSource(streamUrl);
                    hls.attachMedia(v);
                    hls.on(window.Hls.Events.MANIFEST_PARSED, function(){ v.play().catch(()=>{}); });
                } else if (typeof streamUrl === 'string' && streamUrl) {
                    v.src = streamUrl;
                    v.load();
                }
            }

            function ensureWebTorrent(ready, fail){
                if (window.WebTorrent && (typeof window.WebTorrent === 'function' || window.WebTorrent.default)) return ready();
                (async () => {
                    try {
                        const urls = [
                            'https://esm.sh/webtorrent@2.6.5',
                            'https://esm.run/webtorrent@2.6.5'
                        ];
                        let mod = null;
                        for (const u of urls) {
                            try { mod = await import(u); if (mod) break; } catch { /* try next */ }
                        }
                        if (!mod) throw new Error('ESM import failed');
                        // Normalizar en window.WebTorrent como ctor directo
                        window.WebTorrent = mod.default || mod.WebTorrent || mod;
                        ready();
                    } catch (e) {
                        console.warn('WebTorrent ESM import failed', e);
                        fail && fail(e);
                    }
                })();
            }

            // Prioridad: WebTorrent (si hay magnet) → HLS/HTTP → Fallback a embed si no hay URL
            if (magnet) {
                ensureWebTorrent(() => {
                try {
                    const WebTorrentCtor = (window.WebTorrent && (typeof window.WebTorrent === 'function' ? window.WebTorrent : window.WebTorrent.default)) || null;
                    if (!WebTorrentCtor) throw new Error('WebTorrent global not found');
                    const client = new WebTorrentCtor();
                    let attached = false;
                    let started = false;
                    const attachTimeout = setTimeout(() => {
                        if (!started && fallbackEmbed) {
                            console.warn('WebTorrent timeout, fallback → Webtor');
                            mountEmbed(fallbackEmbed);
                        }
                    }, 12000);

                    client.add(magnet, (torrent) => {
                        // Elegir el archivo reproducible: mp4/webm preferido
                        const files = torrent.files || [];
                        let candidates = files.filter(f => /\.(mp4|webm)$/i.test(f.name));
                        if (!candidates.length) {
                            console.warn('No hay MP4/WEBM reproducibles vía WebTorrent, fallback → Webtor');
                            if (fallbackEmbed) mountEmbed(fallbackEmbed);
                            return;
                        }
                        candidates.sort((a,b)=> (b.length||0) - (a.length||0));
                        const file = candidates[0];

                        file.renderTo(v, { autoplay: true }, (err) => {
                            if (err) {
                                console.error('WebTorrent render error', err);
                                if (fallbackEmbed) mountEmbed(fallbackEmbed);
                                return;
                            }
                            attached = true;
                            // Marcar inicio al primer playing del <video>
                            v.addEventListener('playing', () => { started = true; clearTimeout(attachTimeout); }, { once: true });
                            clearTimeout(attachTimeout);
                            console.log('▶ Reproduciendo vía WebTorrent:', file.name);
                        });

                        torrent.on('error', (e)=>{
                            console.error('WebTorrent torrent error', e);
                            if (fallbackEmbed) mountEmbed(fallbackEmbed);
                        });

                        // Progreso visual aproximado
                        const update = () => {
                            if (!torrent || !torrent.progress) return;
                            const pct = Math.max(0, Math.min(100, torrent.progress * 100));
                            bufferBar.style.width = pct.toFixed(1) + '%';
                            bufferText.textContent = 'Buffer: ' + pct.toFixed(1) + '%';
                        };
                        setInterval(update, 1500);
                    });
                } catch (e) {
                    console.error('WebTorrent init error', e);
                    if (fallbackEmbed) mountEmbed(fallbackEmbed);
                }
                }, () => {
                    console.warn('No se pudo cargar WebTorrent, fallback');
                    if (fallbackEmbed) mountEmbed(fallbackEmbed); else playViaUrl();
                });
            } else {
                playViaUrl();
            }

            function mountEmbed(url) {
                const area = document.getElementById('playArea');
                if (!area) return;
                if (updateTimer) { clearInterval(updateTimer); updateTimer = null; }
                area.innerHTML = `<iframe class="w-full h-full" allowfullscreen frameborder="0" sandbox="allow-scripts allow-same-origin allow-forms allow-popups" src="${url}"></iframe>`;
            }

            let autoStarted = false;
            let lastBufferedPct = -1;
            let updateTimer = null;

            v.addEventListener('loadedmetadata', () => console.log('✅ Metadata cargada'));
            v.addEventListener('canplay', () => console.log('✅ Listo para reproducir'));
            v.addEventListener('play', () => console.log('▶️ Reproducción iniciada'));
            v.addEventListener('waiting', () => console.log('⏳ Esperando buffer…'));
            v.addEventListener('stalled', () => console.warn('⚠️ Buffer detenido'));
            v.addEventListener('error', e => console.error('❌ Error de video:', e));

            function updateBuffer() {
                if (!v.duration || !isFinite(v.duration)) return;
                if (v.buffered.length === 0) return;

                let end = 0;
                for (let i = 0; i < v.buffered.length; i++) end = Math.max(end, v.buffered.end(i));
                const pct = Math.max(0, Math.min(100, (end / v.duration) * 100));
                if (pct !== lastBufferedPct) {
                    bufferBar.style.width = pct.toFixed(1) + '%';
                    bufferText.textContent = 'Buffer: ' + pct.toFixed(1) + '%';
                    console.log(`📶 Buffer: ${pct.toFixed(1)}%`);
                    lastBufferedPct = pct;
                }
                if (!autoStarted && pct >= 20) {
                    autoStarted = true;
                    v.play().catch(() => { });
                    console.log('▶️ Auto play at', pct.toFixed(1) + '%');
                }
            }

            v.addEventListener('progress', updateBuffer);
            updateTimer = setInterval(updateBuffer, 1500);

            v.addEventListener('timeupdate', () => {
                if (!v.duration || !isFinite(v.duration)) return;
                const played = Math.max(0, Math.min(100, (v.currentTime / v.duration) * 100));
                playText.textContent = 'Reproducción: ' + played.toFixed(1) + '%';
            });
        });
    </script>
@endsection
