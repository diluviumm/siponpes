@props(['santri', 'school' => null])

@php
    $schoolQuery = $school && auth()->user()->isSuperadmin() ? ['school_id' => $school->id] : [];
@endphp

<div class="inline-flex items-center gap-1.5">
    <a href="{{ route('wallet.editLimit', array_merge(['santri' => $santri->id], $schoolQuery)) }}"
       class="btn btn-ghost btn-sm">
        Limit
    </a>
    <a href="{{ route('wallet.editTransaction', array_merge(['santri' => $santri->id], $schoolQuery)) }}"
       class="btn btn-secondary btn-sm">
        Top-up / Tarik
    </a>
    <a href="{{ route('wallet.history', array_merge(['santri' => $santri->id], $schoolQuery)) }}"
       class="btn btn-info btn-sm">
        Riwayat
    </a>
</div>
