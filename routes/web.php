<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\CustomFormController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\BoardController;

use App\Http\Controllers\FileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\WorkController;
use App\Http\Controllers\CustomfieldController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\AutoJobController;
use App\Http\Controllers\AdminWorkController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonExamController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\RemindController;
use App\Http\Controllers\RefreshController;
use App\Http\Controllers\DriveController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\OpenAiController;
use App\Http\Controllers\ProjectPlanController;
use App\Http\Controllers\PublicSurveyController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\PublicHolidayController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppCommentController;
use App\Models\User;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/incident_fill', [AutoJobController::class, 'incident_fill']);
//for home page
Route::get('get_team_external', [ProjectController::class, 'get_team_external']);
Route::get('get_projects_external', [ProjectController::class, 'get_projects_external']);
Route::match(['get', 'post'],'/zoom3_event', [AutoJobController::class, 'zoom_event']);
Route::match(['get', 'post'],'/zoom2_event', [AutoJobController::class, 'zoom_event']);
Route::match(['get', 'post'],'/zoom1_event', [AutoJobController::class, 'zoom_event']);

Route::get("/departure_report", [AutoJobController::class, 'departure_report'])->name('departure_activate')->middleware('signed');;

Route::get('app/public/{app_name}', function ($app_name, Request $request) {    
    $query = $request->getQueryString(); 
    $url = "/{$app_name}";    
    if ($query) {
        $url .= '?' . $query;
    }    
    return redirect($url);
});

Route::get('/pdf-reader/{path}', [ContentController::class, 'pdf_reader'])
    ->where('path', '.*');
// temp_routes
// Route::get('/reacted_users_make', [AutoJobController::class, 'reactedUsersMake']);
// Route::get('/change_to_dummy', [AutoJobController::class, 'change_to_dummy']);
// Route::get('/create_notice_board', [AutoJobController::class, 'create_notice_board']);
// Route::get('/migrate_app_files_to_message_files', [AutoJobController::class, 'migrate_app_files_to_message_files']);
// Route::get('/remove_temp_files_cron', [AutoJobController::class, 'removeTemprorayFiles']);
// Route::get('/generate_readers', [NoticeController::class, 'generate_readers']);
// Route::get('/move_note_to_task', [AutoJobController::class, 'move_note_to_task']);
// Route::get('/genertate_my_groups', [CalendarController::class, 'genertate_my_groups']);
// Route::get('/update_last_act', [AutoJobController::class, 'update_last_act']);
// Route::get('/sync_first_month_calendar_shift', [AutoJobController::class, 'sync_first_month_calendar_shift']);
// Route::get('/process_csv', [AutoJobController::class, "process_csv"]);
// Route::get('/create_thumbnails', [AutoJobController::class, 'createThumbnails']);
// Route::get('/board_files_thumbnail', [AutoJobController::class, 'board_files_thumbnail']);
// Route::get('/change_shift_status', [AutoJobController::class, 'change_shift_status']);
// Route::get('/board_badge_update_auto', [AutoJobController::class, 'board_badge_update_auto']);
Route::get('/timecard_update', [AutoJobController::class, 'timecard_update']);
Route::get('/emote_rearrange', [AutoJobController::class, 'emote_rearrange']);
// temp_routes
// Route::get('/for_kintone', [ContentController::class, 'for_kintone']);
// Route::get('/for_kintone_pop', [ContentController::class, 'for_kintone_pop']);
// Route::get('/clap_process', [AutoJobController::class, 'clap_process']);

Route::get('/content_api/{which}/{path}', [ContentController::class, 'iconTransferApi']);   
Route::get('/export_ical', [CalendarController::class, 'export_ical']);
// Route::get('/help/{any?}', function () {
//     return view('help');
// })->where('any', '.*')->name('help');
Route::match(['get', 'post'], '/cron-trigger', [AutoJobController::class, 'cronTest']);

