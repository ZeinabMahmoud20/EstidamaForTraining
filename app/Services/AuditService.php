<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes administration changes to the audit trail.
 */
class AuditService
{
    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function record(string $action, Model $entity, ?array $old = null, ?array $new = null, ?User $actor = null): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'old_value' => $old,
            'new_value' => $new,
        ]);
    }
}
