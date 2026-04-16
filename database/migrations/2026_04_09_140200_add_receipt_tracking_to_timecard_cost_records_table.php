<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timecard_cost_records', function (Blueprint $table) {
            $table->uuid('draft_uuid')->nullable()->after('id');
            $table->date('receipt_date')->nullable()->after('date_month');
            $table->string('merchant_name')->nullable()->after('department');
            $table->string('currency', 8)->default('JPY')->after('merchant_name');
            $table->string('receipt_source_type', 32)->default('paper_scan')->after('currency');
            $table->string('file_original_name')->nullable()->after('file_path');
            $table->string('file_mime_type')->nullable()->after('file_original_name');
            $table->unsignedBigInteger('file_size_bytes')->nullable()->after('file_mime_type');
            $table->string('file_sha256', 64)->nullable()->after('file_size_bytes');
            $table->timestamp('file_uploaded_at')->nullable()->after('file_sha256');

            $table->index('draft_uuid');
            $table->index(['merchant_name', 'receipt_date']);
        });
    }

    public function down(): void
    {
        Schema::table('timecard_cost_records', function (Blueprint $table) {
            $table->dropIndex(['merchant_name', 'receipt_date']);
            $table->dropIndex(['draft_uuid']);
            $table->dropColumn([
                'draft_uuid',
                'receipt_date',
                'merchant_name',
                'currency',
                'receipt_source_type',
                'file_original_name',
                'file_mime_type',
                'file_size_bytes',
                'file_sha256',
                'file_uploaded_at',
            ]);
        });
    }
};
