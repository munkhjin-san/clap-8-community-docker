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
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\SocialLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\AutoJobController;
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
//Social Login
Route::get('auth/facebook', [SocialLoginController::class, 'redirectToFacebook']);
Route::get('auth/facebook/callback', [SocialLoginController::class, 'facebookSignin']);
Route::get('auth/google', [SocialLoginController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [SocialLoginController::class, 'googleSignin']);
Route::get('auth/twitter', [SocialLoginController::class, 'redirectToTwitter']);
Route::get('auth/twitter/callback', [SocialLoginController::class, 'twitterSignin']);
Route::get('support/docs/{lang}/privacy-policy', function ($lang) {    
    return view('privacy.' . $lang . '.privacy');
});
Route::get('support/docs/{lang}/terms-of-service', function ($lang) {    
    return view('terms.' . $lang . '.terms');
});
Route::get('support/docs/{lang}/data-deletion', function ($lang) {    
    return view('data-deletion.' . $lang . '.dataDeletion');
});
Route::view('/auth', 'auth.login')->name('auth')->middleware('guest');
Route::view('/auth/{lang}/terms-of-service', 'auth.login');
Route::view('/auth/{lang}/privacy-policy', 'auth.login');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/register/complete', [RegisterController::class, "register"])->name('register.complete')->middleware('guest');
Route::post('/register', [RegisterController::class, "guestregister"])->name('register.post')->middleware('guest');

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('forgot.password');
Route::get('/phone/verify', function () {
    return view('auth.verify_phone_number');
})->name('verify.phone');
Route::post('/phone/verification', [PhoneVerificationController::class, 'verify'])->name('verification.phone');
// Route::post('/phone/verification', [PhoneVerificationController::class, 'verifyPhone'])->name('verification.phone');
Route::post('/phone/send-code-again', [PhoneVerificationController::class, 'sendCodeAgain'])->name('phone.sendCodeAgain');
Route::post('/password/reset/phone', [ResetPasswordController::class, 'sendResetLinkPhone'])->name('password.send.phone');
Route::get('/password/reset/phone/token/{token}', [ResetPasswordController::class, 'showResetFormPhone'])->name('password.reset.phone');
Route::post('/password/reset/phone/reset', [ResetPasswordController::class, 'resetPasswordPhone'])->name('password.update.phone');

Route::get('/health', function () {
    return response('OK', 200)->header('Content-Type', 'text/plain');
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\RedirectIfAuthenticated::class]);

    Route::get('/invite', [MembersController::class, 'inviteToGuest']);
    Route::get('/join', [MembersController::class, 'joinToChat']);

Route::post('/report_send', [ContentController::class, 'reportSend']);
Route::group(["middleware"=>"auth"],function(){
    $id = Auth::id();
    // Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
    // Route::get('/email/verify/{id}/{hash}', [VerificationController::class ,'verify'])->name('verification.verify')->middleware(['signed']);
    // Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
    Route::get('request_private_chat/{id}',[MembersController::class, 'chatRequest']);
    Route::get('/profile/{id}',  [BoardController::class, "index"]);
    Route::get('/profile/{id}/{settings}',  [BoardController::class, "index"]);
    // Route::get('/profile', [UserController::class, 'index']);
    // Route::get('/invite', [MembersController::class, 'inviteToMember']);
    // Route::group(['middleware' => ['verified.user']], function() {
        Route::get('/' ,function () {
            {return Redirect::route('board');}
        });
        Route::get('/chat', [BoardController::class, "index"])->name('board');
        Route::get('/chat/{id}', [BoardController::class, "index"])->name('board_room');
        Route::get('/chat/{id}/{app_name}/{folder_id}' ,function ($id) {
            return redirect("/chat/{$id}");
        });
        Route::get('/chat/{id}/{app_name}' ,function ($id) {
            return redirect("/chat/{$id}");
        });
        Route::get('/home',function () {
            {return Redirect::route('board');}
        });
        // Route::get('/members', [BoardController::class, "index"]);
        // Route::get('/knowledge', [BoardController::class, "index"]);
        // Route::get('/nice', [BoardController::class, "index"]);
        // Route::get('/challenge', [BoardController::class, "index"]);

        Route::get('/{name}/{path?}',[BoardController::class, "index"])->where('name', '(challenge|knowledge|nice|members|calendar|work)');
        
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
        // Route::get('/{app_name}/managed_files/{board_id}/{path}', [FileController::class, 'cdnExtract']);
        // Route::get('/{app_name}/managed_files/{board_id}/{sub_folder}/{path}', [FileController::class, 'cdnSubExtract']);

        Route::get('/managed_files/{board_id}/{path}', [ContentController::class, 'managedFileTransfer']);
        Route::get('/managed_files/{board_id}/{sub_folder}/{path}', [ContentController::class, 'managedFileThumbTransfer']);

        Route::get('/post_files/{path}', [ContentController::class, 'postFileTransfer']);
        
        // Board
        Route::post('/chat_list', [BoardController::class, 'getAllMessage']); // 一覧表示API
        Route::post('/chat_create', [BoardController::class, 'postAddMessage']); // 作成API
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
        Route::post('/send_reaction_api', [BoardController::class, 'sendReaction']); 
        Route::post('/add_task_api', [BoardController::class, 'addTask']); 
        Route::post('/message_search', [BoardController::class, 'messageSearch']);
        Route::post('/get_target_message', [BoardController::class, 'getTargetMessage']); 
        Route::post('/get_bottom_messages', [BoardController::class, 'getAppend']); 
        Route::post('/get_instant_user', [BoardController::class, 'getInstantUser']); 
        Route::post('/add_memo', [BoardController::class, 'addMemo']); 
        Route::post('/edit_memo', [BoardController::class, 'editMemo']);     
        Route::post('/delete_memo', [BoardController::class, 'deleteMemo']); 
        // Route::post('/update_remember', [BoardController::class, 'updateRemember']); 
        Route::post('/get_all_members', [BoardController::class, 'getPossibleMembers']); 
        Route::post('/get_incompleted_tasks', [BoardController::class, 'getIncompletedTasks']); 
        Route::post('/respond_invite_request', [BoardController::class, 'respondInviteRequest']); 
        Route::post('/respond_join_request', [BoardController::class, 'respondJoinRequest']); 
        Route::post('/set_admin_role', [BoardController::class, 'setAdminRole']); 
        Route::post('/remove_group_member', [BoardController::class, 'removeGroupMember']); 
        Route::post('/group_add_member', [BoardController::class, 'groupAddMember']); 
        Route::post('/get_unsigned_messages', [BoardController::class, 'getUnsignedUsers']);
        Route::post('/get_edit_user', [BoardController::class, 'getEditUser']);
        Route::post('/signature_upload_api', [BoardController::class, 'signFile']);
        Route::post('/save_user_signature', [BoardController::class, 'saveSignature']);
        Route::post('/leave_board', [BoardController::class, 'leaveBoard']);
        Route::post('/cancel_join_request', [BoardController::class, 'cancelJoinRequest']);
        // file
        // Route::post('/file_get_file_list', [FileController::class, 'getFileList']); 
        // Route::post('/file_delete_folder', [FileController::class, 'deleteFolder']); 
        // Route::post('/file_delete_file', [FileController::class, 'deleteFile']);
        // Route::post('/file_create_new_folder', [FileController::class, 'createNewFolder']); 
        // Route::post('/file_move_to_folder', [FileController::class, 'moveToFolder']);
        // Route::post('/file_copy_paste_items', [FileController::class, 'copyPasteItems']);
        // Route::post('/file_move_paste_items', [FileController::class, 'movePasteItems']);
        // Route::post('/file_upload_new_file', [FileController::class, 'uploadNewFile']);
        // Route::post('/file_edit_folder', [FileController::class, 'editFolder']);
        // Route::post('/file_restore_items', [FileController::class, 'restoreItems']);
        // Route::post('/file_get_folders', [FileController::class, 'folderStructure']);
        // Route::post('/file_get_quota', [FileController::class, 'getQuota']);
        // Route::post('/file_import_from_message', [FileController::class, 'importFromBoard']);
        // Route::post('/file_get_search_result', [FileController::class, 'getSearch']);
        // Route::get('/file_download_request', [FileController::class, 'downloadRequest']);
        // Route::post('/file_download_managed_files', [FileController::class, 'downloadManagedFiles']);
        // Route::post('/file_remove_temp_path', [FileController::class, 'removeTempPath']);

        Route::post('/get_file_list', [FileController::class, 'fetchFileList']); 



        // notification
        Route::post('/notification/get_list_api', [NotificationController::class, 'getNotify']); 
        Route::post('/notification/update_list_api', [NotificationController::class, 'updateNotify']); 

        // Admin Panel User:
        Route::get('/admin_account_control', [AdminAccountController::class, 'index']);
        Route::get('/admin_account_control/get_user_list', [AdminAccountController::class, 'getUser']);
        Route::post('/admin_account_control/user_add', [AdminAccountController::class, 'addUser']);
        Route::post('/admin_account_control/user_delete', [AdminAccountController::class, 'deleteUser']);
        Route::post('/admin_account_control/user_edit', [AdminAccountController::class, 'editUser']);
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
        Route::get('/admin_account_control/get_office_list', [AdminOfficeController::class, 'getOffice']);
        Route::post('/admin_account_control/office_add', [AdminOfficeController::class, 'addOffice']);
        Route::post('/admin_account_control/office_edit', [AdminOfficeController::class, 'editOffice']);
        // Admin Panel Work Group
        Route::get('/admin_account_control/get_work_group', [AdminWorkGroupController::class, 'getWorkGroup']);
        Route::post('/admin_account_control/work_group_add', [AdminWorkGroupController::class, 'addWorkGroup']);
        Route::post('/admin_account_control/work_group_edit', [AdminWorkGroupController::class, 'editWorkGroup']);
        Route::post('/admin_account_control/work_group_sort', [AdminWorkGroupController::class, 'workgroupSort']);




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
        Route::post('/profile_get_update_user', [UserController::class, 'getUpdateUser']);
        Route::post('/profile_set_privacy', [UserController::class, 'setPrivacy']);
        Route::post('/profile_generate_new_code', [UserController::class, 'generateNewUserQrCode']);
        Route::post('/profile_set_color', [UserController::class, 'setColor']);
        Route::post('/set_language', [UserController::class, 'setLanguage']);

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



        Route::post('/get_calendar_data', [CalendarController::class, 'get_calendar_data']);
        Route::post('/get_possible_facilities', [CalendarController::class, 'get_possible_facilities']);
        Route::post('/calendar_add_record', [CalendarController::class, 'calendar_add_record']);



    // });    

        Route::post('/get_work_data', [WorkController::class, 'getWorkData']);
        Route::post('/get_shift_data', [WorkController::class, 'getShiftData']);
        Route::post('/add_shift', [WorkController::class, 'shiftAdd']);
        Route::get('/get_work_group', [WorkController::class, 'getWorkGroup']);
        Route::post('/daily_report_add', [WorkController::class, 'dailyReportAdd']);
        Route::post('/custom_field_data', [CustomfieldController::class, 'customFieldRecordListMessage']);
        Route::post('/save_time_card', [WorkController::class, 'saveTimeCard']);
        Route::post('/delete_time_card', [WorkController::class, 'deleteTimeCard']);
        Route::post('/get_attendance_data', [WorkController::class, 'getAttendanceData']);
        Route::post('/remand_time_card', [WorkController::class, 'remandTimeCard']);
        Route::post('/approve_time_card', [WorkController::class, 'approveTimeCard']);
        Route::post('/cancel_time_card', [WorkController::class, 'cancelTimeCard']);
        Route::post('/attendance_confirm', [WorkController::class, 'attendanceConfirm']);
});