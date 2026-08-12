<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * freeeへ登録済みのGLOWD生成仕訳。(target_month, bucket) で一意。
 */
class FreeeJournalPost extends Model
{
    protected $table = 'freee_journal_posts';

    protected $guarded = [];

    protected $casts = [
        'target_month' => 'date',
        'details' => 'array',
        'posted_at' => 'datetime',
        'amount' => 'integer',
        'freee_journal_id' => 'integer',
        'freee_company_id' => 'integer',
    ];
}
