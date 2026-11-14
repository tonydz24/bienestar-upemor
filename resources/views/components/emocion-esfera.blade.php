@props([
    'slug' => null,
    'emocion' => null,
    'nombre' => null,
    'fallbackIcono' => null,
    'size' => 'md',
    'interactive' => false,
    'alt' => null,
])

@php
    $slugClean = $slug ?? ($emocion?->slug ?? '');
    $slugClean = \Illuminate\Support\Str::slug($slugClean);
    $nombreClean = $nombre ?? ($emocion?->nombre ?? ucfirst($slugClean));
    $fallback = $fallbackIcono ?? ($emocion?->icono ?? '✨');
    $altText = $alt ?? "Esfera 3D de {$nombreClean}";

    // Mapeo estandarizado de dimensiones (ancho y alto iguales para preservar proporción 1:1)
    $sizeMap = [
        'xs' => 20,
        'sm' => 26,
        'md' => 42,
        'lg' => 54,
        'xl' => 76,
    ];
    $pixelSize = is_numeric($size) ? (int) $size : ($sizeMap[$size] ?? 42);

    $relPath = "images/emociones/{$slugClean}.png";
    $fullPath = public_path($relPath);
    $hasImage = !empty($slugClean) && file_exists($fullPath);
@endphp

@if ($hasImage)
    <img src="{{ asset($relPath) }}"
         alt="{{ $altText }}"
         width="{{ $pixelSize }}"
         height="{{ $pixelSize }}"
         loading="lazy"
         draggable="false"
         {{ $attributes->merge([
             'class' => 'emotion-sphere-img emotion-sphere-' . $slugClean . ($interactive ? ' emotion-sphere-interactive' : ''),
             'style' => "width: {$pixelSize}px; height: {$pixelSize}px;",
         ]) }}>
@else
    {{-- Fallback accesible de reserva si el archivo aún no ha sido colocado en disco --}}
    <span {{ $attributes->merge([
        'class' => 'emotion-sphere-fallback emotion-sphere-' . $slugClean,
        'style' => "font-size: " . max(14, (int)($pixelSize * 0.72)) . "px; line-height: 1; display: inline-flex; align-items: center; justify-content: center;",
        'title' => $nombreClean,
    ]) }} aria-hidden="true">{{ $fallback }}</span>
@endif

