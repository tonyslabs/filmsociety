@extends('layouts.app')

@section('title', $stream['behaviorHints']['filename'] ?? $stream['name'])

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3 rounded-circle bg-primary bg-opacity-25 p-3">
                        <span class="text-primary fs-4">▶</span>
                    </div>
                    <div>
                        <h5 class="mb-0 text-light">{{ $stream['name'] ?? 'Stream' }}</h5>
                        <div class="text-muted small">
                            {{ $stream['quality'] ?? '—' }} · {{ $stream['size'] ?? '—' }} ·
                            {{ $stream['type'] ?? '—' }}
                            {{ isset($stream['seeders']) ? "· Seeders: " . $stream['seeders'] : '' }}
                        </div>
                    </div>
                </div>

                <div class="card bg-dark border-0 shadow-sm p-3 mb-3">
                    @if ($stream['isEmbed'])
                        <div class="ratio ratio-16x9">
                            <iframe id="embedFrame" class="rounded-3" allowfullscreen frameborder="0"
                                sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>
                        </div>
                    @else
                        <div class="ratio ratio-16x9">
                            <video id="videoPlayer" class="rounded-3 w-100 h-100" controls preload="none"
                                style="background:#000;"></video>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height:10px;">
                                <div id="bufferBar" class="progress-bar bg-secondary" role="progressbar" style="width:0%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1 small text-muted">
                                <span id="bufferText">Buffer: 0%</span>
                                <span id="playText">Reproducción: 0%</span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-3 text-secondary small">
                    <strong>Descripción:</strong><br>
                    {!! nl2br(e($stream['description'] ?? 'Sin descripción')) !!}
                </div>

                <div class="mt-2 text-muted small">
                    <strong>Paso:</strong> {{ $stream['debug']['step'] ?? '—' }}<br>
                    <strong>URL Origen:</strong> {{ $stream['debug']['original_url'] ?? '—' }}<br>
                    <strong>URL Repro:</strong> {{ $stream['url'] ?? '—' }}
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            console.group('%cFreeWatch Debug', 'color:cyan');
            console.log('Stream type:', '{{ $stream['type'] ?? 'N/A' }}');
            console.log('Step:', '{{ $stream['debug']['step'] ?? 'none' }}');
            console.log('Original URL:', '{{ $stream['debug']['original_url'] ?? '' }}');
            console.log('Resolved URL:', '{{ $stream['url'] ?? '' }}');
            console.log('Is Embed:', {{ $stream['isEmbed'] ? 'true' : 'false' }});
            console.groupEnd();

            const streamUrl = @json($stream['url']);

            if ('{{ $stream['isEmbed'] ? 'true' : 'false' }}' === 'true') {
                document.getElementById('embedFrame').src = streamUrl;
                return;
            }

            const v = document.getElementById('videoPlayer');
            const bufferBar = document.getElementById('bufferBar');
            const bufferText = document.getElementById('bufferText');
            const playText = document.getElementById('playText');

            v.src = streamUrl;
            v.load();

            let autoStarted = false;
            let lastBufferedPct = -1;

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
            setInterval(updateBuffer, 1500);

            v.addEventListener('timeupdate', () => {
                if (!v.duration || !isFinite(v.duration)) return;
                const played = Math.max(0, Math.min(100, (v.currentTime / v.duration) * 100));
                playText.textContent = 'Reproducción: ' + played.toFixed(1) + '%';
            });
        });
    </script>
@endsection