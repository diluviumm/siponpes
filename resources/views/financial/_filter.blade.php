<form method="GET" action="{{ url()->current() }}" class="card p-4 mb-4">
    <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
        @if(auth()->user()->isSuperadmin())
            <div class="w-full sm:w-56">
                <label class="label">Sekolah</label>
                <select name="school_id" class="select" onchange="this.form.submit()">
                    <option value="">Semua Sekolah (Gabungan)</option>
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}" {{ (string) $schoolId === (string) $school->id ? 'selected' : '' }}>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="w-full sm:w-44">
            <label class="label">Dari Tanggal</label>
            <input type="date" name="from" class="input" value="{{ $from ?? '' }}">
        </div>
        <div class="w-full sm:w-44">
            <label class="label">Sampai Tanggal</label>
            <input type="date" name="to" class="input" value="{{ $to ?? '' }}">
        </div>
        <button type="submit" class="btn btn-primary btn-md">Tampilkan</button>
        @if(($from ?? null) || ($to ?? null) || ($schoolId ?? null))
            <a href="{{ url()->current() }}" class="btn btn-secondary btn-md">Reset</a>
        @endif
    </div>
</form>
