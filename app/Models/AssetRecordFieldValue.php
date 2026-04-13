<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRecordFieldValue extends Model
{
    protected $fillable = [
        'asset_record_id',
        'asset_category_item_field_id',
        'value',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(AssetRecord::class, 'asset_record_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(AssetCategoryItemField::class, 'asset_category_item_field_id');
    }
}
