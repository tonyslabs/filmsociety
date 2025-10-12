@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-4">
  <a href="javascript:history.back()" class="text-blue-600">← Volver</a>

  @php $m = $meta['meta'] ?? null; @endphp
  @if($m)
    <div class="flex gap-4 mt-3">
      @if(!empty($m['poster']))
        <img src="{{ $m['poster'] }}" class="w-40 rounded-xl" alt="">
      @endif
      <div>
        <h1 class="text-2xl font-bold">{{ $m['name'] }}</h1>
        <p class="text-gray-700 mt-2">{{ $m['description'] ?? '' }}</p>
      </div>
    </div>
  @endif

  <h2 class="text-xl font-semibold mt-6">Streams</h2>
  @php $ss = $streams['streams'] ?? []; @endphp
  <ul class="mt-2 space-y-2">
    @forelse($ss as $s)
      <li class="p-3 rounded border">
        @if(!empty($s['url']))
          <div class="font-medium">{{ $s['name'] ?? $s['title'] ?? 'Stream' }}</div>
          <a class="text-blue-600 break-all" href="{{ $s['url'] }}" target="_blank" rel="noreferrer">
            {{ $s['url'] }}
          </a>
          @if(Str::of($s['url'])->contains('.mp4'))
            <video src="{{ $s['url'] }}" controls class="w-full mt-2 rounded"></video>
          @elseif(Str::of($s['url'])->contains('.m3u8'))
            <div class="text-xs text-gray-500 mt-1">HLS detectado. Usa VLC/MPV si tu navegador no soporta.</div>
          @endif
        @else
          <div>{{ $s['name'] ?? $s['title'] ?? 'Stream' }} — sin URL directa</div>
        @endif
      </li>
    @empty
      <li class="text-gray-500">Sin streams.</li>
    @endforelse
  </ul>
</div>
@endsection
