<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\SoftDeletes;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(fn ($model) => static::recordAudit('created', $model));
        static::updated(fn ($model) => static::recordAudit('updated', $model));
        static::deleted(fn ($model) => static::recordAudit('deleted', $model));

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn ($model) => static::recordAudit('restored', $model));
        }
    }

    protected static function recordAudit(string $action, $model): void
    {
        try {
            if (! auth()->check()) {
                return;
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'branch_id' => static::resolveBranchIdForAudit($model),
                'action' => $action,
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'summary' => static::auditSummary($action, $model),
                'changes' => static::auditChanges($action, $model),
            ]);
        } catch (\Throwable $e) {
            // Jangan sampai log audit menggagalkan operasi utama.
        }
    }

    /**
     * Cari sekolah/branch milik model untuk keperluan pemfilteran riwayat audit.
     */
    public static function resolveBranchIdForAudit($model): ?int
    {
        if (isset($model->branch_id) && $model->branch_id !== null) {
            return (int) $model->branch_id;
        }

        foreach (['school', 'santri', 'canteen', 'wallet'] as $relation) {
            if (method_exists($model, $relation) && $model->{$relation} !== null) {
                $related = $model->{$relation};

                if (isset($related->branch_id) && $related->branch_id !== null) {
                    return (int) $related->branch_id;
                }
            }
        }

        return null;
    }

    protected static function auditChanges(string $action, $model): ?array
    {
        if ($action !== 'updated') {
            return null;
        }

        $original = $model->getOriginal();
        $dirty = $model->getDirty();

        if (empty($dirty)) {
            return null;
        }

        $changes = [];

        foreach ($dirty as $key => $newValue) {
            if (! array_key_exists($key, $original)) {
                continue;
            }

            $oldValue = $original[$key];

            if ($oldValue == $newValue) {
                continue;
            }

            $changes[$key] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        return empty($changes) ? null : $changes;
    }

    protected static function auditSummary(string $action, $model): ?string
    {
        foreach (['nama', 'name', 'invoice_number', 'description', 'account_name', 'nama_kelas', 'santri_card_token', 'nis', 'email'] as $attribute) {
            if (isset($model->{$attribute}) && $model->{$attribute} !== null && $model->{$attribute} !== '') {
                return (string) $model->{$attribute};
            }
        }

        return null;
    }
}
