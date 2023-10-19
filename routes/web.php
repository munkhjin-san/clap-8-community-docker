<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\MembersController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\AdminOfficeController;
use App\Http\Controllers\AdminPositionController;
use App\Http\Controllers\AdminWorkGroupController;
use App\Http\Controllers\AppRuleController;
use App\Http\Controllers\KnowledgeRecordsController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\NiceRecordsController;
use App\Http\Controllers\ChallengeRecordsController;
use App\Http\Controllers\EmployeeRecordsController;
use App\Http\Controllers\SupportRecordsController;
use App\Http\Controllers\WorkController;
use App\Http\Controllers\CustomfieldController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\AutoJobController;
use App\Http\Controllers\AdminWorkController;
use App\Http\Controllers\MemberController;
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
Route::get('app/public/{app_name}', function ($app_name, Request $request) {    
    $query = $request->getQueryString(); 
    $url = "/{$app_name}";    
    if ($query) {
        $url .= '?' . $query;
    }    
    return redirect($url);
});

Route::get('/reacted_users_make', [AutoJobController::class, 'reactedUsersMake']);
Route::get('/change_to_dummy', [AutoJobController::class, 'change_to_dummy']);
Route::get('/help/{any?}', function () {
    return view('help');
})->where('any', '.*')->name('help');
Route::match(['get', 'post'], '/cron-trigger', [AutoJobController::class, 'cronTest']);

Auth::routes();
// CDN for External API
Route::get('/storage/app/private/{folder}/{folder_id}/{path}', [FileController::class, 'getFile']);
Route::get('/shared_docs/{board_id}/{path}/{keyword}/{user_id}', [ContentController::class, 'docTransfer']);
Route::get('/managed_docs/{board_id}/{path}/{keyword}/{user_id}', [ContentController::class, 'docTransfer']);
Route::get('/{app_name}/root/{sub_folder}/{path}/{keyword}/{user_id}', [PostController::class, 'cdnExtractDocsPost']);
Route::get('/firstload', [NotificationController::class, "index"]);

Route::view('/auth', 'auth.login')->name('auth')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');



Route::get('/health', function () {
    return response('OK', 200)->header('Content-Type', 'text/plain');
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\RedirectIfAuthenticated::class]);