Auth::routes(['register' => false]);
Route::get('/user_icon_thumbnail/{path}/{size}/{color?}', [ContentController::class, 'user_icon_thumbnail']);
Route::get('/user_default_thumbnail/{char}/{size}/{color?}', [ContentController::class, 'user_default_thumbnail']);
Route::prefix('cdn_external')->group(function () {
    Route::get('{user_id}/{keyword}/{any?}', [ContentController::class, 'fileTransferAllExternal'])->where('any', '.*');
});
Route::get('/public-surveys/{token}', [PublicSurveyController::class, 'show']);
Route::get('/public-surveys/{token}/data', [PublicSurveyController::class, 'data']);
Route::post('/public-surveys/{token}/answers', [PublicSurveyController::class, 'submit'])->middleware('throttle:20,1');
Route::group(["middleware"=> ["auth", "session.expired"]],function(){
    Route::post('/push/subscribe', [PushController::class, 'subscribe']);
    Route::get('/push/test', [PushController::class, 'test']);

    Route::get('auth/google/auth', [GoogleController::class, 'redirect'])->name('google.auth');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
    // pusher authorize
    Route::post('/pusher_authorizition',  [BoardController::class, "pusher_auth"]);
    Route::post('/pusher_subscribe',  [BoardController::class, "pusher_subscribe"]);
    Route::get('/pusher/beams-auth', [BoardController::class, "pusher_beamToken"]);
    // pusher authorize


    Route::get('/user', function () {
        $id = Auth::id();
        return redirect("/user/{$id}");
    });

    Route::get('/start_private_board', [BoardController::class, 'start_private_board']);
    Route::get('/' ,function () {
        return redirect("/board");
    });

    Route::get('/board/{id}/{app_name}' ,function ($id) {
        return redirect("/board/{$id}");
    })->where('app_name', '(task|file)');

    Route::get('/home',function (Request $request) {
        $query = $request->id; 
        if($query){
            $url = "/notice/{$query}";    
            return redirect($url);  
        }else{
            return redirect("/board");
        }
        
        
    });
        
    Route::get('/employee', function () {return redirect("/members");});
    Route::get('/notice/{id}', function ($id) {return redirect("/dashboard/notice?notice_id={$id}");});

    Route::get('/{name}/{any?}',[BoardController::class, "index"])
    ->whereIn('name', [
        'community',
        'board', 
        'challenge', 
        'post', 
        'knowledge', 
        'nice', 
        'members', 
        'schedule', 
        'timesheet', 
        'admin_control', 
        'support', 
        'settings', 
        'user', 
        'learning', 
        'project', 
        'survey', 
        'remind',
        'contact',
        'asset-partner',
        'survey-answers',
        'file-preview',
        'help',
        'dashboard',
    ])->where('any', '.*')->name('board');

    Route::get('/board_default_thumbnail/{name}/{size}/{color?}', [ContentController::class, 'board_default_thumbnail']);
    Route::get('/board_icon_thumbnail/{path}/{size?}/{color?}', [ContentController::class, 'board_icon_thumbnail']);

    Route::get('/shared_thumbnail/{board_id}/{path}', [ContentController::class, 'sharedThumbnail']);
    Route::prefix('cdn')->group(function () {
        Route::get('/{any?}', [ContentController::class, 'fileTransferAll'])->where('any', '.*');
    });
    Route::get('/kintone_file', [ContentController::class, 'kintone_file']);
    Route::post('/auth_check', [UserController::class, 'auth_check']);

    Route::get('/lesson_files/{path}', [ContentController::class, 'lessonFileTransfer']);


        // Board
    Route::post('/board_list', [BoardController::class, 'board_list']); // 一覧表示API
    Route::post('/search_board_list', [BoardController::class, 'search_board_list']);
    Route::post('/board_create', [BoardController::class, 'board_create']); // 作成API
    Route::post('/board_edit', [BoardController::class, 'board_edit']); // 編集API
    Route::post('/board_delete', [BoardController::class, 'board_delete']); // 削除API
    Route::post('/get_messages', [BoardController::class, 'get_messages']); // コメント一覧表示API
    Route::post('/chat_add_api', [BoardController::class, 'chatAdd']); // ファイルアップロードAPI
    Route::post('/check_send_api', [BoardController::class, 'checkSend']); //確認フラグ送る
    Route::post('/chat_delete_api', [BoardController::class, 'chatDelete']); //メッセージ削除
    Route::post('/chat_edit_api', [BoardController::class, 'chatEdit']); //メッセージ削除
    Route::post('/chat_mark_unread', [BoardController::class, 'chat_mark_unread']);
    Route::patch('/board_badge', [BoardController::class, 'update_board_badge']); // #20201207_006
    Route::get('/board_badge', [BoardController::class, 'get_board_badge']); 
    Route::post('/icon_up_api', [BoardController::class, 'getIconUp']);
    Route::post('/pin_board_api', [BoardController::class, 'pinBoard']); 
    Route::post('/attach_upload_api', [BoardController::class, 'attachUpload']); 
    Route::post('/remove_temp_file', [BoardController::class, 'removeTemp']); 
    Route::post('/check_request_api', [BoardController::class, 'checkRequest']);
    Route::post('/remind_add', [BoardController::class, 'remindRequest']); 
    Route::post('/send_reaction_api', [BoardController::class, 'sendReaction']); 
    Route::post('/notification_board', [BoardController::class, 'notification_board']);
    
    Route::post('/message_search', [BoardController::class, 'messageSearch']);
    Route::post('/get_target_message', [BoardController::class, 'getTargetMessage']); 
    Route::post('/get_bottom_messages', [BoardController::class, 'getAppend']); 
    Route::post('/get_instant_user', [BoardController::class, 'getInstantUser']);   
    Route::post('/set_admin_role', [BoardController::class, 'setAdminRole']); 
    Route::post('/remove_group_member', [BoardController::class, 'removeGroupMember']); 
    Route::post('/group_add_member', [BoardController::class, 'groupAddMember']); 
    Route::post('/get_edit_user', [BoardController::class, 'getEditUser']);
    Route::post('/signature_upload_api', [BoardController::class, 'signFile']);
    // Route::post('/save_user_signature', [BoardController::class, 'saveSignature']);
    Route::post('/cancel_sign', [BoardController::class, 'cancelSignature']);
    Route::post('/leave_board', [BoardController::class, 'leaveBoard']);
    Route::post('/board_possible_users', [BoardController::class, 'board_possible_users']);
    Route::post('/send_reconfirm_email', [BoardController::class, 'send_reconfirm_email']);
    Route::post('/addable_board_members', [BoardController::class, 'addable_board_members']);
    Route::post('/get_file_list', [FileController::class, 'fetchFileList']); 
    Route::get('/incomplete_check', [BoardController::class, 'incomplete_check']);
    Route::put('/draft_send', [BoardController::class, 'draftSend']);
    Route::put('/set_message_schedule', [BoardController::class, 'set_message_schedule']);
    Route::put('/update_view_from', [BoardController::class, 'update_view_from']);
    Route::post('/send_emote', [BoardController::class, 'send_emote']);
    Route::post('/post_send_emote', [PostController::class, 'post_send_emote']);
    Route::post('/comment_send_emote', [PostController::class, 'comment_send_emote']);
    // Task
    Route::get('/task_list', [TaskController::class, 'getTask']); 
    Route::patch('/complete_task', [TaskController::class, 'completeTask']); 
    Route::patch('/task_item', [TaskController::class, 'updateTask']); 
    Route::post('/task_file_upload', [TaskController::class, 'task_file_upload']);
    Route::get('/task_badge', [TaskController::class, 'get_task_badge']);    
    Route::post('/task_edit_api', [TaskController::class, 'taskEdit']); 
    Route::delete('/task_item', [TaskController::class, 'taskDelete']);  
    Route::put('/task_item', [TaskController::class, 'addTask']);
    Route::put('/task_sub_item', [TaskController::class, 'addSubTask']);  
    Route::put('/task_approve_request', [TaskController::class, 'task_approve_request']);
    Route::put('/task_approve', [TaskController::class, 'task_approve']);
    Route::put('/task_update_prize', [TaskController::class, 'task_update_prize']);
    Route::put('/task_update_flag', [TaskController::class, 'task_update_flag']);
    Route::put('/task_update_pin', [TaskController::class, 'task_update_pin']);
    Route::post('/update_task_comment_check', [TaskController::class, 'update_task_comment_check']); 
    Route::get('/get_task_comment_list', [TaskController::class, 'get_task_comment_list']);
    Route::put('/task_comment', [TaskController::class, 'task_comment']); 
    Route::delete('/task_comment', [TaskController::class, 'task_comment_delete']); 
    Route::put('/task_comment_update', [TaskController::class, 'task_comment_update']); 
    Route::post('/add_board_task', [TaskController::class, 'addBoardTask']); 
        // Admin Panel User:
        Route::get('/get_controllable_users', [AdminAccountController::class, 'get_controllable_users']);
        Route::post('/user_add', [AdminAccountController::class, 'addUser']);
        Route::get('/get_monthly_prizes', [AdminAccountController::class, 'getMonthlyPrizes']);
        // Admin Panel Work Group
        Route::post('/work_group_add', [AdminAccountController::class, 'workgroupAdd']);
        Route::post('/work_group_edit', [AdminAccountController::class, 'workgroupEdit']);
        Route::post('/work_group_delete', [AdminAccountController::class, 'workgroupDelete']);
        // Admin Panel Work
        Route::post('/get_admin_work', [AdminWorkController::class, 'get_admin_work']);
        Route::get('/work_audit_logs', [AdminWorkController::class, 'work_audit_logs']);
        Route::get('/work_audit_logs/{event}', [AdminWorkController::class, 'work_audit_log_detail']);
        // Admin clap statistics
        Route::post('/clap_statistics', [AdminAccountController::class, 'clap_statistics']);
        Route::post('/get_planned_shifts', [AdminWorkController::class, 'get_planned_shifts']);
        Route::post('/change_planned_shifts', [AdminWorkController::class, 'change_planned_shifts']);
        Route::post('/one_shot_confirmation', [WorkController::class, 'one_shot_confirmation']);    
        
        //User
        Route::post('/user_generate_file_key', [UserController::class, 'generate_key']);
        Route::post('/user_icon_cropped_up_api', [UserController::class, 'croppedUp']);
        Route::post('/user_icon_create_api', [UserController::class, 'userIconCreate']); 
        Route::post('/profile_profile_edit_api', [UserController::class, 'profileEdit']); 
        Route::post('/user_pass_change_api', [UserController::class, 'passChange']); // パスワード変更API
        Route::post('/profile_get_update_user', [UserController::class, 'profile_get_update_user']);
        Route::post('/profile_set_color', [UserController::class, 'setColor']);
        Route::post('/get_user_claps', [UserController::class, 'getClaps']);
        Route::post('/user_file_upload', [UserController::class, 'userFileUpload']);
        Route::post('/user_file_delete', [UserController::class, 'user_file_delete']);
        Route::post('/save_intro', [UserController::class, 'save_intro']);
        Route::post('/mov_delete', [UserController::class, 'deleteMov']);
        Route::post('/save_user_signature', [UserController::class, 'saveSignature']);
        Route::get('/ical_url_generate', [CalendarController::class, 'ical_url_generate']);
        Route::post('/get_albums', [UserController::class, 'get_albums']);
        Route::patch('/set_active_linked_account', [UserController::class, 'set_active_linked_account']);
        Route::patch('/set_footer_view ', function(Request $request){
            $update = Auth::user()->update([
                "footer_view" => $request->value
            ]);
            return $update;
        }); 

        Route::post('/get_posts', [PostController::class, 'get_posts']);
        Route::post('/delete_post', [PostController::class, 'delete_post']);
        Route::post('/post_get_users', [PostController::class, 'post_get_users']);
        Route::post('/post_get_tags', [PostController::class, 'post_get_tags']);
        Route::post('/post_get_suggested_tags', [PostController::class, 'post_get_suggested_tags']);
        Route::post('/post_file_upload', [PostController::class, 'post_file_upload']);
        Route::post('/post_add_record', [PostController::class, 'post_add_record']);
        Route::post('/post_delete_file', [PostController::class, 'post_delete_file']);
        Route::get('/post_get_possible_charge', function (Request $request){
            return Auth::user()->award_charge;
        });
        Route::post('/challenge_charge_to', [PostController::class, 'challenge_charge_to']);
        Route::post('/get_post_comments', [PostController::class, 'get_post_comments']);
        Route::post('/post_comment_add', [PostController::class, 'post_comment_add']);
        Route::post('/post_add_clap', [PostController::class, 'post_add_clap']);
        Route::post('/post_comment_edit', [PostController::class, 'post_comment_edit']);
        Route::post('/post_comment_delete', [PostController::class, 'post_comment_delete']);
        Route::post('/post_status_update', [PostController::class, 'post_status_update']);
        Route::post('/post_get_post_users', [PostController::class, 'post_get_post_users']);
        Route::post('/post_get_all_possible_users', [PostController::class, 'post_get_all_possible_users']);
        Route::post('/post_get_challenge_users', [PostController::class, 'post_get_challenge_users']);
        Route::get('/post_badge', [PostController::class, 'get_post_badge']);
        Route::patch('/post_badge', [PostController::class, 'update_post_badge']);
        Route::get('/get_top_tags', [PostController::class, 'get_top_tags']);
        Route::post('/get_featured_tags', [PostController::class, 'get_featured_tags']);
        Route::post('/post_advanced_search', [PostController::class, 'post_advanced_search']);
        Route::post('/get_history', [PostController::class, 'get_history']);
        Route::post('/prepare_sharing_files', [PostController::class, 'prepare_sharing_files']);
        Route::post('/post_entries', [PostController::class, 'post_entries']);
        Route::post('/get_top_posts', [PostController::class, 'get_top_posts']);
        Route::post('/post_grant_upload', [PostController::class, 'post_grant_upload']);
        Route::post('/post_remove_file', [PostController::class, 'post_remove_file']);
        Route::prefix('/refresh')->group(function () {
            Route::get('/posts', [RefreshController::class, 'indexPosts']);
            Route::patch('/posts/{id}/approve', [RefreshController::class, 'approvePost']);
            Route::patch('/usages/{id}/confirm', [RefreshController::class, 'confirmPendingUsage']);
            Route::delete('/posts/{id}', [RefreshController::class, 'destroyPost']);
            Route::get('/kintone', [RefreshController::class, 'kintoneRecords']);
            Route::post('/kintone/sync', [RefreshController::class, 'syncKintone']);
            Route::get('/me/summary', [RefreshController::class, 'mySummary']);
            Route::get('/users/{id}/history', [RefreshController::class, 'userHistory']);
            Route::get('/management', [RefreshController::class, 'indexManagement']);
            Route::post('/management/grants', [RefreshController::class, 'storeManagementGrant']);
            Route::patch('/management/leave-review', [RefreshController::class, 'confirmLeaveReview']);
            Route::delete('/management/reviews', [RefreshController::class, 'destroyManagementReview']);
        });

        Route::post('/get_calendar_data', [CalendarController::class, 'get_calendar_data']);
        Route::post('/get_possible_facilities', [CalendarController::class, 'get_possible_facilities']);
        Route::post('/calendar_add_record', [CalendarController::class, 'calendar_add_record']);
        Route::post('/calendar_add_temp_record', [CalendarController::class, 'calendar_add_temp_record']);
        Route::post('/get_my_groups', [CalendarController::class, 'get_my_groups']);
        Route::post('/update_selected_calendar_members', [CalendarController::class, 'update_selected_calendar_members']);
        Route::post('/select_work_group', [CalendarController::class, 'select_work_group']);
        Route::post('/calendar_more_users', [CalendarController::class, 'calendar_more_users']);
        Route::get('/get_possible_groups', [CalendarController::class, 'get_possible_groups']);
        Route::post('/set_more_members', [CalendarController::class, 'set_more_members']);
        Route::post('/delete_my_group', [CalendarController::class, 'delete_my_group']);
        Route::post('/get_calendar_search', [CalendarController::class, 'get_calendar_search']);
        Route::post('/get_all_facilities', [CalendarController::class, 'get_all_facilities']);
        Route::post('/calendar_drop', [CalendarController::class, 'calendar_drop']);
        Route::post('/calendar_delete_record', [CalendarController::class, 'calendar_delete_record']);
        Route::get('/get_departments_calendar', [CalendarController::class, 'get_departments_calendar']);
        Route::get('/get_schedule_summaries', [CalendarController::class, 'get_schedule_summaries']);
        Route::put('/save_edited_summary', [CalendarController::class, 'save_edited_summary']);
        Route::delete('/delete_schedule_summary', [CalendarController::class, 'delete_schedule_summary']);
        Route::post('/calendar_temp_reserve', [CalendarController::class, 'calendar_temp_reserve']);
        Route::get('/all_facility_items', [CalendarController::class, 'all_facility_items']);
        Route::post('/calendar_temp_confirm', [CalendarController::class, 'calendar_temp_confirm']);
        Route::get('/check_google_calendars', [GoogleController::class, 'check_google_calendars']);
        Route::post('/save_google_calendar_settings', [GoogleController::class, 'save_google_calendar_settings']);
        Route::post('/disconnect_google_calendar', [GoogleController::class, 'disconnect_google_calendar']);
        Route::post('/get_google_calendar_events', [GoogleController::class, 'get_google_calendar_events']);
        Route::get('/public_holidays', [PublicHolidayController::class, 'index']);

        Route::post('/get_members_list', [MemberController::class, 'get_members_list']);
        Route::post('/get_kadai_list', [MemberController::class, 'get_kadai_list']);
        Route::post('/update_kadai', [MemberController::class, 'update_kadai']);
        Route::post('/save_kadai_template', [MemberController::class, 'save_kadai_template']);
        Route::post('/get_kadai_template', [MemberController::class, 'get_kadai_template']);
        Route::post('/check_kadai_record', [MemberController::class, 'check_kadai_record']);
        Route::post('/delete_kadai_template', [MemberController::class, 'delete_kadai_template']);
        Route::post('/delete_applied_issue', [MemberController::class, 'delete_applied_issue']);
        Route::get('/get_kadai_themes', [MemberController::class, 'get_kadai_themes']);
        Route::post('/get_applied_issues', [MemberController::class, 'get_applied_issues']);
        Route::post('/update_issue', [MemberController::class, 'update_issue']);
        Route::get('/get_performance_options', [MemberController::class, 'get_performance_options']);
        Route::post('/get_performance_records', [MemberController::class, 'get_performance_records']);
        Route::post('/get_job_evaluation', [MemberController::class, 'get_job_evaluation']);
        Route::get('/mark_condition_asread', [MemberController::class, 'mark_condition_asread']);
        Route::get('/get_today_comments', [MemberController::class, 'get_today_comments']);
        Route::post('/create_comment', [MemberController::class, 'create_comment']);
        Route::post('/create_custom_field_emote_user', [MemberController::class, 'create_custom_field_emote_user']);
        Route::get('/get_evaluation_levels', [ProjectController::class, 'get_evaluation_levels']);
        Route::get('/mentionable_users', [ProjectController::class, 'mentionable_users']);
        Route::post('/project_finance_comment', [ProjectController::class, 'project_finance_comment']);
        Route::get('/get_project_finance_comments', [ProjectController::class, 'get_project_finance_comments']);
        Route::get('/get_total_finance_badge', [ProjectController::class, 'get_total_finance_badge']);
        Route::post('/project_resource_comment', [ProjectController::class, 'project_resource_comment']);
        Route::get('/get_project_resource_comments', [ProjectController::class, 'get_project_resource_comments']);
        Route::put('/resource_comment_update', [ProjectController::class, 'resource_comment_update']);
        Route::delete('/resource_comment_delete', [ProjectController::class, 'resource_comment_delete']);
        Route::post('/get_resource_comment_counts', [ProjectController::class, 'get_resource_comment_counts']);
        Route::post('/project_create_member_role', [ProjectController::class, 'project_create_member_role']);
        Route::delete('/project_delete_member_role', [ProjectController::class, 'project_delete_member_role']);
        Route::post('/update_project_member_role', [ProjectController::class, 'update_project_member_role']);
        Route::post('/evaluate_member', [ProjectController::class, 'evaluate_member']);
        Route::post('/save_member_assign_data', [ProjectController::class, 'save_member_assign_data']);
        Route::get('/user_related_goal_member_data', [ProjectController::class, 'user_related_goal_member_data']);
        
        Route::patch('/project_change_status', [ProjectController::class, 'project_change_status']);
        Route::patch('/project_checkitem_update', [ProjectController::class, 'project_checkitem_update']);
        Route::post('/project_checkitem_comment_add', [ProjectController::class, 'project_checkitem_comment_add']);
        Route::post('/project_refresh', [ProjectController::class, 'project_refresh']);
        Route::post('/ensure_checkitems', [ProjectController::class, 'ensureProjectCheckitems']);
        Route::get('/project_types', [ProjectController::class, 'get_project_types']);
        Route::post('/project_types', [ProjectController::class, 'save_project_type']);
        Route::delete('/project_types/{projectType}', [ProjectController::class, 'delete_project_type']);
        Route::get('/check_item_categories', [ProjectController::class, 'check_item_categories']);
        Route::post('/check_item_categories', [ProjectController::class, 'save_check_item_category']);
        Route::delete('/check_item_categories/{category}', [ProjectController::class, 'delete_check_item_category']);
        Route::get('/project_checkitem_templates', [ProjectController::class, 'get_project_checkitem_templates']);
        Route::post('/create_update_checkitem', [ProjectController::class, 'create_update_checkitem']);
        Route::delete('/delete_checkitem/{checkitem}', [ProjectController::class, 'delete_checkitem']);
        Route::post('/mark_as_seen', [ProjectController::class, 'markAsSeen']);
        Route::get('/get_members_assign_data', [ProjectController::class, 'get_members_assign_data']);
        Route::post('/add_assign_action', [ProjectController::class, 'add_assign_action']);
        Route::post('/update_assign_support_level', [ProjectController::class, 'update_assign_support_level']);
        Route::post('/apply_assign_data_to_hr', [ProjectController::class, 'apply_assign_data_to_hr']);
        Route::post('/apply_assign_data_to_member', [ProjectController::class, 'apply_assign_data_to_member']);
        Route::post('/confirm_assign_record', [ProjectController::class, 'confirm_assign_record']);
        Route::post('/reapply_assign_data_to_member', [ProjectController::class, 'reapply_assign_data_to_member']);
        Route::delete('/delete_assign_record/{assignRecord}', [ProjectController::class, 'delete_assign_record']);

        Route::get('/get_work_data', [WorkController::class, 'getWorkData']);
        Route::get('/get_shift_data', [WorkController::class, 'get_shift_data']);
        Route::get('/get_shift_data_table', [WorkController::class, 'get_shift_data_table']);
        Route::post('/add_shift', [WorkController::class, 'shiftAdd']);
        Route::post('/get_work_group', [WorkController::class, 'getWorkGroup']);
        Route::post('/daily_report_add', [WorkController::class, 'dailyReportAdd']);
        Route::post('/daily_report_break', [WorkController::class, 'daily_report_break']);
        Route::post('/save_time_card', [WorkController::class, 'saveTimeCard']);
        Route::post('/delete_time_card', [WorkController::class, 'deleteTimeCard']);
        Route::get('/get_attendance_data', [WorkController::class, 'getAttendanceData']);
        Route::post('/remand_time_card', [WorkController::class, 'remandTimeCard']);
        Route::post('/approve_time_card', [WorkController::class, 'approveTimeCard']);
        Route::post('/cancel_time_card', [WorkController::class, 'cancelTimeCard']);
        Route::post('/attendance_confirm', [WorkController::class, 'attendanceConfirm']);
        Route::post('/attendance_delete', [WorkController::class, 'attendanceDelete']);
        Route::post('/attendance_closed', [WorkController::class, 'attendanceClose']);
        Route::get('/work_badge', [WorkController::class, 'work_badge']);
        Route::post('/request_overtime', [WorkController::class, 'request_overtime']);
        Route::delete('/request_overtime', [WorkController::class, 'delete_overtime']);
        Route::patch('/request_overtime', [WorkController::class, 'respond_overtime']);
        Route::patch('/shift_approve', [WorkController::class, 'shift_approve']);
        Route::patch('/shift_approve_all', [WorkController::class, 'shift_approve_all']);
        Route::delete('/work_cost_delete', [WorkController::class, 'work_cost_delete']);
        Route::delete('/work_incentive_delete', [WorkController::class, 'work_incentive_delete']);
        Route::post('/work_file_upload', [WorkController::class, 'work_file_upload']);
        Route::post('/work_file_delete', [WorkController::class, 'work_file_delete']);
        Route::post('/work_receipt_ocr', [WorkController::class, 'work_receipt_ocr']);
        Route::get('/next_month_shift', [WorkController::class, 'next_month_shift']);
        Route::get('/get_shift_with_work_group', [WorkController::class, 'get_shift_with_work_group']);
        Route::get('/work_generate_csv', [WorkController::class, 'work_generate_csv']);
        Route::get('/check_break_time', [WorkController::class, 'check_break_time']);
        Route::put('/shift_add_department', [WorkController::class, 'shift_add_department']);
        Route::post('/get_planned_leaves', [WorkController::class, 'get_planned_leaves']);
        Route::get('/annual_leave_data', [WorkController::class, 'annual_leave_data']);

        Route::get('/get_my_car_data', [WorkController::class, 'get_my_car_data']);
        Route::get('/get_remaining_days', [WorkController::class, 'get_remaining_days']);
        Route::get('/get_work_temp', [WorkController::class, 'get_work_temp']);

        Route::post('/custom_field_data', [CustomfieldController::class, 'customFieldRecordListMessage']);
        Route::post('/today_weather', [CustomfieldController::class, 'getTodayWeather']);
        Route::post('/save_weather', [CustomfieldController::class, 'saveWeather']);

        Route::post('/support_record_list', [SupportController::class, 'support_record_list']);
        Route::post('/support_Regulation_list', [SupportController::class, 'support_Regulation_list']);
        Route::post('/support_feedback', [SupportController::class, 'support_feedback']);
        Route::post('/support_resolve_decision', [SupportController::class, 'support_resolve_decision']);
        Route::post('/faq_add_record', [SupportController::class, 'faq_add_record']);
        Route::post('/faq_delete_record', [SupportController::class, 'faq_delete_record']);
        Route::post('/faq_tag_save', [SupportController::class, 'faq_tag_save']);
        Route::post('/faq_tag_delete', [SupportController::class, 'faq_tag_delete']);
        Route::post('/support_add_consult', [SupportController::class, 'support_add_consult']);
        Route::get('/get_recieved_consults', [SupportController::class, 'get_recieved_consults']);
        Route::post('/add_memo_to_consult', [SupportController::class, 'add_memo_to_consult']);
        Route::post('/update_consult_status', [SupportController::class, 'update_consult_status']);
        Route::post('/support_add_message', [SupportController::class, 'support_add_message']);
        Route::get('/search_regulations_from_files', [SupportController::class, 'search_regulations_from_files']);
        Route::post('/add_emergency_contact', [SupportController::class, 'add_emergency_contact']);
        Route::get('/get_emergency_contacts', [SupportController::class, 'get_emergency_contacts']);
        Route::post('/update_emergency_contact_status', [SupportController::class, 'update_emergency_contact_status']);
        Route::get('/get_emergency_contact_actions', [SupportController::class, 'get_emergency_contact_actions']);
        Route::post('/add_emergency_contact_action', [SupportController::class, 'add_emergency_contact_action']);
        Route::get('/system_updates', [SupportController::class, 'get_system_updates']);
        Route::post('/system_update_save', [SupportController::class, 'save_system_update']);
        Route::post('/system_update_delete', [SupportController::class, 'delete_system_update']);
        Route::post('/system_update_check', [SupportController::class, 'system_update_check']);

        Route::get('/get_notices', [NoticeController::class, 'get_notices']);
        Route::get('/get_notice', [NoticeController::class, 'get_notice']);
        Route::post('/read_notice', [NoticeController::class, 'read_notice']);
        Route::get('/notice_badge', [NoticeController::class, 'get_notice_badge']);
        Route::post('/notice_file_upload', [NoticeController::class, 'notice_file_upload']);
        Route::post('/notice_delete_file', [NoticeController::class, 'notice_delete_file']);
        Route::post('/notice_add_record', [NoticeController::class, 'notice_add_record']);
        Route::delete('/notice_delete', [NoticeController::class, 'notice_delete']);
        Route::get('/load_notice_body', [NoticeController::class, 'load_notice_body']);

        // Lessons
        Route::get('/get_lessons', [LessonController::class, 'get_lessons']);
        Route::get('/get_learning_themes', [LessonController::class, 'get_learning_themes']);
        Route::get('/get_lesson_themes', [LessonController::class, 'get_lesson_themes']);
        Route::post('/lesson_add_record', [LessonController::class, 'lesson_add_record']);
        Route::delete('/lesson_remove_record', [LessonController::class, 'lesson_remove_record']);
        Route::post('/save_lesson_portfolio', [LessonController::class, 'save_lesson_portfolio']);
        Route::post('/update_lesson_portfolio', [LessonController::class, 'update_lesson_portfolio']);
        Route::post('/get_lesson_portfolio', [LessonController::class, 'get_lesson_portfolio']);
        Route::post('/save_lesson_form', [LessonController::class, 'save_lesson_form']);
        Route::get('/get_portfolio_view', [LessonController::class, 'get_portfolio_view']);
        Route::get('/get_material_list', [LessonController::class, 'get_material_list']);
        Route::get('/get_material', [LessonController::class, 'get_material']);
        Route::get('/get_completed_lesson_themes', [LessonController::class, 'get_completed_lesson_themes']);

        Route::post('/create_learning_theme', [LessonController::class, 'create_learning_theme']);
        Route::delete('/delete_learning_theme', [LessonController::class, 'delete_learning_theme']);
        Route::get('/get_portfolios_list', [LessonController::class, 'get_portfolios_list']);

        Route::post('/upload_lesson_file', [LessonController::class, 'upload_lesson_file']);
        Route::get('/get_support_account', function () {
            $support = User::where('name', '研修サポート')->first();
            return empty($support) ? 0 : $support->id;
        });
        Route::get('/get_lesson_files', [LessonController::class, 'get_lesson_files']);
        Route::delete('/remove_lesson_file', [LessonController::class, 'remove_lesson_file']);

        Route::post('/section_update', [LessonController::class, 'section_update']);
        Route::put('/update_portfolio_status', [LessonController::class, 'update_portfolio_status']);
        Route::post('/update_lesson_answer', [LessonController::class, 'update_lesson_answer']);
        Route::post('/add_material_summary', [LessonController::class, 'add_material_summary']);
        Route::get('/get_forms', [LessonController::class, 'get_forms']);
        Route::delete('/lesson_remove_summary', [LessonController::class, 'lesson_remove_summary']);
        Route::post('/save_summary_answers', [LessonController::class, 'save_summary_answers']);
        Route::get('/get_theme_data', [LessonController::class, 'get_theme_data']);
        Route::get('/get_members_by_position', [LessonController::class, 'get_members_by_position']);

        Route::get('/lesson_exam', [LessonExamController::class, 'get_exam']);
        Route::post('/lesson_exam', [LessonExamController::class, 'save_exam']);
        Route::delete('/lesson_exam', [LessonExamController::class, 'delete_exam']);
        Route::get('/learning_exam', [LessonExamController::class, 'get_learning_exam']);
        Route::post('/learning_exam_submit', [LessonExamController::class, 'submit_exam']);
        Route::get('/lesson_exam_attempts', [LessonExamController::class, 'get_exam_attempts']);
        // Lessons

        // Project
        Route::get('/get_projects', [ProjectController::class, 'get_projects']);
        Route::post('/get_outcome_goals', [ProjectController::class, 'get_outcome_goals']);
        Route::get('/api/project/{projectId}/member/{memberId}', [ProjectController::class, 'get_member']);
        Route::post('/get_project_criteria', [ProjectController::class, 'get_project_criteria']);
        Route::post('/save_project_goal', [ProjectController::class, 'save_project_goal']);
        Route::post('/get_applied_goals', [ProjectController::class, 'get_applied_goals']);
        Route::put('/update_project_progress', [ProjectController::class, 'update_project_progress']);
        Route::put('/apply_kadai', [ProjectController::class, 'apply_kadai']);
        Route::post('/get_selectable_users', [ProjectController::class, 'get_selectable_users']);
        Route::post('/users_with_goals', [ProjectController::class, 'users_with_goals']);
        Route::get('/check_goal_create_permission', [ProjectController::class, 'check_goal_create_permission']);
        Route::post('/get_previous_evaluation', [ProjectController::class, 'get_previous_evaluation']);
        Route::post('/create_project', [ProjectController::class, 'create_project']);
        Route::get('/get_salary_options', [ProjectController::class, 'get_salary_options']);
        Route::post('/get_evaluations', [ProjectController::class, 'get_evaluations']);
        Route::post('/save_evaluation_grade', [ProjectController::class, 'save_evaluation_grade']);
        Route::post('/upload_evaluation_csv', [ProjectController::class, 'upload_evaluation_csv']);
        Route::put('/save_member_role', [ProjectController::class, 'save_member_role']);
        Route::post('/set_increase_request', [ProjectController::class, 'set_increase_request']);
        Route::post('/get_evaluation_data', [ProjectController::class, 'get_evaluation_data']);
        Route::delete('/delete_project_goal', [ProjectController::class, 'delete_project_goal']);
        Route::put('/approve_salary_issue', [ProjectController::class, 'approve_salary_issue']);
        Route::post('/get_salary_issues', [ProjectController::class, 'get_salary_issues']);
        Route::delete('/delete_project', [ProjectController::class, 'delete_project']);
        Route::put('/approve_outcome_goal', [ProjectController::class, 'approve_outcome_goal']);
        Route::put('/update_issue_report', [ProjectController::class, 'update_issue_report']);
        Route::delete('/delete_issue', [ProjectController::class, 'delete_issue']);
        Route::get('/project_badge', [ProjectController::class, 'get_project_badge']);
        Route::get('/check_evaluation_for_user_in_span ', [ProjectController::class, 'check_evaluation_for_user_in_span']);
        Route::get('/get_manuals', [ProjectController::class, 'get_manuals']);
        Route::post('/update_manuals', [ProjectController::class, 'update_manuals']);
        Route::post('/create_manual_rule', [ProjectController::class, 'create_manual_rule']);
        Route::post('/create_manual_record', [ProjectController::class, 'create_manual_record']);
        Route::post('/delete_manual_rule', [ProjectController::class, 'delete_manual_rule']);
        Route::post('/delete_manual_record', [ProjectController::class, 'delete_manual_record']);
        Route::get('/get_yearly_plan', [ProjectController::class, 'get_yearly_plan']);
        Route::get('/get_profit', [ProjectController::class, 'get_profit']);
        Route::get('/get_settlement', [ProjectController::class, 'get_settlement']);
        Route::post('/get_partners_tags', [ProjectController::class, 'get_partners_tags']);
        Route::get('/get_task_comment_badge', [ProjectController::class, 'get_task_comment_badge']);
        Route::get('/get_dispatch_data', [ProjectController::class, 'get_dispatch_data']);
        Route::get('/get_total_finance', [ProjectController::class, 'get_total_finance']);
        Route::post('/set_project_goal_step_status', [ProjectController::class, 'set_project_goal_step_status']);
        Route::post('/project_goal_report_create', [ProjectController::class, 'project_goal_report_create']);
        Route::post('/get_previous_goals', [ProjectController::class, 'get_previous_goals']);
        Route::post('/save_project_progress', [ProjectController::class, 'save_project_progress']);
        Route::post('/salary_issue_action_complete', [ProjectController::class, 'salary_issue_action_complete']);
        Route::get('/projects/{project}/finance-comments/monthly-count', [ProjectController::class, 'monthlyCount']);
        Route::post('/projects/{project}/finance/mark-read', [ProjectController::class, 'mark_finance_read']);
        Route::get('/projects/finance/unread-badges', [ProjectController::class, 'get_finance_comment_badge']);
        Route::put('/finance_comment_update', [ProjectController::class, 'finance_comment_update']);
        Route::delete('/finance_comment_delete', [ProjectController::class, 'finance_comment_delete']);
        Route::post('/get_comment_count_from_total', [ProjectController::class, 'get_comment_count_from_total']);
        Route::post('/finance_check', [ProjectController::class, 'finance_check']);
        Route::get('/clear_project_report_badge', [ProjectController::class, 'clear_project_report_badge']);
        Route::get('/clear_project_confirm_badge', [ProjectController::class, 'clear_project_confirm_badge']);

        Route::get('/get_members_goals_badge', [ProjectController::class, 'get_members_goals_badge']);
        Route::get('/get_managers_goals_badge', [ProjectController::class, 'get_managers_goals_badge']);
        Route::get('/get_salary_issue_badge', [ProjectController::class, 'get_salary_issue_badge']);

        Route::get('/get_contracts', [ProjectController::class, 'get_contracts']);

        Route::get('/project_actual_status_suggestions', [ProjectController::class, 'project_actual_status_suggestions']);
        Route::post('/get_resources_kintone', [ProjectController::class, 'get_resources_kintone']);
        Route::post('/update_resource_kintone', [ProjectController::class, 'update_resource_kintone']);

        // Project plan (accounts/amounts) - new prefixed schema
        Route::get('/projects/{project}/plan/grid', [ProjectPlanController::class, 'grid']);
        Route::post('/projects/{project}/plan/grid', [ProjectPlanController::class, 'save']);
        Route::post('/projects/{project}/plan/lock', [ProjectPlanController::class, 'lock']);
        Route::post('/projects/{project}/plan/unlock', [ProjectPlanController::class, 'unlock']);
        Route::get('/projects/{project}/plan/scenarios', [ProjectPlanController::class, 'scenarios']);
        Route::post('/projects/{project}/plan/scenarios', [ProjectPlanController::class, 'scenarioStore']);
        Route::put('/projects/{project}/plan/scenarios/{scenario}', [ProjectPlanController::class, 'scenarioUpdate']);
        Route::delete('/projects/{project}/plan/scenarios/{scenario}', [ProjectPlanController::class, 'scenarioDestroy']);
        Route::get('/projects/{project}/plan/template', [ProjectPlanController::class, 'downloadTemplate']);
        Route::post('/projects/{project}/plan/template', [ProjectPlanController::class, 'uploadTemplate']);
        Route::get('/projects/{project}/accounts', [ProjectPlanController::class, 'accounts']);
        Route::post('/projects/{project}/accounts', [ProjectPlanController::class, 'accountStore']);
        Route::post('/projects/{project}/accounts/sync-template', [ProjectPlanController::class, 'syncTemplate']);
        Route::put('/projects/{project}/accounts/{account}', [ProjectPlanController::class, 'accountUpdate']);
        Route::delete('/projects/{project}/accounts/{account}', [ProjectPlanController::class, 'accountDestroy']);
        Route::get('/projects/{project}/cases', [ProjectController::class, 'project_cases']);
        Route::post('/projects/{project}/cases', [ProjectController::class, 'project_case_store']);
        Route::put('/projects/{project}/cases/{case}', [ProjectController::class, 'project_case_update']);
        Route::post('/project_goal_comment_create', [ProjectController::class, 'project_goal_comment_create']);
        Route::post('/project_goal_report_file_upload', [ProjectController::class, 'project_goal_report_file_upload']);
        Route::get('/project_list', [ProjectController::class, 'project_list']);
        Route::post('/view_case', [ProjectController::class, 'view_case']);
        Route::delete('/delete_case/{case}', [ProjectController::class, 'delete_case']);
        Route::post('/projects/{project}/contract', [ProjectController::class, 'store_contract'])->name('projects.contract.store');
        Route::get('/projects/{project}/contract', [ProjectController::class, 'show_contract'])->name('projects.contract.show');
        Route::get('/projects/{project}/contract/file', [ProjectController::class, 'preview_contract'])->name('projects.contract.preview');
        Route::get('/projects/{project}/contract/download', [ProjectController::class, 'download_contract'])->name('projects.contract.download');
        Route::get('/projects/{project}/contract/extract', [ProjectController::class, 'extract_contract'])->name('projects.contract.extract');
        Route::delete('/projects/{project}/contract/{contract}', [ProjectController::class, 'delete_contract'])->name('projects.contract.delete');
        Route::post('/save_review', [ProjectController::class, 'save_review']);
        Route::post('/get_non_member_users', [ProjectController::class, 'get_non_member_users']);
        Route::post('/get_non_member_assign_data', [ProjectController::class, 'get_non_member_assign_data']);

        Route::get('/get_gantt_tasks', [TaskController::class, 'get_gantt_tasks']);
        Route::get('/get_gantt_projects', [TaskController::class, 'get_gantt_projects']);
        Route::patch('/quick_edit_task', [TaskController::class, 'quick_edit_task']);
        Route::get('/get_gantt_project_tasks', [TaskController::class, 'get_gantt_project_tasks']);
        
        Route::get('/get_custom_forms', [CustomFormController::class, 'get_custom_forms']);
        Route::get('/get_active_project_creation_form', [CustomFormController::class, 'get_active_project_creation_form']);
        Route::get('/custom_forms/{form}/projects', [CustomFormController::class, 'get_form_projects']);
        Route::post('/duplicate_custom_form', [CustomFormController::class, 'duplicate_custom_form']);
        Route::post('/save_custom_form', [CustomFormController::class, 'save_custom_form']);
        Route::get('/get_survey', [CustomFormController::class, 'get_survey']);
        Route::post('/save_survey_answer', [CustomFormController::class, 'save_survey_answer']);
        Route::get('/get_survey_answers', [CustomFormController::class, 'get_survey_answers']);
        Route::delete('/delete_custom_form', [CustomFormController::class, 'delete_custom_form']);
        Route::post('/get_authorized_users', [CustomFormController::class, 'get_authorized_users']);
        Route::get('/get_my_surveys', [CustomFormController::class, 'get_my_surveys']);
        Route::get('/get_board_forms', [CustomFormController::class, 'get_board_forms']);
        Route::put('/save_form_prize', [CustomFormController::class, 'save_form_prize']);
        Route::post('/update_custom_form_status', [CustomFormController::class, 'update_custom_form_status']);

        //Contact
        Route::post('contact_item', [ContactController::class, 'create_contact']);
        Route::delete('contact_item', [ContactController::class, 'delete_contact']);
        Route::post('upload_name_card', [ContactController::class, 'upload_name_card']);
        Route::get('contact_list', [ContactController::class, 'contact_list']);
        Route::post('contact_private_memo', [ContactController::class, 'update_private_memo']);
        Route::get('google_test', [ContactController::class, 'index_test']);
        Route::post('scan_card', [ContactController::class, 'scan_card']);
        Route::get('get_contact_types', [ContactController::class, 'get_contact_types']);
        Route::get('contact_duplicates', [ContactController::class, 'duplicate_index']);
        Route::post('contact_duplicates/{contact}', [ContactController::class, 'resolve_duplicate']);
        Route::post('/contact_create_comment', [ContactController::class, 'contact_create_comment']);
        Route::post('/follow_contact', [ContactController::class, 'follow_contact']);
        Route::post('/contact/{contact}/comment_read', [ContactController::class, 'contact_comment_read']);
        Route::get('get_contact_comment_badge', [ContactController::class, 'get_contact_comment_badge']);
        Route::delete('/unfollow_contact/{contact}', [ContactController::class, 'unfollow_contact']);
        Route::get('/contact_batches', [ContactController::class, 'contact_batches']);
        Route::post('/contact_batches/{batch}/dismiss', [ContactController::class, 'dismiss_contact_batch']);
        Route::get('/contact_batch_notifications', [ContactController::class, 'contact_batch_notifications']);
        Route::post('/contact_batch_notifications/{notification}/read', [ContactController::class, 'contact_batch_notification_read']);
        
        // Remind
        Route::get('/remind_attendance', [RemindController::class, 'remind_attendance']);
        Route::get('/remind_unsigned_messages', [RemindController::class, 'remind_unsigned_messages']);
        Route::get('/remind_unchecked_messages', [RemindController::class, 'remind_unchecked_messages']);
        Route::get('/remind_task_not_approved', [RemindController::class, 'remind_task_not_approved']);
        Route::get('/remind_project_not_approved', [RemindController::class, 'remind_project_not_approved']);
        Route::get('/remind_timesheet', [RemindController::class, 'remind_timesheet']);
        Route::get('/remind_planned_leave', [RemindController::class, 'remind_planned_leave']);
        Route::get('/remind_reminded_messages', [RemindController::class, 'remind_reminded_messages']);
        Route::get('/remind_task_untouched', [RemindController::class, 'remind_task_untouched']);
        Route::get('/remind_task_unfinished', [RemindController::class, 'remind_task_unfinished']);
        Route::get('/remind_form', [RemindController::class, 'remind_form']);
        Route::get('/remind_asset', [RemindController::class, 'remind_asset']);
        Route::get('/remind_badge', [RemindController::class, 'remind_badge']);
        Route::get('/remind_summary', [RemindController::class, 'remind_summary']);
        Route::get('/remind_temp_reserved_schedules', [RemindController::class, 'remind_temp_reserved_schedules']);
        Route::get('/remind_departure_report', [RemindController::class, 'remind_departure_report']);
        Route::get('/check_departure_report', [RemindController::class, 'check_departure_report']);
        Route::post('/send_departure_report', [WorkController::class, 'send_departure_report']);
        Route::get('/remind_challenge_progress', [RemindController::class, 'remind_challenge_progress']);
        Route::get('/get_today_readable', [RemindController::class, 'get_today_readable']);
        Route::get('/badge_summary', [RemindController::class, 'badge_summary']);
        Route::get('/remind_overdue', [RemindController::class, 'remind_overdue']);
        Route::get('/remind_goal_slot', [RemindController::class, 'remind_goal_slot']);


        Route::get('/generate_welcome_message', [AutoJobController::class, 'generate_welcome_message']);
        Route::get('/welcome_message ', [AutoJobController::class, 'get_welcome_message']);
        Route::get('/get_today_things', [AutoJobController::class, 'get_today_things']);
        // Asset
        Route::get('/get_possible_projects', [AssetController::class, 'get_possible_projects']);
        Route::get('/get_possible_projects_by_user', [AssetController::class, 'get_possible_projects_by_user']);

        Route::get('/get_possible_members', [AssetController::class, 'get_possible_members']);
        Route::get('/get_asset_users', [AssetController::class, 'get_asset_users']);
        Route::post('/create_asset', [AssetController::class, 'create_asset']);
        Route::get('/get_assets', [AssetController::class, 'get_assets']);       
        Route::get('/admin_asset_list', [AssetController::class, 'admin_asset_list']);       
        Route::delete('/delete_asset', [AssetController::class, 'delete_asset']);
        Route::post('/asset_apply_request', [AssetController::class, 'asset_apply_request']);
        Route::post('/asset_return_request', [AssetController::class, 'asset_return_request']);
        Route::get('/get_assets_history', [AssetController::class, 'get_assets_history']);
        Route::put('/asset_request_status', [AssetController::class, 'asset_request_status']);
        Route::get('/get_possible_offices', [AssetController::class, 'get_possible_offices']);
        Route::post('/asset_recieve_request', [AssetController::class, 'asset_recieve_request']);
        Route::post('/asset_move_request', [AssetController::class, 'asset_move_request']);
        Route::post('/asset_approve', [AssetController::class, 'asset_approve']);
        Route::get('/export_asset_csv', [AssetController::class, 'export_asset_csv']);
        Route::post('/confirm_asset', [AssetController::class, 'confirm_asset']);
        Route::post('/asset_decision', [AssetController::class, 'asset_decision']);
        Route::get('/asset_reveal_password', [AssetController::class, 'asset_reveal_password']);

        Route::post('/get_asset_types', [AssetController::class, 'get_asset_types']);
        Route::get('/get_asset_badge', [ProjectController::class, 'get_asset_badge']);

        // Asset category items (single layer)
        Route::get('/get_asset_category_items', [AssetCategoryController::class, 'get_asset_category_items']);
        // Backward-compatible alias
        Route::get('/get_asset_categories', [AssetCategoryController::class, 'get_asset_categories']);

        Route::post('/create_asset_category_item', [AssetCategoryController::class, 'create_asset_category_item']);
        Route::put('/update_asset_category_item', [AssetCategoryController::class, 'update_asset_category_item']);
        Route::delete('/delete_asset_category_item', [AssetCategoryController::class, 'delete_asset_category_item']);
        Route::post('/duplicate_asset_category_item', [AssetCategoryController::class, 'duplicate_asset_category_item']);
        Route::post('/reorder_asset_category_items', [AssetCategoryController::class, 'reorder_asset_category_items']);

        Route::post('/create_asset_category_item_field', [AssetCategoryController::class, 'create_asset_category_item_field']);
        Route::put('/update_asset_category_item_field', [AssetCategoryController::class, 'update_asset_category_item_field']);
        Route::delete('/delete_asset_category_item_field', [AssetCategoryController::class, 'delete_asset_category_item_field']);
        Route::post('/reorder_asset_category_item_fields', [AssetCategoryController::class, 'reorder_asset_category_item_fields']);

        Route::get('/db_structure', [AutoJobController::class, 'db_structure']);

        Route::get('/drive', [DriveController::class,'index']);
        Route::post('/drive/folders', [DriveController::class,'createFolder']);
        Route::post('/drive/upload', [DriveController::class,'upload']);
        Route::patch('/drive/{id}', [DriveController::class,'rename']);
        Route::post('/drive/delete', [DriveController::class,'destroy']);
        Route::get('/drive_thumbnail/{b64path}/{size}/{color?}', [DriveController::class, 'drive_thumbnail'])
        ->where([
            'b64path' => '[A-Za-z0-9\-_]+',    // base64url
            'size'    => 'original|\d{1,4}',   // “original” or px (e.g., 128)
            'color'   => '[A-Fa-f0-9]{6}',     // hex without '#'
        ]);
        Route::get('/drive/files/{id}/download', [DriveController::class, 'downloadFile']);
        Route::get('/drive/folders/{id}/download.zip', [DriveController::class, 'downloadFolderZip']);
        Route::post('/drive/zip', [DriveController::class, 'downloadMultiZip']);
        Route::get('/drive/{id}/sharing',  [DriveController::class,'show']);     // read current state
        Route::put('/drive/{id}/sharing',  [DriveController::class,'update']);   // set visibility + members (+ cascade)
        Route::post('/drive/{id}/share/grant',  [DriveController::class,'grant']);  // optional fine-grain
        Route::delete('/drive/{id}/share/revoke',[DriveController::class,'revoke']);
        Route::get('/drive/preview/{id}', [DriveController::class, 'previewFile']);
        Route::get('/drive/logs', [DriveController::class, 'logs']);
        Route::post('/drive/move', [DriveController::class,'move']);
        Route::post('/drive/access_logs', [DriveController::class, 'writeAccessLogs']);
        Route::post('/drive/download_logs', [DriveController::class, 'writeDownloadLogs']);
        // Regulations
        Route::get('/get_regulation_list', [SupportController::class, 'get_regulations']);
        Route::get('/regulations', [SupportController::class, 'get_regulations']);
        Route::post('/regulation_add_record', [SupportController::class, 'save_regulation']);
        Route::post('/regulation_delete', [SupportController::class, 'delete_regulation']);
        Route::post('/regulation_file_upload', [SupportController::class, 'regulation_file_upload']);
        Route::post('/support_add_message', [SupportController::class, 'support_add_message']);
        Route::get('/get_conversations_history', [SupportController::class, 'get_conversations_history']);
        Route::post('/delete_conversation', [SupportController::class, 'delete_conversation']);

        Route::post('/scan_batch_cards', [ContactController::class, 'scan_batch_cards']);
        Route::get('/check_batch_status', [ContactController::class, 'check_batch_status']);
        Route::get('/get_batch_results', [ContactController::class, 'get_batch_results']);
        Route::get('/get_batch_company_data', [ContactController::class, 'get_batch_company_data']);
        Route::get('/get_batch_results1', [ContactController::class, 'get_batch_results1']);

        Route::get('get_office_list', [CommunityController::class, 'get_office_list']);
        Route::post('/office_item', [CommunityController::class, 'create_office_item']);
        Route::delete('/office_item', [CommunityController::class, 'delete_office_item']);

        Route::post('/ai_correction_prepare', [OpenAiController::class, 'prepare']);
        Route::post('/non_stream_prompt', [OpenAiController::class, 'non_stream_prompt']);
        Route::get('/stream_prompt', [OpenAiController::class, 'stream_prompt']);
        Route::post('/review_document', [OpenAiController::class, 'review_document']);
        Route::post('/summarize_contract_comparison', [OpenAiController::class, 'summarize_contract_comparison']);
        Route::post('/suggest_challenge', [OpenAiController::class, 'suggest_challenge']);
        Route::get('/lunch_challenge_popup', [OpenAiController::class, 'lunch_challenge_popup']);
        Route::post('/chatkit/session', [OpenAiController::class, 'session']);

        Route::get('/goal_issue_comment_badge', [ProjectController::class, 'goal_issue_comment_badge']);
        Route::post('/clear_goal_issue_badge', [ProjectController::class, 'clear_goal_issue_badge']);
        Route::get('/must_sync_check', [AutoJobController::class, 'must_sync_check']);

        Route::get('/dashboard_data', [DashboardController::class, 'dashboard_data']);
        Route::get('/app_comments', [AppCommentController::class, 'index']);
        Route::post('/app_comments', [AppCommentController::class, 'store']);
        Route::get('/app_comment_mentionable_users', [AppCommentController::class, 'mentionableUsers']);
        Route::get('/get_incidents', [DashboardController::class, 'getIncidents']);
        Route::get('/incident_page', [DashboardController::class, 'getIncidentPage']);
        Route::get('/incident_options', [DashboardController::class, 'getIncidentOptions']);
        Route::get('/incident_settings', [DashboardController::class, 'getIncidentSettings']);
        Route::post('/incident_category', [DashboardController::class, 'createIncidentCategory']);
        Route::put('/incident_category', [DashboardController::class, 'updateIncidentCategory']);
        Route::delete('/incident_category', [DashboardController::class, 'deleteIncidentCategory']);
        Route::post('/incident_categories/reorder', [DashboardController::class, 'reorderIncidentCategories']);
        Route::post('/incident_status', [DashboardController::class, 'createIncidentStatus']);
        Route::put('/incident_status', [DashboardController::class, 'updateIncidentStatus']);
        Route::delete('/incident_status', [DashboardController::class, 'deleteIncidentStatus']);
        Route::post('/incident_statuses/reorder', [DashboardController::class, 'reorderIncidentStatuses']);
        Route::post('/incident_punishment', [DashboardController::class, 'createIncidentPunishment']);
        Route::put('/incident_punishment', [DashboardController::class, 'updateIncidentPunishment']);
        Route::delete('/incident_punishment', [DashboardController::class, 'deleteIncidentPunishment']);
        Route::post('/incident_punishments/reorder', [DashboardController::class, 'reorderIncidentPunishments']);
        Route::get('/incident_logs', [DashboardController::class, 'getIncidentLogs']);
        Route::post('/incident_record_create', [DashboardController::class, 'createIncidentRecord']);
        Route::post('/incident_record_update', [DashboardController::class, 'updateIncidentRecord']);
        Route::post('/incident_record_delete', [DashboardController::class, 'deleteIncidentRecord']);
        Route::get('/community_members_tree', [CommunityController::class, 'community_members_tree']);
});
     Route::post('/tts_stream', [OpenAiController::class, 'stream_tts']);
