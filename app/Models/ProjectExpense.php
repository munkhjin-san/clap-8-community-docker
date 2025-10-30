<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectExpense extends Model
{
    protected $guarded = [];

    // in ProjectExpense / ProjectSale
    protected $casts = [
        'period' => 'date',
        'bonus' => 'float', 'indirect' => 'float', 'internal_orders' => 'float',
        'outsourcing' => 'float', 'salaries' => 'float', 'sga_other' => 'float',
    ];

}
