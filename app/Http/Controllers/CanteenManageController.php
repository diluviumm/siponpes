<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSchoolContext;
use App\Models\AuditLog;
use App\Models\Canteen;
use App\Models\CanteenItem;
use App\Models\School;
use App\Support\CanteenUnits;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CanteenManageController extends Controller
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
                ->withCount('canteens')
                ->orderBy('name')
                ->get();
        } else {
            $school = $user->school;
        }

        $q = $request->query('q');

        $canteens = $school
            ? Canteen::withCount(['items', 'transactions'])
                ->with(['transactions' => fn ($query) => $query
                    ->with('items')
                    ->orderByDesc('transaction_date')
                    ->orderByDesc('id')])
                ->where('branch_id', $school->id)
                ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
                ->orderBy('name')
                ->get()
            : collect();

        $deletedCanteens = $school
            ? Canteen::onlyTrashed()
                ->where('branch_id', $school->id)
                ->orderByDesc('deleted_at')
                ->get()
            : collect();

        return view('kantin.manage', compact('school', 'schools', 'canteens', 'deletedCanteens', 'q'));
    }

    public function show(Canteen $canteen)
    {
        $school = $canteen->school;

        $canteen->load('items');

        return view('kantin.manage_show', compact('canteen', 'school'));
    }

    public function storeCanteen(Request $request)
    {
        $branchId = $this->resolveBranchId($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('canteens', 'name')
                    ->where('branch_id', $branchId)
                    ->withoutTrashed(),
            ],
        ]);

        $existing = Canteen::withTrashed()
            ->where('branch_id', $branchId)
            ->where('name', $validated['name'])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->items()->withTrashed()->restore();
                $existing->restore();

                return redirect()->route('kantin.manage', $this->schoolQuery($branchId))
                    ->with('success', "Kantin '{$existing->name}' yang sebelumnya dihapus berhasil dipulihkan.");
            }

            return back()->withErrors(['name' => 'Nama kantin sudah ada di sekolah ini.']);
        }

        Canteen::create([
            'branch_id' => $branchId,
            'name' => $validated['name'],
            'is_active' => true,
        ]);

        return redirect()->route('kantin.manage', $this->schoolQuery($branchId))
            ->with('success', 'Kantin berhasil ditambahkan.');
    }

    public function destroyCanteen(Canteen $canteen)
    {
        $branchId = $canteen->branch_id;

        $canteen->items()->delete();
        $canteen->delete();

        return redirect()->route('kantin.manage', $this->schoolQuery($branchId))
            ->with('success', 'Kantin berhasil dihapus. Anda dapat memulihkannya dari bagian "Kantin Terhapus" di halaman ini.');
    }

    public function toggleCanteen(Canteen $canteen)
    {
        $branchId = $canteen->branch_id;

        $canteen->update(['is_active' => ! $canteen->is_active]);

        $state = $canteen->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        return redirect()->route('kantin.manage', $this->schoolQuery($branchId))
            ->with('success', "Kantin '{$canteen->name}' berhasil {$state}. Kantin yang dinonaktifkan tidak muncul di Kasir Kantin.");
    }

    public function canteenRiwayat(Canteen $canteen)
    {
        $school = $canteen->school;

        $canteenLogs = AuditLog::with('user')
            ->where('auditable_type', Canteen::class)
            ->where('auditable_id', $canteen->id)
            ->where('action', 'deleted')
            ->orderByDesc('id')
            ->get();

        $items = CanteenItem::withTrashed()
            ->where('canteen_id', $canteen->id)
            ->get()
            ->keyBy('id');

        $itemLogs = AuditLog::with('user')
            ->where('auditable_type', CanteenItem::class)
            ->whereIn('auditable_id', $items->pluck('id'))
            ->where('action', 'deleted')
            ->orderByDesc('id')
            ->get();

        return view('kantin.canteen_riwayat', compact('canteen', 'school', 'canteenLogs', 'itemLogs', 'items'));
    }

    public function restoreCanteen($canteen)
    {
        $canteen = Canteen::withTrashed()->findOrFail($canteen);
        $branchId = $canteen->branch_id;

        $canteen->items()->withTrashed()->restore();
        $canteen->restore();

        return redirect()->route('kantin.manage', $this->schoolQuery($branchId))
            ->with('success', "Kantin '{$canteen->name}' berhasil dipulihkan beserta menunya.");
    }

    public function restoreItem($item)
    {
        $item = CanteenItem::withTrashed()->findOrFail($item);

        $canteen = $item->canteen()->withTrashed()->first();
        $branchId = $canteen?->branch_id;

        if (! auth()->user()->isSuperadmin() && $branchId !== auth()->user()->branch_id) {
            abort(403);
        }

        $item->restore();

        return redirect()->route('kantin.canteen.riwayat', $canteen)
            ->with('success', "Menu '{$item->name}' berhasil dipulihkan.");
    }

    public function storeItem(Request $request)
    {
        $branchId = $this->resolveBranchId($request);

        $validated = $request->validate([
            'canteen_id' => ['required', Rule::exists('canteens', 'id')->where('branch_id', $branchId)],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', Rule::in(CanteenUnits::all())],
            'stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $canteen = Canteen::findOrFail($validated['canteen_id']);

        $existing = CanteenItem::withTrashed()
            ->where('canteen_id', $canteen->id)
            ->where('name', $validated['name'])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();

                return redirect()->route('kantin.manage.show', $canteen)
                    ->with('success', "Menu '{$existing->name}' yang sebelumnya dihapus berhasil dipulihkan.");
            }

            return back()->withErrors(['name' => 'Menu dengan nama ini sudah ada di kantin tersebut.']);
        }

        CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => $validated['name'],
            'price' => $validated['price'],
            'unit' => $validated['unit'],
            'stock' => (int) ($validated['stock'] ?? 0),
            'is_active' => true,
        ]);

        return redirect()->route('kantin.manage.show', $canteen)
            ->with('success', 'Menu kantin berhasil ditambahkan.');
    }

    public function updateItem(Request $request, CanteenItem $item)
    {
        $canteen = $item->canteen()->withTrashed()->first();

        if (! auth()->user()->isSuperadmin() && $canteen?->branch_id !== auth()->user()->branch_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', Rule::in(CanteenUnits::all())],
            'stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $item->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'unit' => $validated['unit'],
            'stock' => (int) ($validated['stock'] ?? 0),
            'is_active' => $request->boolean('is_active', $item->is_active),
        ]);

        return redirect()->route('kantin.manage.show', $canteen)
            ->with('success', 'Menu kantin berhasil diperbarui.');
    }

    public function destroyItem(CanteenItem $item)
    {
        $canteen = $item->canteen()->withTrashed()->first();

        if (! auth()->user()->isSuperadmin() && $canteen?->branch_id !== auth()->user()->branch_id) {
            abort(403);
        }

        $item->delete();

        return redirect()->route('kantin.manage.show', $canteen)
            ->with('success', 'Menu kantin berhasil dihapus.');
    }
}
