<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractReviewJob extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'status',
        'review_type',
        'role',
        'contract_type',
        'original_filename',
        'mime',
        'stored_path',
        'rendered_page_paths',
        'use_extracted_text',
        'project_contract_id',
        'result_json',
        'raw_text',
        'document_input',
        'file_path',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'rendered_page_paths' => 'array',
        'use_extracted_text' => 'boolean',
        'result_json' => 'array',
        'document_input' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
