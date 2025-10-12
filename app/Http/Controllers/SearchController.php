<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private string $base;

    public function __construct()
    {
        $this->base = rtrim(config('stremio.base'), '/');
    }

    public function index(Request $request)
    {
        $query = trim($request->input('q'));

        if (!$query) {
            return view('search', ['query' => '', 'results' => collect()]);
        }

        // Catalogos de búsqueda (películas y series)
        $catalogs = [
            ['type' => 'movie', 'id' => 'e5ce3b0.top'],
            ['type' => 'series', 'id' => 'e5ce3b0.top'],
        ];

        $results = collect();

        foreach ($catalogs as $cat) {
            $url = "{$this->base}/catalog/{$cat['type']}/{$cat['id']}.json";
            $response = Http::get($url, [
                'extra' => json_encode(['search' => $query]),
            ]);

            if ($response->ok()) {
                $data = $response->json();
                $results = $results->merge($data['metas'] ?? []);
            }
        }

        // Evitar duplicados por ID
        $results = $results->unique('id')->values();

        return view('search', compact('query', 'results'));
    }
}
