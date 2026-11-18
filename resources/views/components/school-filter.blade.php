@props(['schools', 'route', 'current' => null])

@if(auth()->user()->isSuperadmin())
<form method="GET" action="{{ route($route) }}" class="card p-4 mb-6">
    @if(request('kelas_id'))
        <input type="hidden" name="kelas_id" value="{{ request('kelas_id') }}">
    @endif
    @if(request('search'))
        <input type="hidden" name="search" value="{{ request('search') }}">
    @endif
    <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
        <div class="w-full sm:w-72">
            <label class="label">Sekolah</label>
            <select name="school_id" class="select" onchange="this.form.submit()">
                <option value="">-- Pilih Sekolah --</option>
                @foreach($schools as $school)
                    <option value="{{ $school->id }}" {{ (string) $current === (string) $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-md">Tampilkan</button>
        @if($current)
            <a href="{{ route($route) }}" class="btn btn-ghost btn-md">Ganti Sekolah</a>
        @endif
    </div>
</form>
@endif
