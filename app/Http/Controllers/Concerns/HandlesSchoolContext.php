<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait HandlesSchoolContext
{
    protected function schoolQuery(?int $branchId = null): array
    {
        if (! auth()->user()->isSuperadmin()) {
            return [];
        }

        $branchId = $branchId ?? request('school_id');

        return $branchId ? ['school_id' => $branchId] : [];
    }

    protected function resolveBranchId(Request $request): int
    {
        $user = auth()->user();

        if ($user->isSuperadmin()) {
            return (int) $request->validate([
                'branch_id' => 'required|exists:branches,id',
            ])['branch_id'];
        }

        return (int) $user->branch_id;
    }
}
