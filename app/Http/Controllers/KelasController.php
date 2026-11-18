<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSchoolContext;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KelasController extends Controller
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
                ->withCount('kelas')
                ->orderBy('name')
                ->get();
        } else {
            $school = $user->school;
        }

        $search = $request->query('search');

        $kelas = $school
            ? Kelas::where('branch_id', $school->id)
                ->when($search, fn ($q) => $q->where('nama_kelas', 'like', "%{$search}%"))
                ->withCount('santris')
                ->orderBy('nama_kelas')
                ->get()
            : collect();

        return view('kelas.index', compact('kelas', 'school', 'schools'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $school = null;
        $schools = collect();

        if ($user->isSuperadmin()) {
            $school = $request->query('school_id')
                ? School::findOrFail($request->query('school_id'))
                : null;

            if (! $school) {
                $schools = School::where('status', School::STATUS_ACTIVE)->orderBy('name')->get();
            }
        } else {
            $school = $user->school;
        }

        return view('kelas.create', compact('school', 'schools'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:255',
            'wali_kelas' => 'nullable|string|max:255',
            'kapasitas' => 'nullable|integer|min:1',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $validated['branch_id'] = $this->resolveBranchId($request);

        Kelas::create($validated);

        return redirect()->route('kelas.index', $this->schoolQuery($validated['branch_id']))
            ->with('success', 'Data kelas berhasil ditambahkan.');
    }

    public function edit(Request $request, Kelas $kela)
    {
        $school = auth()->user()->isSuperadmin()
            ? $kela->school
            : auth()->user()->school;

        return view('kelas.edit', compact('kela', 'school'));
    }

    public function update(Request $request, Kelas $kela)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:255',
            'wali_kelas' => 'nullable|string|max:255',
            'kapasitas' => 'nullable|integer|min:1',
            'keterangan' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($kela, $validated) {
            $locked = Kelas::whereKey($kela->id)->lockForUpdate()->firstOrFail();
            $kapasitasBaru = (int) ($validated['kapasitas'] ?? 0);
            $terisi = $locked->santris()->count();

            if ($kapasitasBaru > 0 && $kapasitasBaru < $terisi) {
                throw ValidationException::withMessages([
                    'kapasitas' => "Kapasitas tidak boleh lebih kecil dari jumlah santri terdaftar ({$terisi} santri).",
                ]);
            }

            $locked->update($validated);
        });

        return redirect()->route('kelas.index', $this->schoolQuery($kela->branch_id))
            ->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kela)
    {
        if ($kela->santris()->exists()) {
            return redirect()->route('kelas.index', $this->schoolQuery($kela->branch_id))
                ->with('error', 'Kelas tidak dapat dihapus karena masih memiliki santri. Pindahkan santri ke kelas lain terlebih dahulu.');
        }

        $kela->delete();

        return redirect()->route('kelas.index', $this->schoolQuery($kela->branch_id))
            ->with('success', 'Data kelas berhasil dihapus.');
    }

    public function destroyAll(Request $request)
    {
        $branchId = $this->resolveBranchId($request);

        $kelasList = Kelas::where('branch_id', $branchId)->get();

        if ($kelasList->isEmpty()) {
            return redirect()->route('kelas.index', $this->schoolQuery($branchId))
                ->with('error', 'Tidak ada kelas untuk dihapus.');
        }

        $totalSantri = Santri::where('branch_id', $branchId)->count();
        $totalKelas = $kelasList->count();

        if ($totalSantri > 0) {
            Santri::where('branch_id', $branchId)->delete();
        }

        foreach ($kelasList as $kelas) {
            $kelas->delete();
        }

        return redirect()->route('kelas.index', $this->schoolQuery($branchId))
            ->with('success', "{$totalKelas} kelas dan {$totalSantri} santri berhasil dihapus.");
    }
}
