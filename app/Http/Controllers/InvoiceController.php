<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\School;
use App\Models\SppTariff;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\InvoiceReminderService;
use App\Services\JournalService;
use App\Services\PdfService;
use App\Support\AccountCodes;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $branchId = $request->query('branch_id');
        $search = $request->query('search');
        $kelasId = $request->query('kelas_id');
        $type = $request->query('type', 'spp');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $invoices = $this->invoiceQuery($status, $branchId, $search, $type, $kelasId, $fromDate, $toDate)
            ->paginate(20)
            ->withQueryString();

        $schools = auth()->user()->isSuperadmin()
            ? School::where('status', School::STATUS_ACTIVE)->orderBy('name')->get()
            : collect();

        // Kelas list for filter pills — only if a school is selected
        $kelasList = collect();
        $schoolId = $branchId ?? ($schools->count() === 1 ? $schools->first()->id : null);
        if (! $schoolId && ! auth()->user()->isSuperadmin()) {
            $schoolId = auth()->user()->branch_id;
        }
        if ($schoolId) {
            $kelasList = Kelas::where('branch_id', $schoolId)
                ->withCount(['santris' => function ($q) use ($type) {
                    $q->whereHas('invoices', function ($iq) use ($type) {
                        if ($type === 'spp') {
                            $iq->whereNotNull('period_label');
                        } elseif ($type === 'manual') {
                            $iq->whereNull('period_label');
                        }
                    });
                }])
                ->orderBy('nama_kelas')
                ->get();
        }

        $overdueCount = Invoice::query()
            ->filterSchool($branchId)
            ->where('status', Invoice::STATUS_UNPAID)
            ->whereDate('due_date', '<=', now())
            ->count();

        $unpaidCount = Invoice::query()
            ->filterSchool($branchId)
            ->where('status', Invoice::STATUS_UNPAID)
            ->count();

        return view('invoice.index', compact(
            'invoices',
            'schools',
            'status',
            'branchId',
            'overdueCount',
            'unpaidCount',
            'search',
            'kelasId',
            'kelasList',
            'type',
            'fromDate',
            'toDate'
        ));
    }

    public function export(Request $request)
    {
        $status = $request->query('status');
        $branchId = $request->query('branch_id');
        $search = $request->query('search');
        $kelasId = $request->query('kelas_id');
        $type = $request->query('type', 'spp');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $invoices = $this->invoiceQuery($status, $branchId, $search, $type, $kelasId, $fromDate, $toDate)->get();

        if ($request->query('pdf')) {
            $schoolName = $branchId
                ? School::find($branchId)?->name
                : (auth()->user()->isSuperadmin()
                    ? 'Semua Sekolah (Gabungan)'
                    : auth()->user()->school?->name);

            $title = 'Daftar Invoice SPP';
            $subtitle = $schoolName ? $title.' — '.$schoolName : $title;

            return app(PdfService::class)->download(
                'invoice.pdf.index',
                compact('invoices', 'title', 'subtitle'),
                'daftar_invoice_'.now()->format('Ymd_His').'.pdf'
            );
        }

        $filename = 'invoices_'.now()->format('Ymd_His').'.csv';
        $columns = ['Invoice Number', 'Santri', 'NIS', 'Sekolah', 'Kelas', 'Total Amount', 'Status', 'Due Date', 'Created At'];

        $callback = function () use ($invoices, $columns) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);

            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->invoice_number,
                    $invoice->santri->nama,
                    $invoice->santri->nis,
                    $invoice->santri->school?->name ?? '-',
                    $invoice->santri->kelas?->nama_kelas ?? '-',
                    $invoice->total_amount,
                    ucfirst($invoice->status),
                    $invoice->due_date->format('Y-m-d'),
                    $invoice->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['santri.school', 'santri.kelas', 'santri.wallet', 'items', 'reminders']);

        $title = 'Bukti Tagihan SPP';
        $subtitle = $invoice->santri->school
            ? $title.' — '.$invoice->santri->school->name
            : $title;

        return app(PdfService::class)->download(
            'invoice.pdf.receipt',
            compact('invoice', 'title', 'subtitle'),
            'bukti_invoice_'.$invoice->invoice_number.'.pdf'
        );
    }

    public function create(Request $request)
    {
        $santris = Santri::with(['kelas', 'school'])
            ->when(! auth()->user()->isSuperadmin(), function ($q) {
                $q->where('branch_id', auth()->user()->branch_id);
            })
            ->when(auth()->user()->isSuperadmin() && $request->query('school_id'), function ($q) use ($request) {
                $q->where('branch_id', $request->query('school_id'));
            })
            ->orderBy('nama')
            ->get();

        return view('invoice.create', compact('santris'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'santri_id' => $this->santriIdRule(true),
            'due_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $invoice = DB::transaction(function () use ($request) {
            $invoice = Invoice::create([
                'santri_id' => $request->santri_id,
                'invoice_number' => $this->buildInvoiceNumber(Santri::find($request->santri_id), Carbon::now()->format('Ym')),
                'total_amount' => $request->amount,
                'status' => Invoice::STATUS_UNPAID,
                'due_date' => $request->due_date,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $request->description,
                'quantity' => 1,
                'unit_price' => $request->amount,
                'total_amount' => $request->amount,
            ]);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice berhasil dibuat.');
    }

    public function remind(Invoice $invoice)
    {
        if ($invoice->status !== Invoice::STATUS_UNPAID) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Pengingat hanya dapat dikirim untuk invoice yang belum dibayar.');
        }

        $service = app(InvoiceReminderService::class);
        $message = $service->buildMessage($invoice);
        $channel = $service->sendReminder($invoice, $message, force: true);

        if ($channel === 'none') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Pengingat tidak dapat dikirim. Santri tidak punya email, dan WhatsApp tidak terkirim (pastikan nomor wali terisi & gateway WA terkonfigurasi).');
        }

        if ($channel === 'email_failed') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Gagal mengirim email pengingat (masalah server email/SMTP). Periksa pengaturan MAIL di .env, lalu coba lagi.');
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Pengingat invoice berhasil dikirim melalui '.strtoupper($channel).'.');
    }

    public function remindOverdue(Request $request)
    {
        $invoices = Invoice::with(['santri'])
            ->filterSchool($request->input('branch_id'))
            ->where('status', Invoice::STATUS_UNPAID)
            ->whereDate('due_date', '<=', now())
            ->get();

        return $this->sendBulkReminders($invoices, overdue: true);
    }

    public function remindAllUnpaid(Request $request)
    {
        $invoices = Invoice::with(['santri'])
            ->filterSchool($request->input('branch_id'))
            ->where('status', Invoice::STATUS_UNPAID)
            ->get();

        return $this->sendBulkReminders($invoices, overdue: false);
    }

    protected function sendBulkReminders($invoices, bool $overdue)
    {
        $service = app(InvoiceReminderService::class);

        $sentCount = 0;
        $skippedCount = 0;
        $noContactCount = 0;
        $emailFailedCount = 0;

        foreach ($invoices as $invoice) {
            $message = $service->buildMessage($invoice, $overdue);
            $channel = $service->sendReminder($invoice, $message);

            if ($channel === 'skipped') {
                $skippedCount++;
            } elseif ($channel === 'email_failed') {
                $emailFailedCount++;
            } elseif ($channel === 'none') {
                $noContactCount++;
            } else {
                $sentCount++;
            }
        }

        $total = $invoices->count();

        if ($total === 0) {
            $label = $overdue ? 'tidak ada invoice unpaid yang sudah jatuh tempo untuk diingatkan.' : 'tidak ada invoice unpaid untuk diingatkan.';

            return redirect()->route('invoices.index')
                ->with('error', ucfirst($label));
        }

        $label = $overdue ? 'Pengingat overdue berhasil dikirim' : 'Pengingat berhasil dikirim ke semua invoice belum dibayar';
        $message = "{$label} ke {$sentCount} dari {$total} invoice.";

        if ($skippedCount > 0) {
            $message .= " {$skippedCount} invoice dilewati karena baru saja diingatkan.";
        }

        if ($emailFailedCount > 0) {
            $message .= " {$emailFailedCount} invoice gagal dikirim email (masalah server email/SMTP).";
        }

        if ($noContactCount > 0) {
            $message .= " {$noContactCount} invoice tidak dikirim karena santri tidak punya email atau nomor wali.";
        }

        return redirect()->route('invoices.index')
            ->with('success', $message);
    }

    protected function invoiceQuery(?string $status, ?int $branchId, ?string $search = null, ?string $type = null, ?int $kelasId = null, ?string $fromDate = null, ?string $toDate = null)
    {
        return Invoice::with(['santri.school', 'santri.kelas', 'santri.wallet'])
            ->filterSchool($branchId)
            ->when($type === 'spp', fn ($q) => $q->whereNotNull('period_label'))
            ->when($type === 'manual', fn ($q) => $q->whereNull('period_label'))
            ->when($kelasId, fn ($q) => $q->whereHas('santri', fn ($sq) => $sq->where('kelas_id', $kelasId)))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('santri', fn ($s) => $s->where('nama', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%"));
                });
            })
            ->when($status === 'overdue', function ($q) {
                $q->where('status', Invoice::STATUS_UNPAID)
                    ->whereDate('due_date', '<', now());
            })
            ->when($status === 'upcoming', function ($q) {
                $q->where('status', Invoice::STATUS_UNPAID)
                    ->whereBetween('due_date', [now(), now()->addDays(7)]);
            })
            ->when(! in_array($status, ['overdue', 'upcoming'], true), function ($q) use ($status) {
                $q->filterStatus($status);
            })
            ->when($fromDate, fn ($q) => $q->whereDate('due_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('due_date', '<=', $toDate))
            ->orderByDesc('created_at');
    }

    protected function santriIdRule(bool $required): array
    {
        $rules = [$required ? 'required' : 'sometimes'];

        $rules[] = Rule::exists('santris', 'id')->where(function ($query) {
            $user = auth()->user();

            if (! $user->isSuperadmin()) {
                $query->where('branch_id', $user->branch_id);
            }
        });

        return $rules;
    }

    protected function buildInvoiceNumber(Santri $santri, string $monthLabel): string
    {
        return sprintf('INV-%s-%s-%s', $santri->id, $monthLabel, Str::upper(Str::random(4)));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['santri.school', 'santri.kelas', 'santri.wallet', 'items', 'reminders']);

        return view('invoice.show', compact('invoice'));
    }

    public function reminders(Invoice $invoice)
    {
        $invoice->load(['santri.school', 'santri.kelas', 'reminders']);

        return view('invoice.reminders', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->status === Invoice::STATUS_PAID) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Invoice sudah lunas dan tidak dapat diubah.');
        }

        $invoice->load(['items']);
        $santris = Santri::with(['kelas', 'school'])
            ->when(! auth()->user()->isSuperadmin(), function ($q) {
                $q->where('branch_id', auth()->user()->branch_id);
            })
            ->orderBy('nama')
            ->get();

        return view('invoice.edit', compact('invoice', 'santris'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status === Invoice::STATUS_PAID) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Invoice sudah lunas dan tidak dapat diubah.');
        }

        $request->validate([
            'santri_id' => $this->santriIdRule(false),
            'due_date' => 'sometimes|date',
            'amount' => 'sometimes|numeric|min:0.01',
            'description' => 'sometimes|string|max:255',
            'status' => 'required|in:unpaid,expired',
        ]);

        DB::transaction(function () use ($invoice, $request) {
            $updates = ['status' => $request->status];

            if ($request->filled('santri_id')) {
                $updates['santri_id'] = $request->santri_id;
            }
            if ($request->filled('due_date')) {
                $updates['due_date'] = $request->due_date;
            }
            if ($request->filled('amount')) {
                $updates['total_amount'] = $request->amount;
            }

            if (count($updates) > 0) {
                $invoice->update($updates);
            }

            if ($request->filled('description') || $request->filled('amount')) {
                $item = $invoice->items()->first();

                if (! $item) {
                    $item = new InvoiceItem(['invoice_id' => $invoice->id]);
                }

                $item->description = $request->input('description', $item->description ?? 'SPP Bulanan');
                $item->quantity = 1;
                $item->unit_price = $request->input('amount', $item->unit_price ?? $invoice->total_amount);
                $item->total_amount = $item->unit_price;
                $item->invoice_id = $invoice->id;
                $item->save();
            }
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice berhasil diperbarui.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === Invoice::STATUS_PAID) {
            return redirect()->route('invoices.index')
                ->with('error', 'Invoice sudah lunas dan tidak dapat dihapus.');
        }

        DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            $invoice->delete();
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice berhasil dihapus.');
    }

    public function pay(Invoice $invoice)
    {
        if ($invoice->status !== Invoice::STATUS_UNPAID) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Invoice hanya dapat dibayar jika statusnya belum dibayar.');
        }

        if (! $invoice->santri) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Data santri tidak ditemukan, invoice tidak dapat dibayar.');
        }

        $wallet = $invoice->santri->wallet;

        if (! $wallet) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Santri belum memiliki wallet. Harap isi saldo terlebih dahulu.');
        }

        if ($wallet->balance < $invoice->total_amount) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Saldo wallet tidak mencukupi untuk membayar invoice ini.');
        }

        // Proteksi bayar ganda untuk invoice berperiode
        if ($invoice->period_label) {
            $sudahBayar = Invoice::where('santri_id', $invoice->santri_id)
                ->where('period_label', $invoice->period_label)
                ->where('status', Invoice::STATUS_PAID)
                ->where('id', '!=', $invoice->id)
                ->exists();

            if ($sudahBayar) {
                return redirect()->route('invoices.show', $invoice)
                    ->with('error', 'Santri sudah membayar SPP periode '.$invoice->period_label.'.');
            }
        }

        DB::transaction(function () use ($wallet, $invoice) {
            $lockedWallet = Wallet::lockForUpdate()->find($wallet->id);

            if ($lockedWallet->balance < $invoice->total_amount) {
                throw new HttpResponseException(
                    redirect()->route('invoices.show', $invoice)
                        ->with('error', 'Saldo wallet tidak mencukupi untuk membayar invoice ini.')
                );
            }

            $lockedWallet->decrement('balance', $invoice->total_amount);

            WalletTransaction::create([
                'wallet_id' => $lockedWallet->id,
                'amount' => $invoice->total_amount,
                'type' => 'debit',
                'description' => "Pembayaran invoice {$invoice->invoice_number}",
                'reference_id' => $invoice->id,
            ]);

            app(JournalService::class)->createJournalEntry(
                "Pembayaran SPP untuk invoice {$invoice->invoice_number}",
                "INV-{$invoice->id}",
                [
                    ['account_code' => AccountCodes::WALLET_LIABILITY, 'debit' => $invoice->total_amount, 'credit' => 0],
                    ['account_code' => AccountCodes::SPP_REVENUE, 'debit' => 0, 'credit' => $invoice->total_amount],
                ],
                now()->toDateString(),
                $invoice->santri->branch_id
            );

            $invoice->update(['status' => Invoice::STATUS_PAID]);
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice berhasil dibayar melalui wallet.');
    }

    /**
     * Halaman Pembayaran SPP — daftar santri per kelas + tombol bayar.
     */
    public function sppPayment(Request $request)
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

            if (! $school) {
                return view('invoice.spp_payment', compact('school', 'schools'));
            }
        } else {
            $school = $user->school;
        }

        $kelasId = $request->query('kelas_id');
        $search = $request->query('search');

        $kelasList = $school
            ? Kelas::where('branch_id', $school->id)->withCount('santris')->orderBy('nama_kelas')->get()
            : collect();

        $selectedKelas = null;
        $santris = collect();

        if ($school && $kelasId) {
            $selectedKelas = $kelasList->firstWhere('id', (int) $kelasId);
            $santris = Santri::with(['kelas', 'wallet', 'school'])
                ->where('kelas_id', $kelasId)
                ->when($search, fn ($q) => $q->where('nama', 'like', "%{$search}%"))
                ->orderBy('nama')
                ->get();
        }

        $tariffs = $school
            ? SppTariff::where('branch_id', $school->id)->orderBy('academic_year')->get()
            : collect();

        $hasTariffs = $tariffs->isNotEmpty();

        $tariffByKelas = [];
        $tariffByAngkatan = [];
        $tariffSemua = 0;
        $periodeTypeByKelas = [];
        $periodeTypeByAngkatan = [];
        $periodeTypeSemua = 'bulanan';
        $tahunAjaranByKelas = [];
        $tahunAjaranByAngkatan = [];
        $tahunAjaranSemua = null;

        foreach ($tariffs as $t) {
            if ($t->type === 'semua') {
                $tariffSemua = (float) $t->amount;
                $periodeTypeSemua = $t->periode_type ?? 'bulanan';
                $tahunAjaranSemua = $t->academic_year;
            } elseif ($t->type === 'kelas') {
                $tariffByKelas[$t->kelas_id] = (float) $t->amount;
                $periodeTypeByKelas[$t->kelas_id] = $t->periode_type ?? 'bulanan';
                $tahunAjaranByKelas[$t->kelas_id] = $t->academic_year;
            } elseif ($t->type === 'angkatan') {
                $tariffByAngkatan[$t->angkatan] = (float) $t->amount;
                $periodeTypeByAngkatan[$t->angkatan] = $t->periode_type ?? 'bulanan';
                $tahunAjaranByAngkatan[$t->angkatan] = $t->academic_year;
            }
        }

        $bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $nextPeriods = [];
        foreach ($santris as $s) {
            $tarif = $tariffByKelas[$s->kelas_id] ?? $tariffByAngkatan[$s->angkatan] ?? $tariffSemua;
            if ($tarif <= 0) {
                $nextPeriods[$s->id] = null;

                continue;
            }

            $periodeType = $periodeTypeByKelas[$s->kelas_id] ?? $periodeTypeByAngkatan[$s->angkatan] ?? $periodeTypeSemua;
            $academicYear = $tahunAjaranByKelas[$s->kelas_id] ?? $tahunAjaranByAngkatan[$s->angkatan] ?? $tahunAjaranSemua;

            $nextPeriods[$s->id] = $this->getNextPeriod($s->id, $periodeType, $academicYear);
        }

        // Acuan dropdown batch — ikuti jenis periode & tahun ajaran tarif terbaru
        $batchTariff = $tariffs->last();
        $periodeTypeBatch = $batchTariff?->periode_type ?? 'bulanan';
        $academicYearBatch = $batchTariff?->academic_year;

        return view('invoice.spp_payment', compact(
            'school', 'schools', 'kelasList', 'selectedKelas',
            'santris', 'kelasId', 'search', 'tariffByKelas',
            'tariffByAngkatan', 'tariffSemua', 'nextPeriods', 'bulanIndo',
            'periodeTypeBatch', 'academicYearBatch', 'hasTariffs'
        ));
    }

    public function sppPay(Request $request)
    {
        $validated = $request->validate([
            'santri_id' => ['required', 'exists:santris,id'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $santri = Santri::findOrFail($validated['santri_id']);
        $branchId = $santri->branch_id;

        $user = auth()->user();
        if (! $user->isSuperadmin() && $user->branch_id !== $branchId) {
            abort(403);
        }

        $tariff = $this->resolveTariffForSantri($santri);

        if (! $tariff) {
            return redirect()->route('invoices.spp-payment',
                $user->isSuperadmin() ? ['school_id' => $branchId, 'kelas_id' => $santri->kelas_id] : ['kelas_id' => $santri->kelas_id]
            )->with('error', 'Belum tersedia Tarif SPP untuk sekolah ini. Silakan tambahkan Tarif SPP terlebih dahulu melalui menu Tarif SPP.');
        }

        $amount = (float) $tariff->amount;

        if ($amount <= 0) {
            return redirect()->route('invoices.spp-payment',
                $user->isSuperadmin() ? ['school_id' => $branchId, 'kelas_id' => $santri->kelas_id] : ['kelas_id' => $santri->kelas_id]
            )->with('error', 'Tarif SPP untuk santri ini belum diatur dengan jumlah yang valid.');
        }

        $periodeType = $tariff->periode_type ?? 'bulanan';
        $academicYear = $tariff->academic_year;

        $nextPeriod = $this->getNextPeriod($santri->id, $periodeType, $academicYear);
        if (! $nextPeriod) {
            return redirect()->route('invoices.spp-payment',
                $user->isSuperadmin() ? ['school_id' => $branchId, 'kelas_id' => $santri->kelas_id] : ['kelas_id' => $santri->kelas_id]
            )->with('error', 'Semua periode SPP sudah lunas untuk santri ini.');
        }

        $wallet = $santri->wallet;
        if (! $wallet) {
            return redirect()->back()->with('error', 'Santri belum memiliki wallet.');
        }

        if ($wallet->balance < $amount) {
            return redirect()->back()->with('error',
                'Saldo wallet tidak mencukupi. Saldo: Rp '.number_format($wallet->balance, 0, ',', '.').
                ', SPP: Rp '.number_format($amount, 0, ',', '.')
            );
        }

        $invoiceNumber = 'INV-'.$santri->id.'-'.now()->format('Ym').'-'.strtoupper(Str::random(4));

        try {
            DB::transaction(function () use ($santri, $wallet, $amount, $nextPeriod, $invoiceNumber, $branchId, &$invoice) {
                $locked = Wallet::lockForUpdate()->find($wallet->id);

                if ($locked->balance < $amount) {
                    throw new \RuntimeException('Saldo tidak cukup.');
                }

                $locked->decrement('balance', $amount);

                $invoice = Invoice::create([
                    'santri_id' => $santri->id,
                    'invoice_number' => $invoiceNumber,
                    'total_amount' => $amount,
                    'status' => Invoice::STATUS_PAID,
                    'due_date' => now()->addDays(30)->toDateString(),
                    'period_type' => $nextPeriod['type'],
                    'period_label' => $nextPeriod['label'],
                    'period_number' => $nextPeriod['number'],
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'SPP - '.$nextPeriod['label'],
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'total_amount' => $amount,
                ]);

                WalletTransaction::create([
                    'wallet_id' => $locked->id,
                    'amount' => $amount,
                    'type' => 'debit',
                    'description' => "Pembayaran SPP {$nextPeriod['label']}",
                    'reference_id' => $invoice->id,
                ]);

                app(JournalService::class)->createJournalEntry(
                    "Pembayaran SPP {$nextPeriod['label']} - {$santri->nama}",
                    "INV-{$invoice->id}",
                    [
                        ['account_code' => AccountCodes::WALLET_LIABILITY, 'debit' => $amount, 'credit' => 0],
                        ['account_code' => AccountCodes::SPP_REVENUE, 'debit' => 0, 'credit' => $amount],
                    ],
                    now()->toDateString(),
                    $branchId
                );
            });
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.spp-payment',
            $user->isSuperadmin() ? ['school_id' => $branchId, 'kelas_id' => $santri->kelas_id] : ['kelas_id' => $santri->kelas_id]
        )->with('success', "Pembayaran SPP {$nextPeriod['label']} untuk {$santri->nama} berhasil.");
    }

    protected function resolveTariffForSantri(Santri $santri): ?SppTariff
    {
        return SppTariff::where('branch_id', $santri->branch_id)
            ->where(function ($q) use ($santri) {
                $q->where('type', 'semua')
                    ->orWhere(fn ($sq) => $sq->where('type', 'kelas')->where('kelas_id', $santri->kelas_id))
                    ->orWhere(fn ($sq) => $sq->where('type', 'angkatan')->where('angkatan', $santri->angkatan));
            })
            ->orderByRaw("CASE type WHEN 'kelas' THEN 1 WHEN 'angkatan' THEN 2 ELSE 3 END")
            ->orderByDesc('academic_year')
            ->first();
    }

    /**
     * Bayar semua santri — per kelas (kelas_id) atau seluruh sekolah (school_id).
     */
    public function sppPayBatch(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'school_id' => ['nullable', 'integer', 'exists:branches,id'],
            'period_number' => ['nullable', 'integer', 'min:1', 'max:12'],
            'periode_type' => ['nullable', 'in:bulanan,semester'],
        ]);

        if (! ($validated['kelas_id'] ?? null) && ! ($validated['school_id'] ?? null)) {
            abort(400);
        }

        $user = auth()->user();

        if ($validated['kelas_id'] ?? null) {
            $kelas = Kelas::findOrFail($validated['kelas_id']);
            $branchId = $kelas->branch_id;
            if (! $user->isSuperadmin() && $user->branch_id !== $branchId) {
                abort(403);
            }
            $santris = Santri::with('wallet')->where('kelas_id', $kelas->id)->orderBy('nama')->get();
        } else {
            $branchId = (int) ($validated['school_id'] ?? 0);
            if (! $user->isSuperadmin() && $user->branch_id !== $branchId) {
                abort(403);
            }
            $santris = Santri::with('wallet')->where('branch_id', $branchId)->orderBy('nama')->get();
        }

        // Jika period_number ditentukan, generate label tetap
        $fixedPeriod = null;
        if (($validated['period_number'] ?? null)) {
            $num = (int) $validated['period_number'];
            $ptype = $validated['periode_type'] ?? 'bulanan';
            $academicYear = null;

            // Ambil academic_year dari tarif pertama sekolah
            $firstTariff = SppTariff::where('branch_id', $branchId)->orderByDesc('academic_year')->first();
            if ($firstTariff) {
                $academicYear = $firstTariff->academic_year;
            }

            $tahun = now()->year;
            if ($academicYear && preg_match('/^(\d{4})\/\d{4}$/', $academicYear, $m)) {
                $tahun = (int) $m[1];
            }

            if ($ptype === 'semester') {
                $fixedPeriod = [
                    'number' => $num,
                    'label' => 'Semester '.$num.' '.$tahun.'/'.($tahun + 1),
                    'type' => 'semester',
                ];
            } else {
                $bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                $bulanTahun = $tahun;
                if ($num >= 1 && $num <= 6) {
                    $bulanTahun = $tahun + 1;
                }
                $fixedPeriod = [
                    'number' => $num,
                    'label' => $bulanIndo[$num].' '.$bulanTahun,
                    'type' => 'bulanan',
                ];
            }
        }

        $tariffs = SppTariff::where('branch_id', $branchId)->orderBy('academic_year')->get();
        $tariffByKelas = [];
        $tariffByAngkatan = [];
        $tariffSemua = 0;

        foreach ($tariffs as $t) {
            if ($t->type === 'semua') {
                $tariffSemua = (float) $t->amount;
            } elseif ($t->type === 'kelas') {
                $tariffByKelas[$t->kelas_id] = (float) $t->amount;
            } elseif ($t->type === 'angkatan') {
                $tariffByAngkatan[$t->angkatan] = (float) $t->amount;
            }
        }

        $successCount = 0;
        $failBalanceCount = 0;
        $failPeriodCount = 0;
        $skipPaidCount = 0;

        foreach ($santris as $santri) {
            $tarif = $tariffByKelas[$santri->kelas_id] ?? $tariffByAngkatan[$santri->angkatan] ?? $tariffSemua;
            if ($tarif <= 0) {
                continue;
            }

            // Tentukan periode yang akan dibayar
            if ($fixedPeriod) {
                $nextPeriod = $fixedPeriod;
                // Skip jika sudah bayar periode tetap ini
                $sudahBayar = Invoice::where('santri_id', $santri->id)
                    ->where('period_type', $fixedPeriod['type'])
                    ->where('period_number', $fixedPeriod['number'])
                    ->where('period_label', $fixedPeriod['label'])
                    ->where('status', Invoice::STATUS_PAID)
                    ->exists();
                if ($sudahBayar) {
                    $skipPaidCount++;

                    continue;
                }
            } else {
                $nextPeriod = $this->getNextPeriod($santri->id);
                if (! $nextPeriod) {
                    $failPeriodCount++;

                    continue;
                }
            }

            $wallet = $santri->wallet;
            if (! $wallet || $wallet->balance < $tarif) {
                $failBalanceCount++;

                continue;
            }

            $invoiceNumber = 'INV-'.$santri->id.'-'.now()->format('Ym').'-'.strtoupper(Str::random(4));

            try {
                DB::transaction(function () use ($santri, $wallet, $tarif, $nextPeriod, $invoiceNumber, $branchId) {
                    $locked = Wallet::lockForUpdate()->find($wallet->id);
                    if ($locked->balance < $tarif) {
                        throw new \RuntimeException('Saldo tidak cukup.');
                    }

                    $locked->decrement('balance', $tarif);

                    $invoice = Invoice::create([
                        'santri_id' => $santri->id,
                        'invoice_number' => $invoiceNumber,
                        'total_amount' => $tarif,
                        'status' => Invoice::STATUS_PAID,
                        'due_date' => now()->addDays(30)->toDateString(),
                        'period_type' => $nextPeriod['type'],
                        'period_label' => $nextPeriod['label'],
                        'period_number' => $nextPeriod['number'],
                    ]);

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => 'SPP - '.$nextPeriod['label'],
                        'quantity' => 1,
                        'unit_price' => $tarif,
                        'total_amount' => $tarif,
                    ]);

                    WalletTransaction::create([
                        'wallet_id' => $locked->id,
                        'amount' => $tarif,
                        'type' => 'debit',
                        'description' => "Pembayaran SPP {$nextPeriod['label']}",
                        'reference_id' => $invoice->id,
                    ]);

                    app(JournalService::class)->createJournalEntry(
                        "Pembayaran SPP {$nextPeriod['label']} - {$santri->nama}",
                        "INV-{$invoice->id}",
                        [
                            ['account_code' => AccountCodes::WALLET_LIABILITY, 'debit' => $tarif, 'credit' => 0],
                            ['account_code' => AccountCodes::SPP_REVENUE, 'debit' => 0, 'credit' => $tarif],
                        ],
                        now()->toDateString(),
                        $branchId
                    );
                });
                $successCount++;
            } catch (\RuntimeException $e) {
                $failBalanceCount++;
            }
        }

        $total = $successCount + $failBalanceCount + $failPeriodCount + $skipPaidCount;
        $message = "Pembayaran batch selesai. {$successCount} dari {$total} santri berhasil dibayar.";
        if ($failBalanceCount > 0) {
            $message .= " {$failBalanceCount} santri gagal (saldo tidak cukup).";
        }
        if ($failPeriodCount > 0) {
            $message .= " {$failPeriodCount} santri sudah lunas semua periode.";
        }
        if ($skipPaidCount > 0) {
            $message .= " {$skipPaidCount} santri sudah bayar periode ini.";
        }

        $redirectParams = $user->isSuperadmin()
            ? ['school_id' => $branchId]
            : [];

        if (($validated['kelas_id'] ?? null)) {
            $redirectParams['kelas_id'] = (int) $validated['kelas_id'];
        }

        return redirect()->route('invoices.spp-payment', $redirectParams)
            ->with('success', $message);
    }

    protected function getNextPeriod(int $santriId, string $periodeType = 'bulanan', ?string $academicYear = null): ?array
    {
        if (! in_array($periodeType, ['bulanan', 'semester'])) {
            $periodeType = 'bulanan';
        }

        $lastPaid = Invoice::where('santri_id', $santriId)
            ->where('status', Invoice::STATUS_PAID)
            ->where('period_type', $periodeType)
            ->orderByDesc('period_number')
            ->first();

        $nextNum = $lastPaid ? ((int) $lastPaid->period_number) + 1 : 1;

        $maxNum = $periodeType === 'semester' ? 2 : 12;
        if ($nextNum > $maxNum) {
            return null;
        }

        $tahun = now()->year;
        if ($academicYear && preg_match('/^(\d{4})\/\d{4}$/', $academicYear, $m)) {
            $tahun = (int) $m[1];
        }

        if ($periodeType === 'semester') {
            return [
                'number' => $nextNum,
                'label' => 'Semester '.$nextNum.' '.$tahun.'/'.($tahun + 1),
                'type' => 'semester',
            ];
        }

        $bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $bulanTahun = $tahun;
        if ($nextNum >= 1 && $nextNum <= 6) {
            $bulanTahun = $tahun + 1;
        }

        return [
            'number' => $nextNum,
            'label' => $bulanIndo[$nextNum].' '.$bulanTahun,
            'type' => 'bulanan',
        ];
    }
}
