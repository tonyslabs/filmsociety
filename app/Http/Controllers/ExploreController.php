<?php

namespace App\Http\Controllers;

use App\Services\StreamApi;
use Illuminate\Http\Request;

class ExploreController extends Controller
{
    protected StreamApi $stream;

    public function __construct(StreamApi $stream)
    {
        $this->stream = $stream;
    }

    public function index(Request $request)
    {
        $type = $request->get('type'); // ❌ quitamos el default 'movie'
        $genre = $request->get('genre');
        $year = $request->get('year');
        $search = $request->get('search');

        // === Base: manifest ===
        $manifest = $this->stream->manifest();

        $genres = collect($manifest['catalogs'])
            ->filter(fn($c) => $c['id'] === '713e3b0.top')
            ->flatMap(fn($c) => collect($c['extra'])->where('name', 'genre')->pluck('options'))
            ->flatten()
            ->unique()
            ->values();

        $years = collect($manifest['catalogs'])
            ->filter(fn($c) => $c['id'] === '713e3b0.year')
            ->flatMap(fn($c) => collect($c['extra'])->where('name', 'genre')->pluck('options'))
            ->flatten()
            ->sortDesc()
            ->values();

        // === Resultados según filtros ===
        if ($search) {
            // 🔍 búsqueda directa
            $results = $this->stream->catalog('movie', '713e3b0.top', ['search' => $search]);
        } elseif ($year) {
            $results = $this->stream->catalog('movie', '713e3b0.year', ['genre' => $year]);
        } elseif ($genre) {
            $results = $this->stream->catalog('movie', '713e3b0.top', ['genre' => $genre]);
        } elseif (!$type) {
            // 🎲 Aleatorio entre películas y series
            $movies = $this->stream->catalog('movie', '713e3b0.top');
            $shows = $this->stream->catalog('series', '713e3b0.top');

            // mezclamos ambos resultados y los barajamos
            $results = collect($movies)
                ->merge($shows)
                ->shuffle()
                ->take(30)
                ->values();
        } else {
            // 🔥 filtrado normal
            $results = $this->stream->catalog($type, '713e3b0.top');
        }

        return view('explorer', compact('type', 'genre', 'year', 'search', 'genres', 'years', 'results'));
    }


    public function loadMore(Request $request)
    {
        $type = $request->get('type');
        $skip = (int) $request->get('skip', 0);
        $genre = $request->get('genre');
        $year = $request->get('year');

        if (!$type) {
            // Si no se define, mezcla ambos tipos
            $movies = $this->stream->catalog('movie', '713e3b0.top', ['skip' => $skip]);
            $shows = $this->stream->catalog('series', '713e3b0.top', ['skip' => $skip]);
            $results = collect($movies)->merge($shows)->shuffle()->take(30)->values();
        } else {
            $extras = ['skip' => $skip];
            if ($genre)
                $extras['genre'] = $genre;
            if ($year)
                $extras['genre'] = $year;

            $results = $this->stream->catalog($type, '713e3b0.top', $extras, 30);
        }

        return response()->json(['items' => $results]);
    }

    public function peliculas(Request $request)
    {
        $request->merge(['type' => 'movie']);
        return $this->index($request);
    }

    public function series(Request $request)
    {
        $request->merge(['type' => 'series']);
        return $this->index($request);
    }


}
