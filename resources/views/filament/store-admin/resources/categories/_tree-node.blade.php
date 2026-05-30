{{-- One row of the tree + the children's <ul> when present. Recursive.
     wire:key is critical: it tells Livewire to track this <li> by id
     across morphs, so after a reorder the same DOM nodes survive
     instead of being replaced — which would strip our Sortable binding
     and break subsequent drags. --}}
<li data-id="{{ $node['id'] }}" wire:key="ct-node-{{ $node['id'] }}">
    <div class="ct-row">
        <span class="ct-handle" aria-hidden="true" title="Drag to move">⋮⋮</span>
        <span class="ct-name">{{ $node['name'] }}</span>
        <span class="ct-slug">/{{ $node['slug'] }}</span>
        <span class="ct-badge">{{ $node['products_count'] }} products</span>
        @if ($node['is_active'])
            <span class="ct-badge ct-badge-on">Active</span>
        @else
            <span class="ct-badge ct-badge-off">Hidden</span>
        @endif
        <div class="ct-actions">
            <a class="ct-btn" href="{{ $node['edit_url'] }}">Edit</a>
        </div>
    </div>
    {{-- Always render a child <ul> so the operator can drag into an
         empty parent to nest something under it. SortableJS uses the
         emptyInsertThreshold to make empty lists drop targets too. --}}
    <ul class="ct-list" data-parent-id="{{ $node['id'] }}">
        @foreach ($node['children'] as $child)
            @include('filament.store-admin.resources.categories._tree-node', ['node' => $child])
        @endforeach
    </ul>
</li>
