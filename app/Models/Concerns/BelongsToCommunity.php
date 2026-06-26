<?php

namespace App\Models\Concerns;

use App\Services\Community\CommunityContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait BelongsToCommunity
{
    protected static array $communityColumnCache = [];

    protected static function bootBelongsToCommunity(): void
    {
        static::addGlobalScope('active_community', function (Builder $builder) {
            $model = $builder->getModel();

            if (!$model->hasCommunityColumn()) {
                return;
            }

            $communityId = app()->bound(CommunityContext::class)
                ? app(CommunityContext::class)->communityId()
                : null;

            if ($communityId) {
                $builder->where($model->getTable().'.community_id', $communityId);
            }
        });

        static::creating(function ($model) {
            if (!$model->hasCommunityColumn() || $model->community_id) {
                return;
            }

            $communityId = app()->bound(CommunityContext::class)
                ? app(CommunityContext::class)->communityId()
                : null;

            if ($communityId) {
                $model->community_id = $communityId;
            }
        });
    }

    public function scopeWithoutCommunity(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active_community');
    }

    protected function hasCommunityColumn(): bool
    {
        $table = $this->getTable();

        if (!array_key_exists($table, self::$communityColumnCache)) {
            self::$communityColumnCache[$table] = Schema::hasColumn($table, 'community_id');
        }

        return self::$communityColumnCache[$table];
    }
}
