<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-slate-700" for="location-name">Name</label>
        <input id="location-name" class="form-control mt-1" type="text" name="name" value="{{ old('name', $location->name ?? '') }}" required>
        @error('name')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700" for="location-site">Site</label>
        <select id="location-site" class="form-control mt-1" name="site_id">
            <option value="">Select a site</option>
            @foreach ($sites as $site)
                <option value="{{ $site->id }}" @selected(old('site_id', $location->site_id ?? '') == $site->id)>{{ $site->name }} ({{ $site->code }})</option>
            @endforeach
        </select>
        @error('site_id')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700" for="location-parent">Inside location</label>
        <select id="location-parent" class="form-control mt-1" name="parent_id">
            <option value="">Site root</option>
            @foreach ($parentLocations as $parentLocation)
                <option value="{{ $parentLocation->id }}" @selected(old('parent_id', $location->parent_id ?? '') == $parentLocation->id)>{{ $parentLocation->site?->name }} / {{ $parentLocation->name }} ({{ str_replace('_', ' ', $parentLocation->location_type->value) }})</option>
            @endforeach
        </select>
        @error('parent_id')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700" for="location-type">Location type</label>
        <select id="location-type" class="form-control mt-1" name="location_type" required>
            @foreach (\App\Enums\LocationType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('location_type', isset($location) ? $location->location_type->value : \App\Enums\LocationType::OTHER->value) === $type->value)>{{ str_replace('_', ' ', $type->value) }}</option>
            @endforeach
        </select>
        @error('location_type')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
    </div>
    <div><label class="block text-sm font-semibold text-slate-700" for="location-building">Building</label><input id="location-building" class="form-control mt-1" name="building" value="{{ old('building', $location->building ?? '') }}"></div>
    <div><label class="block text-sm font-semibold text-slate-700" for="location-floor">Floor</label><input id="location-floor" class="form-control mt-1" name="floor" value="{{ old('floor', $location->floor ?? '') }}"></div>
    <div class="md:col-span-2"><label class="block text-sm font-semibold text-slate-700" for="location-sublocation">Room, store, or area</label><input id="location-sublocation" class="form-control mt-1" name="sublocation" value="{{ old('sublocation', $location->sublocation ?? '') }}"></div>
    <div class="md:col-span-2"><label class="block text-sm font-semibold text-slate-700" for="location-description">Description</label><textarea id="location-description" class="form-control mt-1" name="description" rows="3">{{ old('description', $location->description ?? '') }}</textarea></div>
    @if (config('inventory.poc_ui_mode'))
        <details class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <summary class="cursor-pointer text-sm font-bold text-slate-700">Advanced location settings</summary>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div><label class="block text-sm font-semibold text-slate-700" for="location-code">Internal code</label><input id="location-code" class="form-control mt-1" name="code" value="{{ old('code', $location->code ?? '') }}"></div>
                <div><label class="block text-sm font-semibold text-slate-700" for="location-level">Structured level</label><input id="location-level" class="form-control mt-1" name="level_number" value="{{ old('level_number', $location->level_number ?? '') }}"></div>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="hidden" name="is_restricted" value="0"><input type="checkbox" name="is_restricted" value="1" @checked((bool) old('is_restricted', $location->is_restricted ?? false))> Restricted physical area</label>
            </div>
        </details>
    @else
        <div><label class="block text-sm font-semibold text-slate-700" for="location-code">Code</label><input id="location-code" class="form-control mt-1" name="code" value="{{ old('code', $location->code ?? '') }}"></div>
        <div><label class="block text-sm font-semibold text-slate-700" for="location-level">Floor/level label</label><input id="location-level" class="form-control mt-1" name="level_number" value="{{ old('level_number', $location->level_number ?? '') }}"></div>
        <label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="hidden" name="is_restricted" value="0"><input type="checkbox" name="is_restricted" value="1" @checked((bool) old('is_restricted', $location->is_restricted ?? false))> Restricted physical area</label>
    @endif
    <label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $location->is_active ?? true))> Active location</label>
</div>
