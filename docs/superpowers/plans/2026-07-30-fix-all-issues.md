# Fix All Issues + Frontend Refresh Implementation Plan

> **For agentic workers:** Execute tasks sequentially. Each task ends with a testable deliverable.

**Goal:** Fix 10 reported issues (kelas edit/delete, santri create/edit, wallet transaction, financial reports) and refresh the frontend UI to be cleaner, more dynamic, and modern.

**Architecture:** Monolithic Laravel 12 app using Tailwind CSS v4, Vite, and Blade templates. All fixes are in controller logic and Blade views.

**Tech Stack:** Laravel 12, Tailwind CSS v4, Vite, Blade, MySQL

## Global Constraints

- Keep existing Tailwind CSS v4 `@theme` config; no new CSS framework
- All views must extend `layouts.app` for consistent styling
- Route parameter names must match what `Route::resource()` generates (e.g. `{kela}` not `{kelas}`)
- Error feedback must use `->withErrors()` / `->with('error', ...)` — no `abort()`
- All monetary values displayed in Indonesian format (Rp, thousand separators dot, decimal comma)

---

### Task 1: Fix Kelas Edit Route Parameter

**Files:**
- Modify: `resources/views/kelas/edit.blade.php` (full replace)

**Details:** The `Route::resource('kelas')` creates route parameter `{kela}` (singularization of "kelas"). The edit view uses `['kelas' => $kelas->id]` which throws `UrlGenerationException`. Fix the parameter name AND extend the app layout for consistent styling.

- [ ] **Step 1: Rewrite `kelas/edit.blade.php`**

Replace the standalone HTML with an extended layout, fix route parameter to `'kela'`.

- [ ] **Step 2: Verify**

Open `http://127.0.0.1:8000/kelas/1/edit` — should show styled form with correct data. Submit should update (no error).

---

### Task 2: Fix Kelas Create View

**Files:**
- Modify: `resources/views/kelas/create.blade.php` (full replace)

**Details:** Currently standalone HTML without layout. Extend `layouts.app` for consistency.

- [ ] **Step 1: Rewrite `kelas/create.blade.php`**

Wrap in layout, use consistent form styling matching other create pages.

- [ ] **Step 2: Verify**

Open `http://127.0.0.1:8000/kelas/create` — styled form with layout.

---

### Task 3: Fix Kelas Delete (Index Page)

**Files:**
- Modify: `resources/views/kelas/index.blade.php`

**Details:** Replace `onclick="return confirm(...)"` with a proper form+button approach that submits correctly. The route parameter `{kela}` must match.

- [ ] **Step 1: Fix delete button and add error/success alerts**

Change delete form to use confirm dialog properly, add success/error flash message display.

- [ ] **Step 2: Verify**

Click "Hapus" → confirm dialog → OK → record deleted, page refreshes with success message.

---

### Task 4: Fix Wallet Transaction Error Handling

**Files:**
- Modify: `app/Http/Controllers/WalletController.php`
- Modify: `resources/views/wallet/edit-transaction.blade.php`

**Details:** Replace `abort(400, '...')` with `redirect()->back()->with('error', '...')`. Add client-side validation to show limit info before submit. Show flash error in view.

- [ ] **Step 1: Update `WalletController.php`**

Replace both `abort(400, ...)` calls with:
```php
return redirect()->back()
    ->withInput()
    ->withErrors(['limit' => 'Saldo tidak cukup untuk penarikan.']);
```
and
```php
return redirect()->back()
    ->withInput()
    ->withErrors(['limit' => 'Transaksi melebihi batas pengeluaran harian.']);
```

- [ ] **Step 2: Update `edit-transaction.blade.php`**

Add display for `$errors` and `session('error')`. Show daily spending limit info. Add JS to disable submit button if amount > limit.

- [ ] **Step 3: Verify**

Submit debit with amount > balance OR > limit → redirect back with error message, no JSON error page.

---

### Task 5: Refresh Layout (Sidebar + Header)

**Files:**
- Modify: `resources/views/layouts/app.blade.php`

**Details:** Add active menu highlighting, responsive sidebar toggle (hamburger), subtle hover effects, gradient header.

- [ ] **Step 1: Update `app.blade.php`**

Add active route detection with `request()->routeIs(...)`. Add sidebar collapse for mobile using `@vite` JS. Add green gradient branding.

- [ ] **Step 2: Verify**

All pages show consistent layout. Active menu highlighted. Mobile-responsive.

---

### Task 6: Refresh Dashboard

**Files:**
- Modify: `resources/views/dashboard/index.blade.php`

**Details:** Add hover card animations, gradient accent colors per card, responsive grid.

- [ ] **Step 1: Update `dashboard/index.blade.php`**

Add card hover effects (`transition-transform hover:-translate-y-1`), unique accent border-top per card, responsive grid using Tailwind.

---

### Task 7: Refresh Santri Index & Edit

**Files:**
- Modify: `resources/views/santri/index.blade.php`
- Modify: `resources/views/santri/edit.blade.php`
- Modify: `resources/views/santri/create.blade.php`

**Details:** Consistent button styles, gender badges, responsive table, hover row effects.

- [ ] **Step 1: Update `santri/index.blade.php`**

Add row hover, gender badges (`L` → blue, `P` → pink), polished action buttons.

- [ ] **Step 2: Update `santri/edit.blade.php`**

Polished form styling, consistent button styles.

- [ ] **Step 3: Update `santri/create.blade.php`**

Polished form styling, consistent button styles.

---

### Task 8: Refresh Wallet Pages

**Files:**
- Modify: `resources/views/wallet/index.blade.php`
- Modify: `resources/views/wallet/edit-limit.blade.php`

**Details:** Consistent table styling, saldo badge colors, improved card layout.

- [ ] **Step 1: Update wallet views**

Add saldo colors (green for positive, red for negative), hover effects, consistent action buttons.

---

### Task 9: Refresh Financial Report Views

**Files:**
- Modify: `resources/views/financial/general_journal.blade.php`
- Modify: `resources/views/financial/ledger.blade.php`
- Modify: `resources/views/financial/trial_balance.blade.php`
- Modify: `resources/views/financial/balance_sheet.blade.php`
- Modify: `resources/views/financial/income_statement.blade.php`

**Details:** Consistent table styling with striped rows, sticky header, better typography.

- [ ] **Step 1: Update all financial views**

Add `even:bg-gray-50` row striping, sticky table headers, card shadows, consistent `page-title`.

- [ ] **Step 2: Verify**

All financial report pages load with consistent polished styling.

---

### Task 10: Add Custom CSS Animations

**Files:**
- Modify: `resources/css/app.css`

**Details:** Add smooth transitions, page fade-in, sidebar link animations.

- [ ] **Step 1: Update `app.css`**

```css
@custom-variant even (&:nth-child(even));
```

Add keyframe for `fadeIn` page transition.

```css
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

main section {
    animation: fadeIn 0.3s ease-out;
}
```
