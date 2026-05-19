@props([
    'size'         => 'md',   // sm | md | lg | xl
    'wordmark'     => true,   // false to render mark only
    'markColor'    => null,   // CSS color for the mark; defaults to var(--brand)
    'wordColor'    => null,   // CSS color for "Ganvo"; defaults to var(--text)
])
@php
    // Sizing presets — mark height in px, wordmark font-size in px, gap between them.
    $presets = [
        'sm' => ['mark' => 22, 'text' => 16, 'gap' => 8,  'tracking' => '-0.015em'],
        'md' => ['mark' => 32, 'text' => 22, 'gap' => 10, 'tracking' => '-0.02em' ],
        'lg' => ['mark' => 56, 'text' => 36, 'gap' => 14, 'tracking' => '-0.025em'],
        'xl' => ['mark' => 80, 'text' => 52, 'gap' => 18, 'tracking' => '-0.03em' ],
    ];
    $s = $presets[$size] ?? $presets['md'];
    $markStyle = 'color: ' . ($markColor ?? 'var(--brand)') . '; display: inline-flex; line-height: 0;';
    $wordStyle = sprintf(
        'color: %s; font-weight: 800; font-size: %dpx; letter-spacing: %s; line-height: 1; font-family: -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, sans-serif;',
        $wordColor ?? 'var(--text)',
        $s['text'],
        $s['tracking']
    );
@endphp

{{-- Inline-flex so the lockup behaves like a single piece of text in headers,
     nav rows, etc. The mark color is independent of the wordmark color so the
     mark stays brand-blue in both light and dark themes while the wordmark
     follows var(--text). --}}
<span {{ $attributes->merge(['style' => 'display: inline-flex; align-items: center; gap: ' . $s['gap'] . 'px;']) }}>
    <span style="{{ $markStyle }}">
        <x-brand-mark :size="$s['mark']" />
    </span>
    @if ($wordmark)
        <span style="{{ $wordStyle }}">Ganvo</span>
    @endif
</span>