Route::post('/report_send', [ContentController::class, 'reportSend']);
Route::group(["middleware"=>"auth"],function(){
    $id = Auth::id();
    Route::get('request_private_chat/{id}',[MembersController::class, 'chatRequest']);
    Route::get('/user/{id}',  [BoardController::class, "index"]);
    Route::get('/user', function () {
        $id = Auth::id();
        return redirect("/user/{$id}");
    });
    Route::get('/user/{id}/{settings}',  [BoardController::class, "index"]);
        Route::get('/' ,function () {
            {return Redirect::route('board');}
        });
        Route::get('/board', [BoardController::class, "index"])->name('board');
        Route::get('/board/{id}', [BoardController::class, "index"])->name('board_room');
        Route::get('/board/{id}/{app_name}/{folder_id}' ,function ($id) {
            return redirect("/board/{$id}");
        });
        Route::get('/board/{id}/{app_name}' ,function ($id) {
            return redirect("/board/{$id}");
        })->where('app_name', '(task|memo|file)');;
        Route::get('/home',function () {
            {return Redirect::route('board');}
        });
        Route::get('/employee', function () {return redirect("/members");});
        // Route::get('/members', [BoardController::class, "index"]);
        // Route::get('/knowledge', [BoardController::class, "index"]);
        // Route::get('/nice', [BoardController::class, "index"]);
        // Route::get('/challenge', [BoardController::class, "index"]);

        Route::get('/{name}/{path?}',[BoardController::class, "index"])->where('name', '(challenge|knowledge|nice|members|calendar|work|admin_control)');
        
        // Route::get('/{name}',function () {
        //     {return Redirect::route('board');}
        // })->where('name', '(message|file|task|memo)');
        // Route::get('/users/{user}', [UserController::class, 'show']);

        
        Route::post('/auth_check', function (Request $request) {
            $r = Auth::id() == $request->id;
            if($r){
                return response()->json();
            }else{
                throw ValidationException::withMessages(['message' => 'Unauthenticated']);
            }
            
        });
        // Content
        Route::get('/content/{which}/{path}', [ContentController::class, 'iconTransfer']);              
        Route::get('/qr/{token}_{id}', [ContentController::class, 'qrTransfer']);
        Route::get('/chat_qr/{token}_{id}', [ContentController::class, 'chatQrTransfer']);
        Route::get('/shared_files/{board_id}/{path}', [ContentController::class, 'sharedFileTransfer']);
        Route::get('/shared_files/{board_id}/thumbs/{path}', [ContentController::class, 'sharedFileThumbTransfer']);
        Route::get('/temp_upload/{path}', [ContentController::class, 'tempUploadFile']);
        Route::get('/user_signature/{path}', [ContentController::class, 'getSignature']);
        Route::get('/user/root/user_album/{folder_id}/{path}', [UserController::class, 'cdnMovie']);
        Route::get('/{app_name}/managed_files/{board_id}/{path}', [FileController::class, 'cdnExtract']);
        Route::get('/{app_name}/managed_files/{board_id}/{sub_folder}/{path}', [FileController::class, 'cdnSubExtract']);

        Route::get('/managed_files/{board_id}/{path}', [ContentController::class, 'managedFileTransfer']);
        Route::get('/managed_files/{board_id}/{sub_folder}/{path}', [ContentController::class, 'managedFileThumbTransfer']);

        Route::get('/post_files/{path}', [ContentController::class, 'postFileTransfer']);
        Route::get('/calendar_files/{path}', [ContentController::class, 'calendarFileTransfer']);
        Route::get('/user_files/{user_id}/{path}', [ContentController::class, 'userFileTransfer']);
        // Board
        Route::post('/chat_list', [BoardController::class, 'getAllMessage']); // 一覧表示API
        Route::post('/chat_create', [BoardController::class, 'create_new_board']); // 作成API
        Route::post('/messages_edit_api', [BoardController::class, 'postEditMessage']); // 編集API
        Route::post('/messages_delete_api', [BoardController::class, 'postDeleteMessage']); // 削除API
        Route::post('/get_messages', [BoardController::class, 'getCommentList']); // コメント一覧表示API
        Route::post('/chat_add_api', [BoardController::class, 'chatAdd']); // ファイルアップロードAPI
        Route::post('/check_send_api', [BoardController::class, 'checkSend']); //確認フラグ送る
        Route::post('/chat_delete_api', [BoardController::class, 'chatDelete']); //メッセージ削除
        Route::post('/chat_edit_api', [BoardController::class, 'chatEdit']); //メッセージ削除
        Route::post('/notification_update_api', [BoardController::class, 'notificationUpdate']); // #20201207_006
        Route::post('/notification_get_api', [BoardController::class, 'notificationGet']); 
        Route::post('/icon_up_api', [BoardController::class, 'getIconUp']);
        Route::post('/get_task_api', [BoardController::class, 'getTask']); 
        Route::post('/get_memo_api', [BoardController::class, 'getMemo']); 
        Route::post('/complete_task_api', [BoardController::class, 'completeTask']); 
        Route::post('/task_update_api', [BoardController::class, 'updateTask']); 
        Route::post('/task_notify_api', [BoardController::class, 'notifyTask']); 
        Route::post('/tab_update_api', [BoardController::class, 'tabUpdate']); 
        Route::post('/add_group_api', [BoardController::class, 'addGroup']); 
        Route::post('/get_group_api', [BoardController::class, 'getGroup']); 
        Route::post('/edit_group_api', [BoardController::class, 'editGroup']); 
        Route::post('/delete_group_api', [BoardController::class, 'deleteGroup']); 
        Route::post('/pin_board_api', [BoardController::class, 'pinBoard']); 
        Route::post('/task_edit_api', [BoardController::class, 'taskEdit']); 
        Route::post('/task_delete_api', [BoardController::class, 'taskDelete']); 
        Route::post('/attach_upload_api', [BoardController::class, 'attachUpload']); 
        Route::post('/remove_temp_file', [BoardController::class, 'removeTemp']); 
        Route::post('/check_request_api', [BoardController::class, 'checkRequest']);
        Route::post('/remind_add', [BoardController::class, 'remindRequest']); 
        Route::post('/send_reaction_api', [BoardController::class, 'sendReaction']); 
        Route::post('/add_task_api', [BoardController::class, 'addTask']); 
        Route::post('/message_search', [BoardController::class, 'messageSearch']);
        Route::post('/get_target_message', [BoardController::class, 'getTargetMessage']); 
        Route::post('/get_bottom_messages', [BoardController::class, 'getAppend']); 
        Route::post('/get_instant_user', [BoardController::class, 'getInstantUser']); 
        Route::post('/add_memo', [BoardController::class, 'addMemo']); 
        Route::post('/edit_memo', [BoardController::class, 'editMemo']);     
        Route::post('/delete_memo', [BoardController::class, 'deleteMemo']);
        Route::post('/set_memo_edit_user', [BoardController::class, 'set_memo_edit_user']);
        Route::post('/update_remember', [BoardController::class, 'updateRemember']); 
        Route::post('/get_all_members', [BoardController::class, 'getPossibleMembers']); 
        Route::post('/get_incompleted_tasks', [BoardController::class, 'getIncompletedTasks']); 
        Route::post('/set_admin_role', [BoardController::class, 'setAdminRole']); 
        Route::post('/remove_group_member', [BoardController::class, 'removeGroupMember']); 
        Route::post('/group_add_member', [BoardController::class, 'groupAddMember']); 
        Route::post('/get_unsigned_messages', [BoardController::class, 'getUnsignedUsers']);
        Route::post('/get_edit_user', [BoardController::class, 'getEditUser']);
        Route::post('/signature_upload_api', [BoardController::class, 'signFile']);
        Route::post('/save_user_signature', [BoardController::class, 'saveSignature']);
        Route::post('/leave_board', [BoardController::class, 'leaveBoard']);
        Route::post('/board_possible_users', [BoardController::class, 'board_possible_users']);
        Route::post('/get_review_text', [BoardController::class, 'get_review_text']);
        Route::post('/send_reconfirm_email', [BoardController::class, 'send_reconfirm_email']);
        Route::post('/get_remind_messages', [BoardController::class, 'getRemindMessage']);
        Route::post('/get_unchecked_messages', [BoardController::class, 'getUncheckedMessage']);
        Route::get('/shared_docs/{board_id}/{path}/{keyword}/{user_id}', [BoardController::class, 'cdnDocs']);

        Route::post('/get_file_list', [FileController::class, 'fetchFileList']); 



        // notification
        Route::post('/notification/get_list_api', [NotificationController::class, 'getNotify']); 
        Route::post('/notification/update_list_api', [NotificationController::class, 'updateNotify']); 

        // Admin Panel User:
        Route::get('/admin_control', [AdminAccountController::class, 'index']);
        Route::get('/get_user_list', [AdminAccountController::class, 'getUserList']);
        Route::post('/user_add', [AdminAccountController::class, 'addUser']);
        Route::post('/user_delete', [AdminAccountController::class, 'deleteUser']);
        Route::post('/user_edit', [AdminAccountController::class, 'editUser']);
        // Admin Panel Rule
        Route::get('/admin_account_control/get_rule_list', [AppRuleController::class, 'getRule']);
        Route::post('/admin_account_control/rule_add', [AppRuleController::class, 'addRule']);
        Route::post('/admin_account_control/rule_edit', [AppRuleController::class, 'editRule']);
        // Admin Panel Position
        Route::get('/admin_account_control/get_position_list', [AdminPositionController::class, 'getPosition']);
        Route::post('/admin_account_control/position_add', [AdminPositionController::class, 'addPosition']);
        Route::post('/admin_account_control/position_sort', [AdminPositionController::class, 'positionSort']);
        Route::post('/admin_account_control/position_edit', [AdminPositionController::class, 'editPosition']);
        Route::post('/admin_account_control/position_delete', [AdminPositionController::class, 'deletePosition']);
        // Admin Panel Office
        Route::post('/office_add', [AdminAccountController::class, 'addOffice']);
        Route::post('/office_edit', [AdminAccountController::class, 'editOffice']);
        Route::post('/office_delete', [AdminAccountController::class, 'deleteOffice']);
        // Admin Panel Work Group
        Route::post('/work_group_add', [AdminAccountController::class, 'workgroupAdd']);
        Route::post('/work_group_edit', [AdminAccountController::class, 'workgroupEdit']);
        Route::post('/work_group_delete', [AdminAccountController::class, 'workgroupDelete']);
        Route::post('/work_group_sort', [AdminAccountController::class, 'workgroupSort']);
        // Admin Panel Work
        Route::post('/get_admin_work', [AdminWorkController::class, 'getAllMessage']);



        //User
        Route::post('/user_delete_account', [UserController::class, 'deleteAccount']);
        Route::post('/user_generate_file_key', [UserController::class, 'generate_key']);
        Route::post('/user_icon_cropped_up_api', [UserController::class, 'croppedUp']);
        Route::post('/user_icon_create_api', [UserController::class, 'userIconCreate']); 
        Route::post('/profile_profile_edit_api', [UserController::class, 'profileEdit']); 
        Route::post('/profile_login_edit_api', [UserController::class, 'loginEdit'])->middleware('throttle:3,1'); 
        Route::post('/user_pass_change_api', [UserController::class, 'passChange']); // パスワード変更API
        Route::post('/profile_delete_api', [UserController::class, 'deleteEdit']);
        Route::post('/profile_get_user_tags', [UserController::class, 'getTags']);
        Route::post('/profile_get_search_tags', [UserController::class, 'getSearchTags']);
        Route::post('/profile_get_update_user', [UserController::class, 'profile_get_update_user']);
        Route::post('/profile_set_privacy', [UserController::class, 'setPrivacy']);
        Route::post('/profile_generate_new_code', [UserController::class, 'generateNewUserQrCode']);
        Route::post('/profile_set_color', [UserController::class, 'setColor']);
        Route::post('/set_language', [UserController::class, 'setLanguage']);
        Route::post('/get_user_claps', [UserController::class, 'getClaps']);
        Route::post('/user_file_upload', [UserController::class, 'userFileUpload']);
        Route::post('/user_delete_file', [UserController::class, 'userDeleteFile']);
        Route::post('/mov_up', [UserController::class, 'uploadMov']);
        Route::post('/mov_delete', [UserController::class, 'deleteMov']);
        Route::post('/save_user_signature', [UserController::class, 'saveSignature']);

        Route::post('/members_get_list', [MembersController::class, 'getList']);
        Route::post('/members_get_friends', [MembersController::class, 'getFriends']);
        Route::get('/members_createDefaultIcons', [MembersController::class, 'createIcons']);
        // Route::post('/members_chat_request', [MembersController::class, 'chatRequest']);
        Route::post('/members_join_request', [MembersController::class, 'joinRequest']);
        Route::post('/members_get_possible_member_list', [MembersController::class, 'getPossibleMemberList']);
        Route::post('/check_invite', [MembersController::class, 'checkInvite']);
        Route::post('/check_join', [MembersController::class, 'checkJoin']);
        Route::post('/set_member_link', [MembersController::class, 'toggleFriend']);
        Route::post('/respond_partner_request', [MembersController::class, 'respondPartnerRequest']);
        Route::post('/block_user', [MembersController::class, 'blockUser']);
        Route::post('/member_get_block_list', [MembersController::class, 'getBlockList']);
        
        // ->middleware('throttle:3,1');


        Route::post('/get_posts', [PostController::class, 'get_posts']);
        Route::post('/post_get_users', [PostController::class, 'post_get_users']);
        Route::post('/post_get_tags', [PostController::class, 'post_get_tags']);
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
        Route::post('/post_get_nice_users', [PostController::class, 'post_get_nice_users']);
        Route::post('/post_get_challenge_users', [PostController::class, 'post_get_challenge_users']);
        Route::get('/get_post_badge', [PostController::class, 'get_post_badge']);
        Route::patch('/update_post_badge', [PostController::class, 'update_post_badge']);
        Route::patch('/set_footer_view ', function(Request $request){
            $update = Auth::user()->update([
                "footer_view" => $request->value
            ]);
            return $update;
        }); 
        Route::post('/get_featured_tags', [PostController::class, 'get_featured_tags']);
        Route::post('/post_advanced_search', [PostController::class, 'post_advanced_search']);
        Route::post('/get_history', [PostController::class, 'get_history']);
        Route::post('/prepare_sharing_files', [PostController::class, 'prepare_sharing_files']);



        Route::post('/get_calendar_data', [CalendarController::class, 'get_calendar_data']);
        Route::post('/get_possible_facilities', [CalendarController::class, 'get_possible_facilities']);
        Route::post('/calendar_add_record', [CalendarController::class, 'calendar_add_record']);
        Route::post('/get_my_groups', [CalendarController::class, 'get_my_groups']);
        Route::post('/update_selected_calendar_members', [CalendarController::class, 'update_selected_calendar_members']);
        Route::post('/calendar_more_users', [CalendarController::class, 'calendar_more_users']);
        Route::post('/set_more_members', [CalendarController::class, 'set_more_members']);
        Route::post('/get_calendar_search', [CalendarController::class, 'get_calendar_search']);
        Route::post('/get_all_facilities', [CalendarController::class, 'get_all_facilities']);
        Route::post('/calendar_drop', [CalendarController::class, 'calendar_drop']);
        Route::post('/calendar_delete_record', [CalendarController::class, 'calendar_delete_record']);

        Route::post('/get_members_list', [MemberController::class, 'get_members_list']);

        Route::post('/get_kadai_list', [MemberController::class, 'get_kadai_list']);
        Route::post('/get_kadai_reviev', [MemberController::class, 'get_kadai_reviev']);
        Route::post('/update_kadai', [MemberController::class, 'update_kadai']);
        Route::post('/save_kadai_template', [MemberController::class, 'save_kadai_template']);
        Route::post('/get_kadai_template', [MemberController::class, 'get_kadai_template']);
        Route::post('/check_kadai_record', [MemberController::class, 'check_kadai_record']);
        Route::post('/delete_kadai_template', [MemberController::class, 'delete_kadai_template']);
        Route::post('/delete_applied_issue', [MemberController::class, 'delete_applied_issue']);
        Route::get('/get_kadai_themes', [MemberController::class, 'get_kadai_themes']);
        Route::post('/get_applied_issues', [MemberController::class, 'get_applied_issues']);

        Route::get('/proxy', [MemberController::class, 'proxy']);

    // });    

        Route::post('/get_work_data', [WorkController::class, 'getWorkData']);
        Route::post('/get_shift_data', [WorkController::class, 'getShiftData']);
        Route::post('/add_shift', [WorkController::class, 'shiftAdd']);
        Route::post('/get_work_group', [WorkController::class, 'getWorkGroup']);
        Route::post('/daily_report_add', [WorkController::class, 'dailyReportAdd']);
        Route::post('/save_time_card', [WorkController::class, 'saveTimeCard']);
        Route::post('/delete_time_card', [WorkController::class, 'deleteTimeCard']);
        Route::post('/get_attendance_data', [WorkController::class, 'getAttendanceData']);
        Route::post('/remand_time_card', [WorkController::class, 'remandTimeCard']);
        Route::post('/approve_time_card', [WorkController::class, 'approveTimeCard']);
        Route::post('/cancel_time_card', [WorkController::class, 'cancelTimeCard']);
        Route::post('/attendance_confirm', [WorkController::class, 'attendanceConfirm']);
        Route::post('/attendance_delete', [WorkController::class, 'attendanceDelete']);
        Route::post('/not_submitted', [WorkController::class, 'notSubmitted']);

        Route::post('/custom_field_data', [CustomfieldController::class, 'customFieldRecordListMessage']);
        Route::post('/today_weather', [CustomfieldController::class, 'getTodayWeather']);
        Route::post('/save_weather', [CustomfieldController::class, 'saveWeather']);
});