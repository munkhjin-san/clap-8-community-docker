<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategoryItem extends Model
{
    protected $fillable = [
        'type',
        'title',
        'required_data',
        'sort_order',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(AssetCategoryItemField::class, 'asset_category_item_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
