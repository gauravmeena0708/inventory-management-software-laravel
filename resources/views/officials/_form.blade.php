<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-slate-700" for="person-name">Name</label>
        <input id="person-name" class="form-control mt-1" name="name" value="{{ old('name', $official->name ?? '') }}" required>
        @error('name')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700" for="person-designation">Designation</label>
        <input id="person-designation" class="form-control mt-1" name="designation" value="{{ old('designation', $official->designation ?? '') }}" placeholder="e.g. System Administrator">
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700" for="person-department">Department</label>
        <input id="person-department" class="form-control mt-1" name="department" value="{{ old('department', $official->department ?? '') }}" placeholder="e.g. IT Operations">
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700" for="person-location">Location</label>
        <select id="person-location" class="form-control mt-1" name="location_id">
            <option value="">No location</option>
            @foreach ($locations as $personLocation)
                <option value="{{ $personLocation->id }}" @selected(old('location_id', $official->location_id ?? '') == $personLocation->id)>{{ $personLocation->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700" for="person-title">Title</label>
        <input id="person-title" class="form-control mt-1" name="title" value="{{ old('title', $official->title ?? '') }}" placeholder="Mr., Ms., Dr.">
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700" for="person-email">Email</label>
        <input id="person-email" class="form-control mt-1" type="email" name="email" value="{{ old('email', $official->email ?? '') }}">
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-700" for="person-phone">Phone</label>
        <input id="person-phone" class="form-control mt-1" name="phone" value="{{ old('phone', $official->phone ?? '') }}">
    </div>
</div>
