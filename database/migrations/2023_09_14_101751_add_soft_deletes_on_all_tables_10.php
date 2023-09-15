<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $tablesToMigrate = [
            'comment_records',
            'message_records',
            'app_block_records',		
            'app_file_records', 
            'app_folder_records',		
            'app_remember_records',		
            'attendance_records',		
            'board_announcements',		
            'board_awards',		
            'board_groups',		
            'board_records',		
            'board_to_users',		
            'board_use_files',		
            'board_use_tags',		
            'calendar_groups',		
            'calendar_records',				
            'calendar_users',		
            'calendar_use_files',		
            'calendar_view_users',		
            'challenge_awards',		
            'challenge_records',		
            'challenge_to_users',		
            'challenge_use_files',		
            'challenge_use_tags',		
            'clap_records',		
            'comment_records',			
            'custom_field_data_records',		
            'custom_field_parts_records',		
            'custom_field_records',		
            'custom_field_type_records',		
            'file_records',		
            'groups',		
            'group_users',		
            'icons',		
            'info_records',		
            'Knowledges',		
            'knowledge_records',		
            'knowledge_to_users',		
            'knowledge_use_files',		
            'knowledge_use_tags',		
            'memo_records',		
            'message_attachments',		
            'message_files',				
            'message_records',				
            'my_groups',		
            'my_group_users',		
            'news_records',		
            'nice_records',		
            'nice_to_users',		
            'nice_use_files',		
            'nice_use_tags',		
            'notice_edit_histories',		
            'notice_files',		
            'notice_records',		
            'notice_tag_records',		
            'offices',		
            'office_records',			
            'petition_records',		
            'petition_types',		
            'positions',		
            'position_records',		
            'qanda_history_records',		
            'qanda_key_word_records',		
            'qanda_tag_records',		
            'qanda_use_key_words',		
            'qanda_use_tags',		
            'question_and_answer_records',		
            'schedule_records',		
            'schedule_users',			
            'search_history_records',		
            'shift_records',		
            'shift_types',		
            'support_mail_form_records',		
            'support_mail_responding_logs',		
            'tag_records',		
            'timecard_break_records',		
            'timecard_records',	
            'users',		
            'user_albums',		
            'user_details',		
            'user_last_records',		
            'work_groups',		
            'work_group_users',		
            'work_month_holidays',	
        ];

        foreach ($tablesToMigrate as $tableName) {
            // Update the deleted_at column for records with deleted_flag = 1

            if (!Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function ($table) {
                    $table->softDeletes();
                });
            }
            DB::table($tableName)
                ->where('deleted_flag', 1)
                ->update(['deleted_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
