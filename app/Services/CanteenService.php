<?php

namespace App\Services;

use App\Models\CanteenItem;
use App\Models\CanteenTransaction;
use App\Models\CanteenTransactionItem;
use App\Models\Santri;
use App\Models\WalletTransaction;
use App\Support\AccountCodes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CanteenService
{
    public const METHOD_WALLET = 'wallet';

    public const METHOD_CASH = 'cash';

    /**
     * Mencatat pembelian kantin.
     *
     * Metode "wallet" memotong saldo santri + jurnal Dr 2010 / Cr 4010.
     * Metode "cash"  tidak memotong saldo (di luar saldo) + jurnal Dr 1010 / Cr 4010.
     *
     * @param  array  $items  Baris detail: ['name', 'unit_price', 'quantity', 'unit'?, 'canteen_item_id'?]
     * @return array{status: string, balance?: ?float, remaining_limit?: float, item?: string}
     */
    public function purchase(Santri $santri, float $amount, string $canteenName, int $branchId, array $items = [], ?int $canteenId = null, string $paymentMethod = self::METHOD_WALLET): array
    {
        return DB::transaction(function () use ($santri, $amount, $canteenName, $branchId, $items, $canteenId, $paymentMethod) {
            $lockedItems = $this->lockStockItems($items);

            $stockError = $this->checkStock($items, $lockedItems);

            if ($stockError) {
                return ['status' => 'insufficient_stock', 'item' => $stockError];
            }

            $wallet = null;

            if ($paymentMethod === self::METHOD_WALLET) {
                $wallet = $santri->wallet()->lockForUpdate()->first();

                if (! $wallet) {
                    return ['status' => 'wallet_missing'];
                }

                if ($wallet->balance < $amount) {
                    return ['status' => 'insufficient_balance', 'balance' => (float) $wallet->balance, 'amount' => $amount];
                }

                $todaySpent = WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('type', 'debit')
                    ->whereBetween('created_at', [now()->startOfDay(), now()->addDay()->startOfDay()])
                    ->sum('amount');

                if ($wallet->daily_spending_limit > 0 && ($todaySpent + $amount) > $wallet->daily_spending_limit) {
                    return [
                        'status' => 'daily_limit_exceeded',
                        'balance' => (float) $wallet->balance,
                        'remaining_limit' => max(0, (float) $wallet->daily_spending_limit - $todaySpent),
                    ];
                }

                $wallet->decrement('balance', $amount);
            }

            $walletTransactionId = null;

            if ($wallet) {
                $description = count($items) > 0
                    ? sprintf('Pembelian di %s (%d item)', $canteenName, count($items))
                    : sprintf('Canteen purchase at %s', $canteenName);

                $walletTransaction = WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'type' => 'debit',
                    'description' => $description,
                    'reference_id' => null,
                ]);

                $walletTransactionId = $walletTransaction->id;
            }

            if (count($items) > 0) {
                $canteenTransaction = CanteenTransaction::create([
                    'wallet_transaction_id' => $walletTransactionId,
                    'canteen_id' => $canteenId,
                    'payment_method' => $paymentMethod,
                    'branch_id' => $branchId,
                    'total' => $amount,
                    'transaction_date' => now()->toDateString(),
                ]);

                foreach ($items as $item) {
                    $unitPrice = (float) ($item['unit_price'] ?? 0);
                    $quantity = (float) ($item['quantity'] ?? 1);
                    $itemId = $item['canteen_item_id'] ?? null;
                    $lockedItem = $itemId ? $lockedItems->get($itemId) : null;

                    CanteenTransactionItem::create([
                        'canteen_transaction_id' => $canteenTransaction->id,
                        'canteen_item_id' => $itemId,
                        'item_name' => $item['name'],
                        'unit' => $lockedItem?->unit ?: ($item['unit'] ?? 'pcs'),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total' => $quantity * $unitPrice,
                    ]);
                }
            }

            $details = $paymentMethod === self::METHOD_CASH
                ? [
                    ['account_code' => AccountCodes::CASH, 'debit' => $amount, 'credit' => 0],
                    ['account_code' => AccountCodes::CANTEEN_REVENUE, 'debit' => 0, 'credit' => $amount],
                ]
                : [
                    ['account_code' => AccountCodes::WALLET_LIABILITY, 'debit' => $amount, 'credit' => 0],
                    ['account_code' => AccountCodes::CANTEEN_REVENUE, 'debit' => 0, 'credit' => $amount],
                ];

            app(JournalService::class)->createJournalEntry(
                sprintf('Pembelian kantin oleh santri %s', $santri->nama),
                sprintf('CANTEEN-%s-%s', $santri->id, now()->format('YmdHis')),
                $details,
                now()->toDateString(),
                $branchId
            );

            $stockError = $this->decrementStock($items, $lockedItems);

            if ($stockError) {
                return ['status' => 'insufficient_stock', 'item' => $stockError];
            }

            return [
                'status' => 'success',
                'balance' => $wallet ? $wallet->fresh()->balance : null,
            ];
        });
    }

    protected function lockStockItems(array $items): Collection
    {
        $ids = collect($items)
            ->pluck('canteen_item_id')
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return CanteenItem::whereIn('id', $ids->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    protected function checkStock(array $items, Collection $lockedItems): ?string
    {
        foreach ($items as $item) {
            $itemId = $item['canteen_item_id'] ?? null;

            if (empty($itemId)) {
                continue;
            }

            $canteenItem = $lockedItems->get($itemId);

            if (! $canteenItem) {
                continue;
            }

            $stock = $canteenItem->stock;
            $quantity = (int) ($item['quantity'] ?? 1);

            if ($stock === null || $stock < $quantity) {
                return $canteenItem->name;
            }
        }

        return null;
    }

    protected function decrementStock(array $items, Collection $lockedItems): ?string
    {
        foreach ($items as $item) {
            $itemId = $item['canteen_item_id'] ?? null;

            if (empty($itemId)) {
                continue;
            }

            $canteenItem = $lockedItems->get($itemId);

            if (! $canteenItem || $canteenItem->stock === null) {
                continue;
            }

            $quantity = (int) ($item['quantity'] ?? 1);

            $affected = CanteenItem::where('id', $canteenItem->id)
                ->where('stock', '>=', $quantity)
                ->decrement('stock', $quantity);

            if (! $affected) {
                return $canteenItem->name;
            }
        }

        return null;
    }
}
