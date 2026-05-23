@php
    /*
     | Product gallery: main image + thumbnail strip. Themes include this
     | partial from their product.blade.php where they used to render a
     | single <img>.
     |
     | Inputs:
     |   $product   — App\Models\Product (must be eager-loaded with gallery)
     |   $variant   — optional, ignored today; here for future variants
     |
     | Behavior:
     |   - Pulls $product->allImages() = primary first, then gallery extras
     |   - Renders the main slot (first image) + thumbnails
     |   - Clicking a thumbnail swaps the main image (vanilla JS, no library)
     |   - When the product has only one image, the thumb strip is hidden
     |   - When the product has none, shows a stylized placeholder
     |
     | Style is intentionally minimal/utility — themes layer their own
     | spacing + accents via wrapping the partial.
     */
    $images = $product->allImages();
    // Stable id so multiple gallery instances on a page don't fight.
    $galleryId = 'pg-' . $product->id;
@endphp

<div class="product-gallery" data-gallery-id="{{ $galleryId }}">
    <div class="pg-main">
        @if ($images->isNotEmpty())
            <img id="{{ $galleryId }}-main"
                 src="{{ $images[0]['url'] }}"
                 alt="{{ $images[0]['alt'] }}"
                 loading="eager">
        @else
            <div class="pg-placeholder">{{ strtoupper(substr($product->name, 0, 2)) }}</div>
        @endif
    </div>

    @if ($images->count() > 1)
        <div class="pg-thumbs" role="tablist" aria-label="Product images">
            @foreach ($images as $i => $img)
                <button type="button"
                        class="pg-thumb {{ $i === 0 ? 'is-active' : '' }}"
                        data-pg-target="{{ $galleryId }}"
                        data-pg-src="{{ $img['url'] }}"
                        data-pg-alt="{{ $img['alt'] }}"
                        role="tab"
                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                        aria-label="View image {{ $i + 1 }} of {{ $images->count() }}">
                    <img src="{{ $img['url'] }}" alt="" loading="lazy">
                </button>
            @endforeach
        </div>
    @endif
</div>

<style>
    /* Scoped utility-style CSS so themes don't have to add anything to
       use the gallery. Themes can still override .pg-* selectors if they
       want different look-and-feel. */
    .product-gallery { display: flex; flex-direction: column; gap: .75rem; }
    .pg-main {
        position: relative;
        aspect-ratio: 1 / 1;
        background: #f3f4f6;
        border-radius: 12px;
        overflow: hidden;
    }
    .pg-main img {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
        transition: opacity .15s ease;
    }
    .pg-placeholder {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 2rem; letter-spacing: 0.1em;
        color: rgba(0,0,0,.25);
    }
    .pg-thumbs {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
        gap: .5rem;
    }
    .pg-thumb {
        aspect-ratio: 1 / 1;
        padding: 0;
        background: #f3f4f6;
        border: 2px solid transparent;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        transition: border-color .15s ease, transform .15s ease;
    }
    .pg-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pg-thumb:hover { transform: translateY(-1px); }
    .pg-thumb.is-active { border-color: currentColor; }
</style>

<script>
    /* Click-to-swap. Idempotent: re-runs on every render but only binds
       once per gallery instance via a data-flag. Vanilla event delegation
       so it survives any Livewire / Alpine re-render that themes might
       add later. */
    (function () {
        if (window.__pgBound) return;
        window.__pgBound = true;
        document.addEventListener('click', (e) => {
            const thumb = e.target.closest('.pg-thumb[data-pg-target]');
            if (! thumb) return;
            const id = thumb.dataset.pgTarget;
            const main = document.getElementById(id + '-main');
            if (! main) return;
            main.style.opacity = '0';
            // Wait for the fade to start, swap src, fade back in. Cheap
            // crossfade without a real transition library.
            requestAnimationFrame(() => {
                main.src = thumb.dataset.pgSrc;
                main.alt = thumb.dataset.pgAlt;
                main.style.opacity = '1';
            });
            // Update active state on the strip.
            document.querySelectorAll('.pg-thumb[data-pg-target="' + id + '"]').forEach(t => {
                const isMe = t === thumb;
                t.classList.toggle('is-active', isMe);
                t.setAttribute('aria-selected', isMe ? 'true' : 'false');
            });
        });
    })();
</script>
