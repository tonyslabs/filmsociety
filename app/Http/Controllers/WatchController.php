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
            $metaResponse = Http::timeout(20)->get("$base/catalog/$type/713e3b0.top.json");
            if ($metaResponse->successful()) {
                $data = $metaResponse->json();
                $meta = collect($data['metas'] ?? [])->firstWhere('id', $id) ?? [];
            }

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
            // 1) P2P con infoHash → TorrServer si el archivo parece reproducible en navegador
            if ($infoHash) {
                $preferTS = $this->looksBrowserPlayable($name, $desc);
                $ping = Http::timeout(2)->get("$torrServer/echo");
                if ($preferTS && $ping->successful()) {
                    // Registra magnet (best-effort) y elige archivo
                    $fileIndex = 0;
                    try {
                        $reg = Http::timeout(15)->get("$torrServer/torrents/add", [
                            'link' => "magnet:?xt=urn:btih:$infoHash",
                        ]);
                        if ($reg->successful()) {
                            $tor = $reg->json('torrent') ?? [];
                            $files = $tor['files'] ?? [];
                            if ($files) {
                                // Escoge el más grande con extensión reproducible
                                $valid = array_values(array_filter(
                                    $files,
                                    fn($f) =>
                                    isset($f['path']) && preg_match('/\.(mp4)$/i', $f['path'])
                                ));
                                if ($valid) {
                                    usort($valid, fn($a, $b) => ($b['length'] ?? 0) <=> ($a['length'] ?? 0));
                                    $fileIndex = $valid[0]['id'] ?? 0;
                                } else {
                                    // si no hay mp4, forzar fallback a Webtor
                                    $fileIndex = null;
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('TS register fail', ['err' => $e->getMessage()]);
                    }

                    if ($fileIndex !== null) {
                        $tsUrl = "$torrServer/stream/file?index=$fileIndex&play=true";
                        $playUrl = url('/proxy/stream?url=' . urlencode($tsUrl));
                        $isEmbed = false;
                        $step = "torrserver_file_stream_index_$fileIndex";
                    } else {
                        $playUrl = "https://webtor.io/embed?magnet=" . urlencode("magnet:?xt=urn:btih:$infoHash");
                        $isEmbed = true;
                        $step = 'fallback_webtor_non_mp4';
                    }
                } else {
                    $playUrl = "https://webtor.io/embed?magnet=" . urlencode("magnet:?xt=urn:btih:$infoHash");
                    $isEmbed = true;
                    $step = $preferTS ? 'torrserver_unreachable' : 'fallback_webtor_codec';
                }
            }
            // 2) Debrid: evita AllDebrid unlock de strem.fun (no soportado). Fallback embed.
            elseif (Str::contains($originalUrl, ['torrentio.strem.fun', 'strem.fun'])) {
                $playUrl = "https://webtor.io/embed?url=" . urlencode($originalUrl);
                $isEmbed = true;
                $step = 'debrid_fallback_webtor';
            }
            // 3) HTTP directo
            elseif (Str::startsWith($originalUrl, 'http')) {
                // Solo proxiar directo si parece H.264/AAC
                if ($this->looksBrowserPlayable($name, $desc) || Str::endsWith($originalUrl, '.mp4')) {
                    $playUrl = url('/proxy/stream?url=' . urlencode($originalUrl));
                    $isEmbed = false;
                    $step = 'direct_http';
                } else {
                    $playUrl = "https://webtor.io/embed?url=" . urlencode($originalUrl);
                    $isEmbed = true;
                    $step = 'direct_http_fallback_webtor';
                }
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

    public function proxy(Request $request)
    {
        $url = $request->query('url');
        if (!$url)
            abort(400, 'Missing URL');

        $range = $request->header('Range', '');

        try {
            $client = Http::withOptions([
                'stream' => true,
                'verify' => false,
                'timeout' => 0,
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

            $forward['Access-Control-Allow-Origin'] = '*';
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
