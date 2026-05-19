@props([
    'size' => 32,
])
{{--
    Ganvo logomark — geometric "G" formed by two rounded-top arches.
    The taller left arch + shorter right arch read as the silhouette of a
    capital G. Fill uses `currentColor` so the parent's CSS color drives
    the tint — call sites typically set `style="color: var(--brand)"`.

    viewBox is 0 0 100 100 (square). Both shapes are bottom-aligned at y=80
    so the lockup baseline stays consistent regardless of the mark height.
--}}
<svg viewBox="0 0 100 100"
     width="{{ $size }}"
     height="{{ $size }}"
     xmlns="http://www.w3.org/2000/svg"
     {{ $attributes->merge(['aria-hidden' => 'true']) }}>
    <path d="M 21 25 a 11 11 0 0 1 22 0 v 55 h -22 z" fill="currentColor"/>
    <path d="M 58 50 a 11 11 0 0 1 22 0 v 30 h -22 z" fill="currentColor"/>
</svg>
