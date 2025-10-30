<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSale extends Model
{
    protected $guarded = [];

    // in ProjectExpense / ProjectSale
    protected $casts = [
        'period' => 'date',
        'sales' => 'float', 'internal_sales' => 'float',
    ];

}
