<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $logs = AuditLog::with('user')
            ->when(! $user->isSuperadmin(), fn ($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('summary', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', $action))
            ->when($request->query('model'), fn ($q, $model) => $q->where('auditable_type', 'like', '%'.$model.'%'))
            ->when($request->query('from'), fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $modelOptions = AuditLog::query()
            ->select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->map(fn ($type) => class_basename($type))
            ->unique()
            ->values();

        $actions = ['login', 'created', 'updated', 'deleted', 'restored'];

        return view('audit.index', compact('logs', 'modelOptions', 'actions'));
    }
}
