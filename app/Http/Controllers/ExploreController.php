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

    /** Selecciona catálogo correcto según filtros */
    private function resolveCatalogId(?string $search, ?string $year, ?string $genre): string
    {
        if ($search)
            return '713e3b0.search';
        if ($year)
            return '713e3b0.year';
        return '713e3b0.top'; // género y default
    }

    /** Construye extras compatibles con el addon */
    private function buildExtras(?string $search, ?string $year, ?string $genre, int $skip = 0, int $limit = 30): array
    {
        $extras = ['skip' => $skip, 'limit' => $limit];

        if ($search) {
            $extras['search'] = $search;
        } else {
            if ($year)
                $extras['genre'] = $year;   // en AIOStreams, el catálogo "year" usa genre=YYYY
            if ($genre)
                $extras['genre'] = $genre;  // top por género
        }

        return $extras;
    }

    public function index(Request $request)
    {
        $type = $request->string('type')->toString() ?: 'movie';
        $genre = $request->string('genre')->toString() ?: null;
        $year = $request->string('year')->toString() ?: null;
        $search = trim($request->string('search')->toString() ?? '');

        // Manifest para poblar selects (fallbacks si viniera vacío)
        $manifest = $this->stream->manifest() ?: [];
        $catList = collect($manifest['catalogs'] ?? []);

        $genres = $catList
            ->filter(fn($c) => ($c['id'] ?? '') === '713e3b0.top')
            ->flatMap(fn($c) => collect($c['extra'] ?? [])->where('name', 'genre')->pluck('options'))
            ->flatten()->unique()->filter()->values();

        if ($genres->isEmpty()) {
            $genres = collect(['Action', 'Adventure', 'Animation', 'Comedy', 'Crime', 'Drama', 'Fantasy', 'Horror', 'Romance', 'Sci-Fi', 'Thriller']);
        }

        $years = $catList
            ->filter(fn($c) => ($c['id'] ?? '') === '713e3b0.year')
            ->flatMap(fn($c) => collect($c['extra'] ?? [])->where('name', 'genre')->pluck('options'))
            ->flatten()->unique()->sortDesc()->values();

        if ($years->isEmpty()) {
            $years = collect(range((int) date('Y'), 1990));
        }

        // Catálogo y extras correctos
        $catalogId = $this->resolveCatalogId($search ?: null, $year, $genre);
        $extras = $this->buildExtras($search ?: null, $year, $genre, skip: 0, limit: 30);

        $results = $this->stream->catalog($type, $catalogId, $extras, 30);

        return view('explorer', compact('type', 'genre', 'year', 'search', 'genres', 'years', 'results', 'catalogId'));
    }

    public function loadMore(Request $request)
    {
        $type = $request->string('type')->toString() ?: 'movie';
        $genre = $request->string('genre')->toString() ?: null;
        $year = $request->string('year')->toString() ?: null;
        $search = trim($request->string('search')->toString() ?? '');
        $skip = (int) $request->get('skip', 0);

        $catalogId = $this->resolveCatalogId($search ?: null, $year, $genre);
        $extras = $this->buildExtras($search ?: null, $year, $genre, skip: $skip, limit: 30);

        $items = $this->stream->catalog($type, $catalogId, $extras, 30);

        // Dedup defensivo por id
        $items = collect($items)->unique('id')->values()->toArray();

        return response()->json(['items' => $items]);
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
