<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'sort_order',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(AssetCategoryItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
