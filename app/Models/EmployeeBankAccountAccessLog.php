<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 口座情報への操作記録。追記のみ（更新しない）ので updated_at を持たない。
 */
class EmployeeBankAccountAccessLog extends Model
{
    const UPDATED_AT = null;

    protected $guarded = [];

    /** 記録に失敗しても業務は止めない。ただし黙って消さずログに残す。 */
    public static function record(?int $actorId, ?int $targetId, string $action): void
    {
        try {
            static::create([
                'actor_user_id' => $actorId,
                'target_user_id' => $targetId,
                'action' => $action,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('bank account access log failed', [
                'action' => $action, 'target' => $targetId, 'error' => $e->getMessage(),
            ]);
        }
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id')->select('id', 'name');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_user_id')->select('id', 'name');
    }
}
