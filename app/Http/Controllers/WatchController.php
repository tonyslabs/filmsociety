<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WatchController extends Controller
{
    // Heurística rápida para decidir si el navegador lo podrá reproducir
    private function looksBrowserPlayable(?string $name, ?string $desc): bool
    {
        $t = strtolower(($name ?? '') . ' ' . ($desc ?? ''));
        $isMp4 = Str::contains($t, ['.mp4 ', '.mp4', ' mp4 ']);
        $isMkv = Str::contains($t, ['.mkv', ' mkv ']);
        $hasHevc = Str::contains($t, ['hevc', 'x265', 'h.265', 'h265']);
        $hasAv1 = Str::contains($t, ['av1']);
        $badAudio = Str::contains($t, ['dts', 'truehd']); // navegadores no reproducen DTS/TrueHD
        // Navegadores suelen ir bien con MP4 + H.264/AAC. MKV/HEVC/AV1/DTS suelen fallar.
        return $isMp4 && !$hasHevc && !$hasAv1 && !$badAudio;
    }

    public function index($type, $id, Request $request)
    {
        $base = rtrim(config('stream.base'), '/');
        $meta = [];
        $streams = [];

        try {
            // Recuperar metadata precisa del item
            $metaResponse = Http::timeout(20)->get("$base/meta/$type/$id.json");
            if ($metaResponse->successful()) {
                $meta = $metaResponse->json('meta', []);
            }

            // Recuperar streams disponibles
            $streamResponse = Http::timeout(30)->get("$base/stream/$type/$id.json");
            if ($streamResponse->successful()) {
                $streams = collect($streamResponse->json('streams', []))
                    ->map(function ($s) {
                        $desc = $s['description'] ?? '';
                        preg_match('/Quality:\s*([^\n]+)/i', $desc, $q);
                        preg_match('/Size:\s*([^\n|]+)/i', $desc, $sz);
                        preg_match('/Language:\s*([^\n|]+)/i', $desc, $lang);
                        preg_match('/Type:\s*([^\n|]+)/i', $desc, $tp);
                        preg_match('/Seeders:\s*(\d+)/i', $desc, $seeders);

                        $s['quality'] = trim($q[1] ?? 'Unknown');
                        $s['size'] = trim($sz[1] ?? 'N/A');
                        $s['language'] = trim($lang[1] ?? 'Unknown');
                        $s['type'] = trim($tp[1] ?? 'Unknown');
                        $s['seeders'] = trim($seeders[1] ?? '');
                        return $s;
                    })
                    ->values()
                    ->toArray();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return view('watch', compact('type', 'id', 'meta', 'streams'));
    }

    public function play($type, $id, $index)
    {
        $base = rtrim(config('stream.base'), '/');
        $torrServer = env('TORRSERVER_URL', 'http://127.0.0.1:8090');
        $alldebridKey = env('ALLDEBRID_KEY');
        $alldebridAgent = env('ALLDEBRID_AGENT', 'filmsociety');

        $res = Http::timeout(25)->get("$base/stream/$type/$id.json");
        $streams = $res->successful() ? $res->json('streams', []) : [];
        $stream = $streams[$index] ?? null;
        if (!$stream)
            abort(404, 'Stream no disponible');

        $originalUrl = $stream['url'] ?? '';
        $infoHash = $stream['infoHash'] ?? null;
        $name = $stream['behaviorHints']['filename'] ?? $stream['name'] ?? '';
        $desc = $stream['description'] ?? '';

        $isEmbed = false;
        $step = 'none';
        $playUrl = '';

        try {
            // 1) P2P con infoHash → intentar AllDebrid (sin servicios externos locales)
            if ($infoHash) {
                $magnetRaw = 'magnet:?xt=urn:btih:' . $infoHash;

                if (!empty($alldebridKey)) {
                    try {
                        // Subir magnet
                        $upload = Http::timeout(15)->get('https://api.alldebrid.com/v4/magnet/upload', [
                            'agent' => $alldebridAgent,
                            'apikey' => $alldebridKey,
                            'magnets[]' => $magnetRaw,
                        ]);
                        $mid = null;
                        if ($upload->successful()) {
                            $uj = $upload->json();
                            $magArr = $uj['data']['magnets'] ?? null;
                            if (is_array($magArr) && !empty($magArr[0]['id'])) {
                                $mid = $magArr[0]['id'];
                            }
                        }

                        // Poll de estado y obtención de links
                        $links = [];
                        if ($mid) {
                            for ($i = 0; $i < 6; $i++) { // ~12s total
                                usleep(2000 * 1000);
                                $st = Http::timeout(12)->get('https://api.alldebrid.com/v4/magnet/status', [
                                    'agent' => $alldebridAgent,
                                    'apikey' => $alldebridKey,
                                    'id' => $mid,
                                ]);
                                if (!$st->successful()) continue;
                                $sj = $st->json();
                                $m = $sj['data']['magnets'][0] ?? ($sj['data']['magnet'] ?? null);
                                if (!$m) continue;
                                $ready = ($m['status'] ?? '') === 'Ready' || ($m['ready'] ?? false) === true || !empty($m['links']);
                                if ($ready) {
                                    $links = $m['links'] ?? [];
                                    break;
                                }
                            }
                        }

                        // Desbloquear y elegir mejor enlace (mp4 si es posible)
                        $unlocked = null;
                        foreach ($links as $lnk) {
                            $link = is_array($lnk) ? ($lnk['link'] ?? ($lnk['download'] ?? '')) : (string) $lnk;
                            if (!$link) continue;
                            $unlock = Http::timeout(12)->get('https://api.alldebrid.com/v4/link/unlock', [
                                'agent' => $alldebridAgent,
                                'apikey' => $alldebridKey,
                                'link' => $link,
                            ]);
                            if ($unlock->successful()) {
                                $uj = $unlock->json();
                                $direct = $uj['data']['link'] ?? null;
                                if ($direct) {
                                    $unlocked = $direct;
                                    // Preferir mp4 si el path lo sugiere
                                    if (str_ends_with(strtolower(parse_url($direct, PHP_URL_PATH) ?? ''), '.mp4')) {
                                        break;
                                    }
                                }
                            }
                        }

                        if ($unlocked) {
                            $playUrl = url('/proxy/stream?url=' . urlencode($unlocked));
                            $isEmbed = false;
                            $step = 'alldebrid_direct';
                        }
                    } catch (\Throwable $e) {
                        Log::warning('AllDebrid error', ['err' => $e->getMessage()]);
                    }
                }

                // Si AllDebrid no dio URL directa, intentar WebTorrent en el cliente y fallback a Webtor
                if (empty($playUrl)) {
                    $trackers = [
                        'wss://tracker.openwebtorrent.com',
                        'wss://tracker.btorrent.xyz',
                        'wss://tracker.fastcast.nz',
                        'wss://tracker.webtorrent.dev'
                    ];
                    $magnet = $magnetRaw;
                    foreach ($trackers as $tr) {
                        $magnet .= '&tr=' . urlencode($tr);
                    }

                    $playUrl = '';
                    $isEmbed = false; // renderizamos <video> y dejamos que el cliente WebTorrent adjunte el stream
                    $step = isset($step) && $step === 'alldebrid_direct' ? $step : 'webtorrent_client';

                    // Añadir datos al array de salida para el frontend
                    $stream['webtorrent_magnet'] = $magnet;
                    $stream['fallback_embed'] = "https://webtor.io/embed?magnet=" . urlencode($magnetRaw);
                }
            }
            // 2) Debrid: evita AllDebrid unlock de strem.fun (no soportado). Fallback embed.
            elseif (Str::contains($originalUrl, ['torrentio.strem.fun', 'strem.fun'])) {
                $playUrl = "https://webtor.io/embed?url=" . urlencode($originalUrl);
                $isEmbed = true;
                $step = 'debrid_fallback_webtor';
            }
            // 3) HTTP directo → reproducir siempre en <video>; HLS se maneja en el frontend
            elseif (Str::startsWith($originalUrl, 'http')) {
                $playUrl = url('/proxy/stream?url=' . urlencode($originalUrl));
                $isEmbed = false;
                $step = 'direct_http';
            } else {
                abort(400, 'Fuente no soportada');
            }
        } catch (\Throwable $e) {
            Log::error('play() exception', ['err' => $e->getMessage()]);
            $playUrl = $originalUrl ?: '';
            $isEmbed = true;
            $step = 'exception_fallback';
        }

        $stream['url'] = $playUrl;
        $stream['isEmbed'] = $isEmbed;
        $stream['debug'] = [
            'step' => $step,
            'original_url' => $originalUrl,
        ];

        Log::info('WATCH_PLAY', [
            'step' => $step,
            'infoHash' => $infoHash,
            'playUrl' => $playUrl,
            'isEmbed' => $isEmbed,
            'name' => $name,
        ]);

        return view('watch-player', compact('stream', 'type', 'id'));
    }

    private function isAllowedProxyUrl(string $url): bool
    {
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            return false;
        }

        $scheme = strtolower($parsed['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $ts = parse_url(env('TORRSERVER_URL', 'http://127.0.0.1:8090')) ?: [];
        $tsHost = $ts['host'] ?? null;
        $host = $parsed['host'];

        // Permitir siempre el host configurado de TorrServer
        if ($tsHost && strcasecmp($host, $tsHost) === 0) {
            return true;
        }

        // Bloquear explícitamente localhost y dominios .local
        if (in_array(strtolower($host), ['localhost', '127.0.0.1'], true) || str_ends_with(strtolower($host), '.local')) {
            return false;
        }

        // Si es IP literal, bloquear rangos privados/reservados
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        // Hosts públicos permitidos por defecto (listas dinámicas se pueden añadir aquí)
        return true;
    }

    public function proxy(Request $request)
    {
        $url = $request->query('url');
        if (!$url)
            abort(400, 'Missing URL');

        $range = $request->header('Range', '');

        // Validaciones básicas para mitigar SSRF
        if (!$this->isAllowedProxyUrl($url)) {
            return response('URL no permitida', 400);
        }

        try {
            $client = Http::withOptions([
                'stream' => true,
                // Mantener timeout 0 para streaming continuo
                'timeout' => 0,
                'verify' => filter_var(env('STREAM_PROXY_VERIFY', true), FILTER_VALIDATE_BOOLEAN),
            ])->withHeaders(array_filter([
                            'User-Agent' => 'FreeWatchProxy/2.0',
                            'Range' => $range ?: 'bytes=0-', // favorece 206 en TS
                        ]));

            $up = $client->get($url);
            $status = $up->status();
            $headers = $up->headers();

            $forward = [];
            foreach ([
                'Content-Type',
                'Content-Length',
                'Content-Range',
                'Accept-Ranges',
                'Cache-Control',
                'Pragma',
                'ETag',
                'Last-Modified',
                'Content-Disposition'
            ] as $h) {
                if (!empty($headers[$h][0]))
                    $forward[$h] = $headers[$h][0];
            }
            if (!isset($forward['Accept-Ranges']))
                $forward['Accept-Ranges'] = 'bytes';
            if (!isset($forward['Content-Type']))
                $forward['Content-Type'] = $headers['content-type'][0] ?? 'application/octet-stream';

            $forward['X-Proxy-Upstream-Status'] = (string) $status;

            Log::info('PROXY_UPSTREAM', [
                'url' => $url,
                'range' => $range,
                'status' => $status,
                'ctype' => $forward['Content-Type'] ?? null,
                'crange' => $forward['Content-Range'] ?? null,
                'clen' => $forward['Content-Length'] ?? null,
            ]);

            return response()->stream(function () use ($up) {
                $body = $up->toPsrResponse()->getBody();
                while (!$body->eof()) {
                    echo $body->read(8192);
                    flush();
                }
            }, $status, $forward);

        } catch (\Throwable $e) {
            Log::error('PROXY_ERROR', ['url' => $url, 'err' => $e->getMessage()]);
            return response("Proxy error: " . $e->getMessage(), 500);
        }
    }
}
