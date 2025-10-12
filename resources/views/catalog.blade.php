@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-4">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Catálogo: {{ $id }} ({{ $type }})</h1>
    <a href="{{ route('home') }}" class="text-blue-600">← Volver</a>
  </div>

  @php $metas = $catalog['metas'] ?? []; @endphp
  <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4 mt-4">
    @foreach($metas as $m)
      <a href="{{ route('title', ['type'=>$m['type'],'id'=>$m['id']]) }}"
         class="rounded-xl border hover:shadow">
        @if(!empty($m['poster']))
          <img src="{{ $m['poster'] }}" class="w-full rounded-t-xl" alt="">
        @endif
        <div class="p-2">
          <div class="font-semibold line-clamp-2">{{ $m['name'] }}</div>
          <div class="text-xs text-gray-500">{{ $m['type'] }}</div>
        </div>
      </a>
    @endforeach
  </div>
</div>
@endsection
