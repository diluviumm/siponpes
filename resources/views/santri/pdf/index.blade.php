@extends('layouts.pdf')

@section('content')
    <h2>Data Santri</h2>

    <table>
        <thead>
            <tr>
                <th>NIS</th>
                <th>Nama</th>
                <th>Jenis Kelamin</th>
                <th>Sekolah</th>
                <th>Kelas</th>
                <th>No HP Wali</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            @forelse($exportSantris as $santri)
                <tr>
                    <td>{{ $santri->nis ?? '-' }}</td>
                    <td>{{ $santri->nama }}</td>
                    <td>{{ $santri->jenis_kelamin === 'L' ? 'Laki-laki' : ($santri->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</td>
                    <td>{{ $santri->school?->name ?? '-' }}</td>
                    <td>{{ $santri->kelas?->nama_kelas ?? '-' }}</td>
                    <td>{{ $santri->no_hp_wali ?? '-' }}</td>
                    <td>{{ $santri->email ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Belum ada data santri.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="total">
            <tr>
                <td colspan="6">Total Santri</td>
                <td>{{ $exportSantris->count() }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
