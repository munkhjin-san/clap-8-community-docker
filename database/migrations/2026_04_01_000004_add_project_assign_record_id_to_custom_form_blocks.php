<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('custom_form_blocks', function (Blueprint $table) {
            if (! Schema::hasColumn('custom_form_blocks', 'project_assign_record_id')) {
                $table->unsignedBigInteger('project_assign_record_id')->nullable()->after('custom_form_id');
                $table->index('project_assign_record_id', 'cf_blocks_par_record_id_idx');
                $table->foreign('project_assign_record_id', 'cf_blocks_par_record_id_fk')
                    ->references('id')
                    ->on('project_assign_records')
                    ->onDelete('cascade');
            }
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_form_blocks', function (Blueprint $table) {
            if (Schema::hasColumn('custom_form_blocks', 'project_assign_record_id')) {
                $table->dropForeign('cf_blocks_par_record_id_fk');
                $table->dropIndex('cf_blocks_par_record_id_idx');
                $table->dropColumn('project_assign_record_id');
            }
        });
    }
};
