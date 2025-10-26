<?php

namespace App\Http\Controllers;

use App\Services\StreamApi;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected StreamApi $stream;

    public function __construct(StreamApi $stream)
    {
        $this->stream = $stream;
    }

    public function index(Request $request)
    {
        $query = trim($request->input('q'));

        if (!$query) {
            return view('search', ['query' => '', 'results' => collect()]);
        }

        // Usar catálogos de búsqueda consistentes con el addon
        $catalogId = '713e3b0.search';

        $movies = $this->stream->catalog('movie', $catalogId, ['search' => $query], 60);
        $series = $this->stream->catalog('series', $catalogId, ['search' => $query], 60);

        $results = collect(array_merge($movies, $series))
            ->unique('id')
            ->values();

        return view('search', compact('query', 'results'));
    }
}
