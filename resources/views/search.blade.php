@extends('layouts.app')

@section('content')
<div class="px-6 sm:px-10 lg:px-16 xl:px-20 py-8">
  <h2 class="text-2xl font-semibold text-indigo-300 mb-6">
    Resultados de búsqueda 
    @if($query)
      <span class="text-gray-400 text-lg">"{{ $query }}"</span>
    @endif
  </h2>

  @if($results->isEmpty())
    <div class="text-gray-500">No se encontraron resultados.</div>
  @else
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
      @foreach($results as $item)
        <a href="{{ route('watch', ['type' => $item['type'], 'id' => $item['id']]) }}"
           class="block rounded-xl overflow-hidden bg-[#20243b]/70 border border-indigo-900/30 
                  shadow-md hover:shadow-indigo-700/40 transform hover:scale-105 transition">

          <img src="{{ $item['poster'] ?? asset('img/no-poster.png') }}" 
               class="w-full h-64 object-cover" 
               alt="{{ $item['name'] ?? '' }}">
          <div class="p-2 text-sm font-medium text-gray-100 truncate">
            {{ $item['name'] ?? 'Sin título' }}
          </div>
        </a>
      @endforeach
    </div>
  @endif
</div>
@endsection
