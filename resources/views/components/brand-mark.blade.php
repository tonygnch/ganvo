@props([
    'size' => 32,
])
{{--
    Ganvo logomark — geometric "G" formed by two rounded-top arches.
    The taller left arch + shorter right arch sit bottom-aligned, reading
    as the silhouette of a capital G. Fill uses `currentColor` so the
    parent's CSS color drives the tint — call sites typically set
    `style="color: var(--brand)"`.

    viewBox is the *tight* bounding box of the artwork (0 0 59 66), so a
    size=N attribute produces an N-pixel-tall mark with no empty padding
    sitting next to a wordmark. This is what makes the lockup feel
    balanced rather than the mark looking visually shrunk inside a
    square box.
--}}
<svg viewBox="0 0 59 66"
     height="{{ $size }}"
     xmlns="http://www.w3.org/2000/svg"
     {{ $attributes->merge(['aria-hidden' => 'true']) }}>
    {{-- Left arch: full-height, rounded top, flat bottom. --}}
    <path d="M 0 11 a 11 11 0 0 1 22 0 v 55 h -22 z" fill="currentColor"/>
    {{-- Right arch: ~62% of the left's height, same arch language. --}}
    <path d="M 37 36 a 11 11 0 0 1 22 0 v 30 h -22 z" fill="currentColor"/>
</svg>
