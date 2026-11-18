<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSchoolContext;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

class StaffAccountController extends Controller
{
    use HandlesSchoolContext;

    public function index(Request $request)
    {
        $user = auth()->user();
        $school = null;
        $schools = collect();

        if ($user->isSuperadmin()) {
            $schoolId = $request->query('school_id');
            $school = $schoolId ? School::findOrFail($schoolId) : null;
            $schools = School::where('status', School::STATUS_ACTIVE)
                ->withCount('users')
                ->orderBy('name')
                ->get();
        } else {
            $school = $user->school;
        }

        $search = $request->query('search');

        $accounts = $school
            ? User::where('branch_id', $school->id)
                ->whereIn('role', [User::ROLE_SCHOOL_ADMIN, User::ROLE_CASHIER])
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('role')
                ->orderBy('name')
                ->get()
            : collect();

        return view('staff.index', compact('school', 'schools', 'accounts'));
    }

    public function store(Request $request)
    {
        $branchId = $this->resolveBranchId($request);

        $data = $request->validate([
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'role' => ['nullable', 'in:'.User::ROLE_SCHOOL_ADMIN.','.User::ROLE_CASHIER],
        ]);

        if (! auth()->user()->isSuperadmin()) {
            $data['role'] = User::ROLE_CASHIER;
        }

        User::create([
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => $data['admin_password'],
            'role' => $data['role'] ?? User::ROLE_SCHOOL_ADMIN,
            'branch_id' => $branchId,
            'email_verified_at' => now(),
        ]);

        $roleLabel = ($data['role'] ?? User::ROLE_SCHOOL_ADMIN) === User::ROLE_CASHIER ? 'kasir' : 'admin';

        return redirect()->route('staff.index', $this->schoolQuery($branchId))
            ->with('success', "Akun {$roleLabel} {$data['admin_email']} berhasil dibuat.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $actor = auth()->user();

        if (! $actor->isSuperadmin() && $user->branch_id !== $actor->branch_id) {
            abort(403, 'Akses ditolak.');
        }

        if (! in_array($user->role, [User::ROLE_SCHOOL_ADMIN, User::ROLE_CASHIER], true)) {
            abort(403, 'Akun ini tidak dapat diubah.');
        }

        $user->update(['password' => $data['password']]);

        return redirect()->route('staff.index', $this->schoolQuery($user->branch_id))
            ->with('success', "Password akun {$user->email} berhasil direset.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->role !== User::ROLE_CASHIER) {
            abort(403, 'Hanya akun kasir yang dapat dihapus.');
        }

        if ($user->id === auth()->id()) {
            abort(403, 'Akun tidak dapat menghapus dirinya sendiri.');
        }

        if (! auth()->user()->isSuperadmin() && $user->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Akses ditolak.');
        }

        $email = $user->email;

        $user->delete();

        return redirect()->route('staff.index', $this->schoolQuery($user->branch_id))
            ->with('success', "Akun kasir {$email} berhasil dihapus.");
    }
}
