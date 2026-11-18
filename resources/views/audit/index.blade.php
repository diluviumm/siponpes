@extends('layouts.app')

@section('title', 'Audit Trail')

@section('page-title', 'Audit Trail')

@section('content')

<x-breadcrumb :items="[['label' => 'Dashboard', 'url' => route('dashboard')], 'Audit Trail']" />

<div class="page-header">
    <div>
        <h1 class="page-title">Audit Trail</h1>
        <x-page-title-rule width="max-w-xs" />
        <p class="page-subtitle">Riwayat perubahan data penting: siapa yang mengubah apa dan kapan.</p>
    </div>
</div>

<form method="GET" action="{{ route('audit.index') }}" class="card card-body mb-6">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        <div class="md:col-span-2">
            <label class="label">Cari</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama pengguna atau ringkasan..." class="input">
        </div>
        <div>
            <label class="label">Aksi</label>
            <select name="action" class="select">
                <option value="">Semua Aksi</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ ucfirst($action) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Model</label>
            <select name="model" class="select">
                <option value="">Semua Model</option>
                @foreach($modelOptions as $model)
                    <option value="{{ $model }}" {{ request('model') === $model ? 'selected' : '' }}>{{ $model }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="label">Dari</label>
                <input type="date" name="from" value="{{ request('from') }}" class="input">
            </div>
            <div>
                <label class="label">Sampai</label>
                <input type="date" name="to" value="{{ request('to') }}" class="input">
            </div>
        </div>
    </div>
    <div class="flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary btn-md">Terapkan Filter</button>
        <a href="{{ route('audit.index') }}" class="btn btn-ghost btn-md">Reset</a>
    </div>
</form>

@if($logs->isEmpty())
    <div class="card card-body text-center text-ink/60">Belum ada catatan audit.</div>
@else
    <div class="table-card">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr>
                        <th class="th">Waktu</th>
                        <th class="th">Pengguna</th>
                        <th class="th">Aksi</th>
                        <th class="th">Objek</th>
                        <th class="th">Ringkasan</th>
                        <th class="th">Perubahan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr class="table-row">
                            <td class="td tabular-nums whitespace-nowrap">{{ $log->created_at->locale('id')->translatedFormat('d/m/Y H:i') }}</td>
                            <td class="td">{{ $log->user?->name ?? 'Sistem' }}</td>
                            <td class="td">
                                @if($log->action === 'login')
                                    <x-badge tone="slate">Login</x-badge>
                                @elseif($log->action === 'created')
                                    <x-badge tone="green">Dibuat</x-badge>
                                @elseif($log->action === 'deleted')
                                    <x-badge tone="red">Dihapus</x-badge>
                                @elseif($log->action === 'restored')
                                    <x-badge tone="blue">Dipulihkan</x-badge>
                                @else
                                    <x-badge tone="yellow">Diubah</x-badge>
                                @endif
                            </td>
                            <td class="td font-mono text-xs">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                            <td class="td">{{ $log->summary }}</td>
                            <td class="td">
                                @if($log->changes && count($log->changes))
                                    <ul class="text-xs space-y-0.5 max-w-xs">
                                        @foreach($log->changes as $field => $diff)
                                            <li><code class="text-ink/50">{{ $field }}</code>: <s class="text-rose-600">{{ $diff['old'] }}</s> → <b class="text-pine-700">{{ $diff['new'] }}</b></li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-ink/40">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endif

@endsection
