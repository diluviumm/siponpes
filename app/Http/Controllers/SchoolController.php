<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReminder;
use App\Models\JournalDetail;
use App\Models\School;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $sort = $request->query('sort', 'name');
        $direction = in_array($request->query('direction'), ['asc', 'desc'], true) ? $request->query('direction') : 'asc';

        $sortable = ['name', 'status', 'santris_count', 'created_at'];
        $sort = in_array($sort, $sortable, true) ? $sort : 'name';

        $schools = School::withCount(['santris', 'kelas', 'journals', 'users'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('users', fn ($uq) => $uq->where('email', 'like', "%{$search}%"));
                });
            })
            ->when($sort === 'name', function ($q) use ($direction) {
                $q->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 ELSE 2 END")
                    ->orderBy('name', $direction);
            })
            ->when($sort === 'status', function ($q) use ($direction) {
                $q->orderBy('status', $direction);
            })
            ->when($sort === 'created_at', function ($q) use ($direction) {
                $q->orderBy('created_at', $direction);
            })
            ->when($sort === 'santris_count', function ($q) use ($direction) {
                $q->orderBy('santris_count', $direction);
            })
            ->get();

        $pendingCount = $schools->where('status', School::STATUS_PENDING)->count();

        $sortUrl = function ($column) use ($request) {
            $dir = $request->query('sort') === $column && $request->query('direction') === 'asc' ? 'desc' : 'asc';

            return route('schools.index', array_filter([
                'search' => $request->query('search'),
                'sort' => $column,
                'direction' => $dir,
            ]));
        };

        return view('school.index', compact('schools', 'pendingCount', 'sortUrl', 'direction', 'sort'));
    }

    public function create()
    {
        return view('school.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value && ! preg_match('/^(?:\+62|62|0)[0-9]{8,13}$/', $value)) {
                    $fail('Format nomor telepon tidak valid. Gunakan 0 atau +62 diikuti 8–13 digit, contoh: 081234567890.');
                }
            }],
            'status' => ['nullable', 'in:pending,active,rejected'],
            'admin_name' => ['nullable', 'required_with:admin_email', 'string', 'max:255'],
            'admin_email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['nullable', 'required_with:admin_email', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $school = DB::transaction(function () use ($data) {
            $school = School::create([
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? School::STATUS_ACTIVE,
                'api_key' => School::generateApiKey(),
            ]);

            if (! empty($data['admin_email'])) {
                User::create([
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'password' => $data['admin_password'],
                    'role' => User::ROLE_SCHOOL_ADMIN,
                    'branch_id' => $school->id,
                    'email_verified_at' => now(),
                ]);
            }

            return $school;
        });

        $message = "Sekolah {$school->name} berhasil ditambahkan.";

        if (! empty($data['admin_email'])) {
            $message .= " Akun admin dibuat untuk {$data['admin_email']}.";
        } else {
            $message .= ' Akun admin belum dibuat (email admin dikosongkan).';
        }

        return redirect()->route('schools.index')
            ->with('success', $message);
    }

    public function edit(School $school)
    {
        $school->loadCount(['santris', 'kelas', 'journals', 'users']);

        return view('school.edit', compact('school'));
    }

    public function update(Request $request, School $school)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value && ! preg_match('/^(?:\+62|62|0)[0-9]{8,13}$/', $value)) {
                    $fail('Format nomor telepon tidak valid. Gunakan 0 atau +62 diikuti 8–13 digit, contoh: 081234567890.');
                }
            }],
            'status' => ['nullable', 'in:pending,active,rejected'],
        ]);

        $school->update($data);

        return redirect()->route('schools.index')
            ->with('success', 'Sekolah berhasil diperbarui.');
    }

    public function profileEdit()
    {
        $school = auth()->user()->school;
        abort_unless($school, 404);

        return view('school.profile', compact('school'));
    }

    public function profileUpdate(Request $request)
    {
        $school = auth()->user()->school;
        abort_unless($school, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value && ! preg_match('/^(?:\+62|62|0)[0-9]{8,13}$/', $value)) {
                    $fail('Format nomor telepon tidak valid. Gunakan 0 atau +62 diikuti 8–13 digit, contoh: 081234567890.');
                }
            }],
        ]);

        $school->update($data);

        return redirect()->route('school.profile')
            ->with('success', 'Profil sekolah berhasil diperbarui.');
    }

    public function destroy(Request $request, School $school)
    {
        $request->validate([
            'confirm' => ['required', 'string'],
        ]);

        if (strtoupper($request->input('confirm')) !== 'HAPUS') {
            return redirect()->route('schools.edit', $school)
                ->with('error', 'Konfirmasi tidak cocok. Ketik "HAPUS" untuk menghapus sekolah secara permanen.');
        }

        DB::transaction(function () use ($school) {
            $santriIds = $school->santris()->pluck('id');

            $walletIds = Wallet::whereIn('santri_id', $santriIds)->pluck('id');
            WalletTransaction::whereIn('wallet_id', $walletIds)->delete();
            Wallet::whereIn('santri_id', $santriIds)->delete();

            $invoiceIds = Invoice::whereIn('santri_id', $santriIds)->pluck('id');
            InvoiceItem::whereIn('invoice_id', $invoiceIds)->delete();
            InvoiceReminder::whereIn('invoice_id', $invoiceIds)->delete();
            Invoice::whereIn('santri_id', $santriIds)->delete();

            $journalIds = $school->journals()->pluck('id');
            JournalDetail::whereIn('journal_id', $journalIds)->delete();
            $school->journals()->delete();

            $school->sppTariffs()->delete();
            $school->kelas()->delete();
            $school->users()->delete();
            $school->santris()->forceDelete();

            $school->delete();
        });

        return redirect()->route('schools.index')
            ->with('success', "Sekolah {$school->name} beserta seluruh datanya berhasil dihapus permanen.");
    }

    public function approve(School $school)
    {
        $school->update(['status' => School::STATUS_ACTIVE]);

        return redirect()->route('schools.index')
            ->with('success', "Sekolah {$school->name} disetujui dan aktif.");
    }

    public function reject(School $school)
    {
        $school->update(['status' => School::STATUS_REJECTED]);

        return redirect()->route('schools.index')
            ->with('success', "Sekolah {$school->name} dinonaktifkan.");
    }

    public function resetPassword(Request $request, School $school)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $user = $school->users()->first();

        if ($user) {
            $user->update(['password' => $data['password']]);

            return redirect()->route('schools.index')
                ->with('success', "Password admin {$school->name} berhasil direset.");
        }

        return redirect()->route('schools.index')
            ->with('error', "Belum ada akun admin untuk {$school->name}.");
    }

    public function createAdmin(Request $request, School $school)
    {
        $data = $request->validate([
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'role' => ['nullable', 'in:'.User::ROLE_SCHOOL_ADMIN.','.User::ROLE_CASHIER],
        ]);

        User::create([
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => $data['admin_password'],
            'role' => $data['role'] ?? User::ROLE_SCHOOL_ADMIN,
            'branch_id' => $school->id,
            'email_verified_at' => now(),
        ]);

        $roleLabel = ($data['role'] ?? User::ROLE_SCHOOL_ADMIN) === User::ROLE_CASHIER ? 'kasir' : 'admin';

        return redirect()->route('schools.edit', $school)
            ->with('success', "Akun {$roleLabel} {$data['admin_email']} berhasil dibuat untuk {$school->name}.");
    }
}
