<div class="grid gap-5 md:grid-cols-2">
    <div class="form-group md:col-span-2"><label for="name">Registry name</label><input id="name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $file->name ?? '') }}" required>@error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
    <div class="form-group"><label for="efile_number">E-file number</label><input id="efile_number" class="form-control" name="efile_number" value="{{ old('efile_number', $file->efile_number ?? '') }}"></div>
    <div class="form-group"><label for="physical_number">Physical file number</label><input id="physical_number" class="form-control" name="physical_number" value="{{ old('physical_number', $file->physical_number ?? '') }}"></div>
    <div class="form-group"><label for="physical_name">Physical file name</label><input id="physical_name" class="form-control" name="physical_name" value="{{ old('physical_name', $file->physical_name ?? '') }}"></div>
    <div class="form-group"><label for="division">Division</label><input id="division" class="form-control" name="division" value="{{ old('division', $file->division ?? '') }}"></div>
    <div class="form-group md:col-span-2"><label for="subject">Subject</label><input id="subject" class="form-control" name="subject" value="{{ old('subject', $file->subject ?? '') }}"></div>
    <div class="form-group"><label for="opened_at">Opened date</label><input id="opened_at" type="date" class="form-control" name="opened_at" value="{{ old('opened_at', isset($file) && $file->opened_at ? $file->opened_at->format('Y-m-d') : '') }}"></div>
</div>
