<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSchoolContext;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\School;
use App\Models\SppTariff;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SppTariffController extends Controller
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
                ->withCount('sppTariffs')
                ->orderBy('name')
                ->get();
        } else {
            $school = $user->school;
        }

        $tariffs = $school
            ? SppTariff::with('kelas')
                ->where('branch_id', $school->id)
                ->orderByDesc('academic_year')
                ->paginate(20)
            : collect();

        return view('spp_tariffs.index', compact('tariffs', 'school', 'schools'));
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

        $classes = $school
            ? Kelas::where('branch_id', $school->id)->orderBy('nama_kelas')->get()
            : collect();

        $angkatans = $school ? $this->angkatanOptions($school->id) : [];

        return view('spp_tariffs.create', compact('classes', 'angkatans', 'school', 'schools'));
    }

    public function store(Request $request)
    {
        $branchId = $this->resolveBranchId($request);

        $validated = $request->validate([
            'type' => ['required', 'in:semua,kelas,angkatan'],
            'periode_type' => ['required', 'in:bulanan,semester'],
            'kelas_id' => ['nullable', 'required_if:type,kelas', Rule::exists('kelas', 'id')->where(fn ($q) => $q->where('branch_id', $branchId))],
            'angkatan' => ['nullable', 'required_if:type,angkatan', 'integer', 'min:1', 'max:12'],
            'amount' => 'required|numeric|min:0',
            'academic_year' => $this->academicYearRules(),
        ]);

        $this->validateTariffScope($request, $branchId);

        if ($validated['type'] === 'semua') {
            $validated['kelas_id'] = null;
            $validated['angkatan'] = null;
        } elseif ($validated['type'] === 'angkatan') {
            $validated['kelas_id'] = null;
        } else {
            $validated['angkatan'] = null;
        }

        $validated['branch_id'] = $branchId;

        SppTariff::create($validated);

        return redirect()->route('spp_tariffs.index', $this->schoolQuery($branchId))
            ->with('success', 'Tarif SPP berhasil ditambahkan.');
    }

    public function edit(Request $request, SppTariff $spp_tariff)
    {
        $user = auth()->user();
        $school = $user->isSuperadmin() ? $spp_tariff->school : $user->school;

        if (! $school) {
            abort(404);
        }

        $classes = Kelas::where('branch_id', $school->id)->orderBy('nama_kelas')->get();
        $angkatans = $this->angkatanOptions($school->id);

        return view('spp_tariffs.edit', compact('spp_tariff', 'classes', 'angkatans', 'school'));
    }

    public function update(Request $request, SppTariff $spp_tariff)
    {
        $branchId = $spp_tariff->branch_id;

        $validated = $request->validate([
            'type' => ['required', 'in:semua,kelas,angkatan'],
            'periode_type' => ['required', 'in:bulanan,semester'],
            'kelas_id' => ['nullable', 'required_if:type,kelas', Rule::exists('kelas', 'id')->where(fn ($q) => $q->where('branch_id', $branchId))],
            'angkatan' => ['nullable', 'required_if:type,angkatan', 'integer', 'min:1', 'max:12'],
            'amount' => 'required|numeric|min:0',
            'academic_year' => $this->academicYearRules(),
        ]);

        $this->validateTariffScope($request, $branchId, $spp_tariff->id);

        if ($validated['type'] === 'semua') {
            $validated['kelas_id'] = null;
            $validated['angkatan'] = null;
        } elseif ($validated['type'] === 'angkatan') {
            $validated['kelas_id'] = null;
        } else {
            $validated['angkatan'] = null;
        }

        $spp_tariff->update($validated);

        return redirect()->route('spp_tariffs.index', $this->schoolQuery($branchId))
            ->with('success', 'Tarif SPP berhasil diperbarui.');
    }

    public function destroy(SppTariff $spp_tariff)
    {
        $branchId = $spp_tariff->branch_id;

        $spp_tariff->delete();

        return redirect()->route('spp_tariffs.index', $this->schoolQuery($branchId))
            ->with('success', 'Tarif SPP berhasil dihapus.');
    }

    protected function angkatanOptions(int $branchId): array
    {
        $fromSantri = Santri::where('branch_id', $branchId)
            ->whereNotNull('angkatan')
            ->distinct()
            ->pluck('angkatan');

        $fromTariff = SppTariff::where('branch_id', $branchId)
            ->whereNotNull('angkatan')
            ->distinct()
            ->pluck('angkatan');

        $values = $fromSantri->merge($fromTariff)
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $values ?: [7, 8, 9];
    }

    protected function validateTariffScope(Request $request, int $branchId, ?int $ignoreId = null): void
    {
        $type = $request->input('type');
        $year = $request->input('academic_year');

        $query = SppTariff::where('branch_id', $branchId)->where('academic_year', $year);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        $exists = match ($type) {
            'semua' => (clone $query)->where('type', 'semua')->exists(),
            'angkatan' => (clone $query)->where('type', 'angkatan')->where('angkatan', $request->input('angkatan'))->exists(),
            'kelas' => (clone $query)->where('type', 'kelas')->where('kelas_id', $request->input('kelas_id'))->exists(),
            default => false,
        };

        if ($exists) {
            throw ValidationException::withMessages([
                'type' => 'Tarif SPP untuk cakupan dan tahun ajaran tersebut sudah ada. Silakan edit tarif yang sudah ada.',
            ]);
        }
    }

    protected function academicYearRules(): array
    {
        return [
            'required',
            'string',
            'max:9',
            'regex:/^\d{4}\/\d{4}$/',
            function ($attribute, $value, $fail) {
                if (! preg_match('/^\d{4}\/\d{4}$/', $value)) {
                    return;
                }

                [$start, $end] = explode('/', $value);

                if ((int) $end !== ((int) $start + 1)) {
                    $fail('Format tahun ajaran tidak valid. Tahun kedua harus satu tahun setelah tahun pertama, contoh: 2025/2026.');
                }
            },
        ];
    }
}
