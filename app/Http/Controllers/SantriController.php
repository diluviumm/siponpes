<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSchoolContext;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\School;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SantriController extends Controller
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
                ->withCount('santris')
                ->orderBy('name')
                ->get();
        } else {
            $school = $user->school;
        }

        $kelasId = $request->query('kelas_id');
        $search = $request->query('search');
        $showAll = $kelasId === 'all';

        $trashCount = $school ? Santri::onlyTrashed()->where('branch_id', $school->id)->count() : 0;

        $kelasList = $school
            ? Kelas::where('branch_id', $school->id)->withCount('santris')->orderBy('nama_kelas')->get()
            : collect();

        $santris = collect();
        $selectedKelas = null;
        $isGlobalSearch = false;

        if ($school && $search && ! $kelasId) {
            $isGlobalSearch = true;
            $santris = Santri::with(['kelas', 'school'])
                ->where('branch_id', $school->id)
                ->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                })
                ->orderBy('nama')
                ->paginate(15)
                ->withQueryString();
        } elseif ($school && $kelasId && ! $showAll) {
            $selectedKelas = $kelasList->firstWhere('id', (int) $kelasId);
            $santris = Santri::with(['kelas', 'school'])
                ->where('kelas_id', $kelasId)
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sq) use ($search) {
                        $sq->where('nama', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%");
                    });
                })
                ->orderBy('nama')
                ->paginate(15)
                ->withQueryString();
        } elseif ($school && $showAll) {
            $isGlobalSearch = true;
            $santris = Santri::with(['kelas', 'school'])
                ->where('branch_id', $school->id)
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sq) use ($search) {
                        $sq->where('nama', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%");
                    });
                })
                ->orderBy('nama')
                ->paginate(15)
                ->withQueryString();
        }

        if ($school && ($request->query('pdf') || $request->query('csv'))) {
            $exportSantris = Santri::with(['kelas', 'school'])
                ->where('branch_id', $school->id)
                ->when($kelasId && ! $showAll, fn ($q) => $q->where('kelas_id', $kelasId))
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sq) use ($search) {
                        $sq->where('nama', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%");
                    });
                })
                ->orderBy('nama')
                ->get();

            if ($request->query('pdf')) {
                $title = 'Data Santri';
                $subtitle = $title.' — '.$school->name;

                if ($kelasId && $selectedKelas) {
                    $subtitle .= ' · Kelas '.$selectedKelas->nama_kelas;
                }

                if ($search) {
                    $subtitle .= ' · Cari "'.$search.'"';
                }

                return app(PdfService::class)->download(
                    'santri.pdf.index',
                    compact('exportSantris', 'title', 'subtitle'),
                    'data_santri_'.now()->format('Ymd_His').'.pdf'
                );
            }

            $filename = 'data_santri_'.now()->format('Ymd_His').'.csv';
            $columns = ['NIS', 'Nama', 'Jenis Kelamin', 'Kelas', 'Sekolah', 'No HP Wali', 'Email', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'Token Kartu'];

            $callback = function () use ($exportSantris, $columns) {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");
                fputcsv($file, $columns);

                foreach ($exportSantris as $santri) {
                    fputcsv($file, [
                        $santri->nis ?? '-',
                        $santri->nama,
                        $santri->jenis_kelamin ?? '-',
                        $santri->kelas?->nama_kelas ?? '-',
                        $santri->school?->name ?? '-',
                        $santri->no_hp_wali ?? '-',
                        $santri->email ?? '-',
                        $santri->tempat_lahir ?? '-',
                        $santri->tanggal_lahir?->format('Y-m-d') ?? '-',
                        $santri->alamat ?? '-',
                        $santri->santri_card_token ?? '-',
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        return view('santri.index', compact(
            'kelasList',
            'santris',
            'selectedKelas',
            'kelasId',
            'search',
            'isGlobalSearch',
            'showAll',
            'trashCount',
            'school',
            'schools'
        ));
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q'));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $results = Santri::with(['kelas', 'school'])
            ->where(function ($sq) use ($q) {
                $sq->where('nama', 'like', "%{$q}%")
                    ->orWhere('nis', 'like', "%{$q}%")
                    ->orWhere('santri_card_token', 'like', "%{$q}%");
            })
            ->when(! auth()->user()->isSuperadmin(), fn ($sq) => $sq->where('branch_id', auth()->user()->branch_id))
            ->orderBy('nama')
            ->limit(10)
            ->get();

        return response()->json($results->map(fn ($s) => [
            'id' => $s->id,
            'nama' => $s->nama,
            'nis' => $s->nis,
            'kelas' => $s->kelas?->nama_kelas,
            'sekolah' => $s->school?->name,
            'url' => route('santri.edit', $s),
        ]));
    }

    public function trash(Request $request)
    {
        $user = auth()->user();
        $search = trim((string) $request->query('q'));

        if ($user->isSuperadmin()) {
            $branchId = $request->query('school_id');
            if (! $branchId) {
                return redirect()->route('santri.index')
                    ->with('error', 'Pilih sekolah terlebih dahulu untuk melihat tempat sampah.');
            }
        } else {
            $branchId = $user->branch_id;
        }

        $santris = Santri::onlyTrashed()
            ->where('branch_id', $branchId)
            ->with(['kelas', 'school'])
            ->when($search, fn ($q) => $q->where(function ($sq) use ($search) {
                $sq->where('nama', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            }))
            ->orderBy('deleted_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('santri.trash', compact('santris', 'search'));
    }

    public function restore($id)
    {
        $santri = Santri::onlyTrashed()->findOrFail($id);

        // Jaga kapasitas kelas: pemulihan santri tidak boleh membuat kelas melebihi kapasitas.
        if ($santri->kelas_id) {
            $kelas = Kelas::whereKey($santri->kelas_id)->first();

            if ($kelas && (int) $kelas->kapasitas > 0) {
                $terisi = Santri::where('kelas_id', $kelas->id)->count();

                if ($terisi >= (int) $kelas->kapasitas) {
                    return redirect()->route('santri.trash', ['school_id' => $santri->branch_id])
                        ->with('error', "Santri \"{$santri->nama}\" tidak dapat dipulihkan: kelas {$kelas->nama_kelas} sudah penuh ({$terisi}/{$kelas->kapasitas}). Pindahkan sebagian santri ke kelas lain terlebih dahulu.");
                }
            }
        }

        $santri->restore();

        return redirect()->route('santri.trash', ['school_id' => $santri->branch_id])
            ->with('success', 'Santri "'.$santri->nama.'" berhasil dipulihkan.');
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $school = null;
        $kelas = collect();

        if ($user->isSuperadmin()) {
            $school = $request->query('school_id')
                ? School::findOrFail($request->query('school_id'))
                : null;

            if (! $school) {
                return redirect()->route('santri.index')
                    ->with('error', 'Pilih sekolah terlebih dahulu untuk menambah santri.');
            }

            $kelas = Kelas::where('branch_id', $school->id)->withCount('santris')->orderBy('nama_kelas')->get();
        } else {
            $school = $user->school;
            $kelas = Kelas::where('branch_id', $school->id)->withCount('santris')->orderBy('nama_kelas')->get();
        }

        return view('santri.create', compact('kelas', 'school'));
    }

    public function store(Request $request)
    {
        $branchId = $this->resolveBranchId($request);

        $validated = $request->validate([
            'nis' => ['nullable', 'string', 'max:10', Rule::unique('santris')->withoutTrashed()->where(fn ($q) => $q->where('branch_id', $branchId))],
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'no_hp_wali' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value && ! preg_match('/^(?:\+62|62|0)[0-9]{8,13}$/', $value)) {
                    $fail('Format nomor telepon tidak valid. Gunakan 0 atau +62 diikuti 8–13 digit, contoh: 081234567890.');
                }
            }],
            'email' => 'nullable|email|max:255',
            'santri_card_token' => ['nullable', 'string', 'max:255', Rule::unique('santris')->withoutTrashed()->where(fn ($q) => $q->where('branch_id', $branchId))],
            'kelas_id' => ['required', Rule::exists('kelas', 'id')->where(fn ($q) => $q->where('branch_id', $branchId))],
        ]);

        foreach ($validated as $key => $value) {
            if ($value === '') {
                $validated[$key] = null;
            }
        }

        $validated['branch_id'] = $branchId;

        DB::transaction(function () use ($validated, $branchId) {
            $kelas = Kelas::where('branch_id', $branchId)->lockForUpdate()->findOrFail($validated['kelas_id']);
            $this->ensureKelasCapacity($kelas);

            Santri::create($validated);
        });

        return redirect()->route('santri.index', $this->schoolQuery($branchId))
            ->with('success', 'Data santri berhasil ditambahkan.');
    }

    public function edit(Santri $santri)
    {
        $user = auth()->user();
        $school = $user->isSuperadmin() ? $santri->school : $user->school;
        $kelas = Kelas::where('branch_id', $school->id)->withCount('santris')->orderBy('nama_kelas')->get();

        return view('santri.edit', compact('santri', 'kelas', 'school'));
    }

    public function update(Request $request, Santri $santri)
    {
        $branchId = $santri->branch_id;

        $validated = $request->validate([
            'nis' => ['nullable', 'string', 'max:10', Rule::unique('santris')->ignore($santri->id)->withoutTrashed()->where(fn ($q) => $q->where('branch_id', $branchId))],
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'no_hp_wali' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value && ! preg_match('/^(?:\+62|62|0)[0-9]{8,13}$/', $value)) {
                    $fail('Format nomor telepon tidak valid. Gunakan 0 atau +62 diikuti 8–13 digit, contoh: 081234567890.');
                }
            }],
            'email' => 'nullable|email|max:255',
            'santri_card_token' => ['nullable', 'string', 'max:255', Rule::unique('santris')->ignore($santri->id)->withoutTrashed()->where(fn ($q) => $q->where('branch_id', $branchId))],
            'kelas_id' => ['required', Rule::exists('kelas', 'id')->where(fn ($q) => $q->where('branch_id', $branchId))],
        ]);

        foreach ($validated as $key => $value) {
            if ($value === '') {
                $validated[$key] = null;
            }
        }

        DB::transaction(function () use ($validated, $santri) {
            if ((int) $validated['kelas_id'] !== (int) $santri->kelas_id) {
                $kelas = Kelas::where('branch_id', $santri->branch_id)->lockForUpdate()->findOrFail($validated['kelas_id']);
                $this->ensureKelasCapacity($kelas, $santri->id);
            }

            $santri->update($validated);
        });

        return redirect()->route('santri.index', $this->schoolQuery($santri->branch_id))
            ->with('success', 'Data santri berhasil diperbarui.');
    }

    public function destroy(Santri $santri)
    {
        $santri->delete();

        return redirect()->route('santri.index', $this->schoolQuery($santri->branch_id))
            ->with('success', 'Data santri berhasil dihapus.');
    }

    public function destroyByKelas(Kelas $kelas)
    {
        $this->authorizeKelas($kelas);

        $count = Santri::where('kelas_id', $kelas->id)->count();

        if ($count === 0) {
            return redirect()->route('santri.index', $this->schoolQuery($kelas->branch_id))
                ->with('error', 'Tidak ada santri di kelas '.$kelas->nama_kelas.'.');
        }

        Santri::where('kelas_id', $kelas->id)->delete();

        return redirect()->route('santri.index', $this->schoolQuery($kelas->branch_id))
            ->with('success', "{$count} santri dari kelas {$kelas->nama_kelas} berhasil dihapus (soft delete).");
    }

    public function destroyAll(Request $request)
    {
        $branchId = $this->resolveBranchId($request);

        $count = Santri::where('branch_id', $branchId)->count();

        if ($count === 0) {
            return redirect()->route('santri.index', $this->schoolQuery($branchId))
                ->with('error', 'Tidak ada santri untuk dihapus.');
        }

        Santri::where('branch_id', $branchId)->delete();

        return redirect()->route('santri.index', $this->schoolQuery($branchId))
            ->with('success', "{$count} santri berhasil dihapus (soft delete).");
    }

    protected function ensureKelasCapacity(Kelas $kelas, ?int $ignoreSantriId = null): void
    {
        $kapasitas = (int) $kelas->kapasitas;

        if ($kapasitas <= 0) {
            return;
        }

        $query = Santri::where('kelas_id', $kelas->id);

        if ($ignoreSantriId) {
            $query->whereKeyNot($ignoreSantriId);
        }

        if ($query->count() >= $kapasitas) {
            throw ValidationException::withMessages([
                'kelas_id' => 'Kapasitas kelas sudah penuh. Tidak dapat menambahkan santri lagi.',
            ]);
        }
    }

    protected function authorizeKelas(Kelas $kelas): void
    {
        if (! auth()->user()->isSuperadmin() && $kelas->branch_id !== auth()->user()->branch_id) {
            abort(403);
        }
    }
}
