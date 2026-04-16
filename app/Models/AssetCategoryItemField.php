<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetCategoryItemField extends Model
{
    protected $fillable = [
        'asset_category_item_id',
        'key',
        'label',
        'input_type',
        'placeholder',
        'rules',
        'visible',
        'editable',
        'sort_order',
    ];

    protected $casts = [
        'editable' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(AssetCategoryItem::class, 'asset_category_item_id');
    }
}
