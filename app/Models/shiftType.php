<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class shiftType extends Model
{
    use SoftDeletes;

    use HasFactory;

    public const LEGAL_HOLIDAY_ID = 18;
    public const UNUSED_IDS = [17];
}
