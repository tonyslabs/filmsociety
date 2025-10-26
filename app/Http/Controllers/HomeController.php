<?php

namespace App\Http\Controllers;

use App\Services\StreamApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    protected StreamApi $stream;

    public function __construct(StreamApi $stream)
    {
        $this->stream = $stream;
    }

    public function index(Request $request)
    {
        $year = now()->year;

        try {
            // Carga mínima y robusta
            $newReleases = $this->stream->getNewReleases($year);
            $popular     = $this->stream->getPopular();
        } catch (\Throwable $e) {
            Log::error('Home load error', ['err' => $e->getMessage()]);
            return view('home', ['banners' => [], 'catalogs' => []]);
        }

        // RPDB helper
        $rpdb = fn($id, $type) =>
            "https://aiostreamsfortheweak.nhyira.dev/api/v1/rpdb?id={$id}&type={$type}"
            . "&fallback=" . rawurlencode("https://images.metahub.space/poster/small/{$id}/img")
            . "&apiKey=t0-free-rpdb";

        // Hero: mezcla estrenos y populares, sin duplicados
        $banners = collect(array_merge(
            $newReleases['movies'] ?? [],
            $newReleases['series'] ?? [],
            $popular['movies'] ?? [],
            $popular['series'] ?? []
        ))
            ->unique('id')
            ->shuffle()
            ->take(6)
            ->map(fn($item) => [
                'id'          => $item['id'],
                'type'        => $item['type'],
                'name'        => $item['name'] ?? '',
                'description' => $item['description'] ?? '',
                'poster'      => $rpdb($item['id'], $item['type']),
                'background'  => $rpdb($item['id'], $item['type']),
                'trailer'     => $item['trailers'][0]['source'] ?? '',
            ])
            ->values();

        // Catálogos
        $seriesTop = collect($popular['series'] ?? [])->unique('id')->take(24)->map(fn($i) => [
            ...$i, 'poster' => $rpdb($i['id'], $i['type']), 'background' => $rpdb($i['id'], $i['type']),
        ]);

        $moviesTop = collect($popular['movies'] ?? [])->unique('id')->take(24)->map(fn($i) => [
            ...$i, 'poster' => $rpdb($i['id'], $i['type']), 'background' => $rpdb($i['id'], $i['type']),
        ]);

        $estrenos = collect(array_merge(
            $newReleases['movies'] ?? [],
            $newReleases['series'] ?? []
        ))
            ->unique('id')
            ->shuffle()
            ->take(20)
            ->map(fn($i) => [
                ...$i, 'poster' => $rpdb($i['id'], $i['type']), 'background' => $rpdb($i['id'], $i['type']),
            ]);

        $catalogs = [
            ['meta' => ['name' => 'Top Series',     'type' => 'series'], 'items' => $seriesTop],
            ['meta' => ['name' => 'Top Películas',  'type' => 'movie' ], 'items' => $moviesTop],
            ['meta' => ['name' => "Estrenos $year", 'type' => 'mixed' ], 'items' => $estrenos],
        ];

        return view('home', compact('banners', 'catalogs'));
    }
}
