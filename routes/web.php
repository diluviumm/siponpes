<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CanteenController;
use App\Http\Controllers\CanteenManageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SantriController;
use App\Http\Controllers\SantriImportController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SppTariffController;
use App\Http\Controllers\StaffAccountController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::get('kebijakan-privasi', [StaticPageController::class, 'privacy'])->name('privacy');
Route::get('syarat-layanan', [StaticPageController::class, 'terms'])->name('terms');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.attempt');
    Route::get('register', [RegisterController::class, 'showRegister'])->name('register');
    Route::post('register', [RegisterController::class, 'register'])->middleware('throttle:5,10')->name('register.store');

    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->middleware('throttle:5,10')->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->middleware('throttle:5,10')->name('password.store');
});

Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');

Route::middleware('auth')->group(function () {
    Route::get('email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('email/verify/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:5,10')->name('verification.send');
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'verified', 'active-school'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('password/ubah', [ProfileController::class, 'editPassword'])->name('password.change');
    Route::put('password/ubah', [ProfileController::class, 'updatePassword'])->name('password.update');

    Route::middleware('role:superadmin')->group(function () {
        Route::resource('schools', SchoolController::class)->except(['show']);
        Route::post('schools/{school}/approve', [SchoolController::class, 'approve'])->name('schools.approve');
        Route::post('schools/{school}/reject', [SchoolController::class, 'reject'])->name('schools.reject');
        Route::post('schools/{school}/reset-password', [SchoolController::class, 'resetPassword'])->name('schools.reset-password');
        Route::post('schools/{school}/admin', [SchoolController::class, 'createAdmin'])->name('schools.create-admin');

        Route::resource('accounts', AccountController::class)->except(['show']);
    });

    Route::middleware('role:superadmin,admin_sekolah')->group(function () {
        Route::delete('kelas/hapus-semua', [KelasController::class, 'destroyAll'])->name('kelas.destroy-all');
        Route::resource('kelas', KelasController::class);

        Route::get('audit', [AuditController::class, 'index'])->name('audit.index');

        Route::get('santri/import', [SantriImportController::class, 'create'])->name('santri.import');
        Route::get('santri/import/template', [SantriImportController::class, 'template'])->name('santri.import.template');
        Route::post('santri/import', [SantriImportController::class, 'store'])->name('santri.import.process');
        Route::get('santri/search', [SantriController::class, 'search'])->name('santri.search');
        Route::get('santri/trash', [SantriController::class, 'trash'])->name('santri.trash');
        Route::post('santri/{santri}/restore', [SantriController::class, 'restore'])->name('santri.restore');
        Route::delete('santri/hapus-semua', [SantriController::class, 'destroyAll'])->name('santri.destroy-all');
        Route::delete('santri/hapus-per-kelas/{kelas}', [SantriController::class, 'destroyByKelas'])->name('santri.destroy-by-kelas');
        Route::resource('santri', SantriController::class)->except(['show']);

        Route::resource('spp_tariffs', SppTariffController::class)->except(['show']);

        Route::get('invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
        Route::post('invoices/remind-overdue', [InvoiceController::class, 'remindOverdue'])->name('invoices.remind-overdue');
        Route::post('invoices/remind-all-unpaid', [InvoiceController::class, 'remindAllUnpaid'])->name('invoices.remind-all-unpaid');
        Route::get('invoices/{invoice}/reminders', [InvoiceController::class, 'reminders'])->name('invoices.reminders');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::resource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::post('invoices/{invoice}/remind', [InvoiceController::class, 'remind'])->name('invoices.remind');

        Route::get('spp/pembayaran', [InvoiceController::class, 'sppPayment'])->name('invoices.spp-payment');
        Route::post('spp/pembayaran', [InvoiceController::class, 'sppPay'])->name('invoices.spp-pay');
        Route::post('spp/pembayaran/batch', [InvoiceController::class, 'sppPayBatch'])->name('invoices.spp-pay-batch');

        Route::get('kas', [KasController::class, 'index'])->name('kas.index');
        Route::get('kas/riwayat', [KasController::class, 'riwayat'])->name('kas.riwayat');
        Route::get('kas/create', [KasController::class, 'create'])->name('kas.create');
        Route::post('kas', [KasController::class, 'store'])->name('kas.store');
        Route::get('kas/{kas_transaction}/edit', [KasController::class, 'edit'])->name('kas.edit');
        Route::put('kas/{kas_transaction}', [KasController::class, 'update'])->name('kas.update');
        Route::delete('kas/{kas_transaction}', [KasController::class, 'destroy'])->name('kas.destroy');

        Route::get('kantin/kelola', [CanteenManageController::class, 'index'])->name('kantin.manage');
        Route::get('kantin/kelola/{canteen}', [CanteenManageController::class, 'show'])->name('kantin.manage.show');
        Route::post('kantin/canteen', [CanteenManageController::class, 'storeCanteen'])->name('kantin.canteen.store');
        Route::delete('kantin/canteen/{canteen}', [CanteenManageController::class, 'destroyCanteen'])->name('kantin.canteen.destroy');
        Route::post('kantin/canteen/{canteen}/toggle', [CanteenManageController::class, 'toggleCanteen'])->name('kantin.canteen.toggle');
        Route::get('kantin/canteen/{canteen}/riwayat', [CanteenManageController::class, 'canteenRiwayat'])->name('kantin.canteen.riwayat');
        Route::post('kantin/canteen/{canteen}/restore', [CanteenManageController::class, 'restoreCanteen'])->name('kantin.canteen.restore');
        Route::post('kantin/item', [CanteenManageController::class, 'storeItem'])->name('kantin.item.store');
        Route::put('kantin/item/{item}', [CanteenManageController::class, 'updateItem'])->name('kantin.item.update');
        Route::delete('kantin/item/{item}', [CanteenManageController::class, 'destroyItem'])->name('kantin.item.destroy');
        Route::post('kantin/item/{item}/restore', [CanteenManageController::class, 'restoreItem'])->name('kantin.item.restore');

        Route::get('wallet', [WalletController::class, 'index'])->name('wallet.index');
        Route::get('wallet/{santri}/limit', [WalletController::class, 'editLimit'])->name('wallet.editLimit');
        Route::put('wallet/{santri}/limit', [WalletController::class, 'updateLimit'])->name('wallet.updateLimit');
        Route::get('wallet/{santri}/transaction', [WalletController::class, 'editTransaction'])->name('wallet.editTransaction');
        Route::get('wallet/{santri}/transactions', [WalletController::class, 'history'])->name('wallet.history');
        Route::post('wallet/{santri}/transaction', [WalletController::class, 'processTransaction'])->name('wallet.processTransaction');

        Route::get('staff', [StaffAccountController::class, 'index'])->name('staff.index');
        Route::post('staff', [StaffAccountController::class, 'store'])->name('staff.store');
        Route::post('staff/{user}/reset-password', [StaffAccountController::class, 'resetPassword'])->name('staff.reset-password');
        Route::delete('staff/{user}', [StaffAccountController::class, 'destroy'])->name('staff.destroy');

        Route::get('financial/journal', [FinancialReportController::class, 'generalJournal'])->name('financial.journal');
        Route::get('financial/ledger', [FinancialReportController::class, 'ledger'])->name('financial.ledger');
        Route::get('financial/trial-balance', [FinancialReportController::class, 'trialBalance'])->name('financial.trial_balance');
        Route::get('financial/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('financial.balance_sheet');
        Route::get('financial/income-statement', [FinancialReportController::class, 'incomeStatement'])->name('financial.income_statement');
        Route::get('financial/cash-flow', [FinancialReportController::class, 'cashFlow'])->name('financial.cash_flow');
        Route::get('financial/adjusting', [JournalController::class, 'index'])->name('financial.adjusting');
        Route::post('financial/adjusting', [JournalController::class, 'store'])->name('financial.adjusting.store');
        Route::post('financial/close', [JournalController::class, 'closeBook'])->name('financial.close');
    });

    Route::middleware('role:admin_sekolah')->group(function () {
        Route::get('profil-sekolah', [SchoolController::class, 'profileEdit'])->name('school.profile');
        Route::put('profil-sekolah', [SchoolController::class, 'profileUpdate'])->name('school.profile.update');
    });

    Route::get('kantin', [CanteenController::class, 'index'])->name('kantin.index');
    Route::post('kantin', [CanteenController::class, 'store'])->name('kantin.store');
    Route::get('kantin/rekap', [CanteenController::class, 'rekap'])->name('kantin.rekap');
});
