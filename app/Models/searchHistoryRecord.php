<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommunity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchHistoryRecord extends Model
{
    use HasFactory;
    use BelongsToCommunity;
}
