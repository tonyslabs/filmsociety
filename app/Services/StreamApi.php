<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StreamApi
{
    private string $base;

    public function __construct()
    {
        $this->base = rtrim(config('stream.base'), '/');
    }

    /** Compat: varias instancias de Stremio/AIO leen "extra" como JSON en query */
    private function withExtraCompat(array $extras): array
    {
        if (empty($extras))
            return [];
        // mantenemos ambos formatos: plano y empaquetado en extra
        return $extras + ['extra' => json_encode($extras, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)];
    }

    private function fetch(string $key, string $path, array $params = [], int $ttl = 900): ?array
    {
        return Cache::remember($key, $ttl, function () use ($path, $params) {
            try {
                $url = "{$this->base}{$path}";
                $response = Http::timeout(20)
                    ->retry(2, 500)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Safari/537.36',
                        'Accept' => 'application/json',
                        'Referer' => 'https://strem.io',
                    ])
                    ->get($url, $params);

                if ($response->successful())
                    return $response->json();

                Log::warning('StreamApi fetch fail', [
                    'url' => $url,
                    'params' => $params,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            } catch (\Throwable $e) {
                Log::error("StreamApi exception {$path}: {$e->getMessage()}");
                return null;
            }
        });
    }

    private function safeImage(?string $url): string
    {
        if (empty($url) || !str_starts_with($url, 'http'))
            return asset('img/no-poster.png');
        if (preg_match('/(tmdb\.org|imdb\.com|metahub\.space|aiostreams[^\/]*\.)/i', $url))
            return $url;
        return asset('img/no-poster.png');
    }

    public function manifest(): ?array
    {
        return $this->fetch('manifest', '/manifest.json', [], 3600);
    }

    public function catalog(string $type, string $id, array $extras = [], int $limit = 30): array
    {
        $params = $this->withExtraCompat($extras);
        $key = "catalog_{$type}_{$id}_" . md5(json_encode($params));
        $data = $this->fetch($key, "/catalog/{$type}/{$id}.json", $params);

        if (!$data || empty($data['metas'])) {
            Log::notice("Catálogo vacío {$type}/{$id}", ['extras' => $params]);
            return [];
        }

        return collect($data['metas'])
            ->map(fn($item) => [
                'id' => $item['id'] ?? '',
                'type' => $item['type'] ?? $type,
                'name' => $item['name'] ?? 'Sin título',
                'description' => $item['description'] ?? '',
                'poster' => $this->safeImage($item['poster'] ?? null),
                'background' => $this->safeImage($item['background'] ?? ($item['poster'] ?? null)),
                'logo' => $this->safeImage($item['logo'] ?? null),
                'rating' => $item['imdbRating'] ?? null,
                'year' => $item['releaseInfo'] ?? null,
            ])
            ->filter(fn($i) => !str_contains($i['poster'], 'no-poster.png'))
            ->take($limit)
            ->values()
            ->toArray();
    }

    public function meta(string $type, string $id): ?array
    {
        return $this->fetch("meta_{$type}_{$id}", "/meta/{$type}/{$id}.json");
    }

    public function streams(string $type, string $id): array
    {
        return $this->fetch("streams_{$type}_{$id}", "/stream/{$type}/{$id}.json", [], 600) ?? [];
    }

    public function subtitles(string $type, string $id): array
    {
        return $this->fetch("subs_{$type}_{$id}", "/subtitles/{$type}/{$id}.json", [], 1800) ?? [];
    }

    public function getNewReleases(?string $year = null): array
    {
        $year = $year ?? date('Y');
        return [
            'movies' => $this->catalog('movie', '713e3b0.year', ['genre' => $year], 40),
            'series' => $this->catalog('series', '713e3b0.year', ['genre' => $year], 40),
        ];
    }

    public function getPopular(): array
    {
        return [
            'movies' => $this->catalog('movie', '713e3b0.top', [], 40),
            'series' => $this->catalog('series', '713e3b0.top', [], 40),
        ];
    }

    public function getFeatured(string $genre = 'Action'): array
    {
        return [
            'movies' => $this->catalog('movie', '713e3b0.imdbRating', ['genre' => $genre], 40),
            'series' => $this->catalog('series', '713e3b0.imdbRating', ['genre' => $genre], 40),
        ];
    }
}
