<?php

namespace App\Http\Controllers;

use App\Services\StreamApi;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    protected StreamApi $stream;

    public function __construct(StreamApi $stream)
    {
        $this->stream = $stream;
    }

    public function index()
    {
        // === 1️⃣ MANIFEST =====================================================
        $manifest = $this->stream->manifest();

        // === 2️⃣ CATALOGOS PRINCIPALES =======================================
        $year = '2025'; // 🔥 Año fijo (puede ser dinámico)
        $newReleases = $this->stream->getNewReleases($year);
        $popular = $this->stream->getPopular();

        // 🔹 Helper: Generar URL rpdb (misma que usa el carrusel del addon)
        $rpdb = fn($id, $type) =>
            "https://aiostreamsfortheweak.nhyira.dev/api/v1/rpdb?id={$id}&type={$type}"
            . "&fallback=" . rawurlencode("https://images.metahub.space/poster/small/{$id}/img")
            . "&apiKey=t0-free-rpdb";

        // === 3️⃣ HERO CAROUSEL ===============================================
        $banners = collect(array_merge(
            $newReleases['movies'] ?? [],
            $newReleases['series'] ?? []
        ))
            ->shuffle()
            ->take(6)
            ->map(fn($item) => [
                'id'          => $item['id'],
                'type'        => $item['type'],
                'name'        => $item['name'],
                'description' => $item['description'],
                'poster'      => $rpdb($item['id'], $item['type']),
                'background'  => $rpdb($item['id'], $item['type']),
                'logo'        => $item['logo'] ?? null,
            ])
            ->values();

        // === 4️⃣ TOP SERIES Y PELÍCULAS ======================================
        $seriesTop = collect($popular['series'])
            ->take(30)
            ->map(fn($item) => [
                ...$item,
                'poster' => $rpdb($item['id'], $item['type']),
                'background' => $rpdb($item['id'], $item['type']),
            ])
            ->values();

        $moviesTop = collect($popular['movies'])
            ->take(30)
            ->map(fn($item) => [
                ...$item,
                'poster' => $rpdb($item['id'], $item['type']),
                'background' => $rpdb($item['id'], $item['type']),
            ])
            ->values();

        // === 5️⃣ ESTRRENOS (pelis + series) ==================================
        $estrenos = collect(array_merge(
            $newReleases['movies'] ?? [],
            $newReleases['series'] ?? []
        ))
            ->shuffle()
            ->take(20)
            ->map(fn($item) => [
                ...$item,
                'poster' => $rpdb($item['id'], $item['type']),
                'background' => $rpdb($item['id'], $item['type']),
            ])
            ->values();

        // === 6️⃣ ESTRUCTURA DE CATÁLOGOS =====================================
        $catalogs = [
            [
                'meta' => ['name' => 'Top Series', 'type' => 'series'],
                'items' => $seriesTop,
            ],
            [
                'meta' => ['name' => 'Top Películas', 'type' => 'movie'],
                'items' => $moviesTop,
            ],
            [
                'meta' => ['name' => "Estrenos {$year}", 'type' => 'mixed'],
                'items' => $estrenos,
            ],
        ];

        // === 7️⃣ ENVIAR DATOS A LA VISTA =====================================
        return view('home', compact('banners', 'catalogs'));
    }
}
