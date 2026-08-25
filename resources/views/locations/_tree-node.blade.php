@php($children = $siteLocations->where('parent_id', $node->id)->values())
<li role="treeitem" aria-expanded="{{ $children->isNotEmpty() ? 'true' : 'false' }}">
    <div class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2">
        <span class="badge bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $node->location_type->value) }}</span>
        <a class="font-semibold text-indigo-700 hover:underline" href="{{ route('locations.show', $node) }}">{{ $node->name }}</a>
        @if ($node->code)<span class="text-xs text-slate-500">{{ $node->code }}</span>@endif
        @can('update', $node)
            <a class="ml-auto text-sm font-semibold text-slate-600 hover:text-slate-900" href="{{ route('locations.edit', $node) }}">Edit</a>
        @endcan
    </div>
    @if ($children->isNotEmpty())
        <ul class="ml-6 mt-2 space-y-2 border-l border-slate-200 pl-4" role="group">
            @foreach ($children as $child)
                @include('locations._tree-node', ['node' => $child, 'siteLocations' => $siteLocations])
            @endforeach
        </ul>
    @endif
</li>
