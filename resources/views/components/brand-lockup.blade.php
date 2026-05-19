@props([
    'size'      => 'md',   // sm | md | lg | xl
    'wordmark'  => true,   // false to render mark only
    'markColor' => null,   // CSS color for the mark; defaults to var(--brand)
    'wordColor' => null,   // CSS color for "Ganvo"; defaults to var(--text)
])
@php
    // Sizing presets. The mark is now drawn with a tight viewBox so its
    // numeric `height` equals its visible height — no padding to compensate
    // for. Proportions: text font-size ≈ 1.4× mark height puts the wordmark
    // cap-height at roughly the same visual size as the mark, so the lockup
    // reads as a single unit (matching the Ganvo lockup artwork the user
    // provided).
    $presets = [
        'sm' => ['mark' => 22, 'text' => 28,  'gap' => 8,  'tracking' => '-0.02em' ],
        'md' => ['mark' => 32, 'text' => 42,  'gap' => 10, 'tracking' => '-0.025em'],
        'lg' => ['mark' => 48, 'text' => 64,  'gap' => 14, 'tracking' => '-0.03em' ],
        'xl' => ['mark' => 72, 'text' => 96,  'gap' => 18, 'tracking' => '-0.035em'],
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

{{-- inline-flex with baseline-ish alignment so the mark's flat bottom sits
     on the same line as the wordmark's baseline. --}}
<span {{ $attributes->merge(['style' => 'display: inline-flex; align-items: flex-end; gap: ' . $s['gap'] . 'px;']) }}>
    <span style="{{ $markStyle }}">
        <x-brand-mark :size="$s['mark']" />
    </span>
    @if ($wordmark)
        <span style="{{ $wordStyle }}">Ganvo</span>
    @endif
</span>
