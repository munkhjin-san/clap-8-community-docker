<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\boardRecord;
use App\Models\messageFile;
use App\Models\User;
use App\Models\userDetail;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Pusher\Pusher;
class FileController extends Controller
{   


    public function fetchFileList(Request $request){ 
        $active_user = Auth::user(); 
        $validatedData = $request->validate([
            'board_id' => ['required'],
            'keyword'  => ['sometimes', 'nullable', 'string'],
        ]);

        $leavePeriod = userDetail::where('user_id', $active_user->id)
        ->whereNotNull('leave_start')
        ->whereNotNull('leave_end')
        ->first();
           
        $targetBoard = boardRecord::findOrFail($request->board_id);
        $usercheck = $targetBoard->board_to_users()->where('user_id','=', $active_user->id)->first();
        $timeLimit = $usercheck->created_at; 
        // $messageFrom = $targetBoard->message_from;     
        $time_condition = $timeLimit;
        $view_from = $usercheck->view_from;
        $keyword = mb_strtolower(trim((string) $request->input('keyword', '')));
        $allFiles = messageFile::where('board_id', $request->board_id)
        ->whereNull('original_file_id')
        ->whereHas('message_records')
        ->when($view_from, function ($query) use ($view_from) {
            $query->where('created_at', '>=', $view_from);
        })
        ->when($time_condition && !$view_from, function ($query) use ($timeLimit) {
            $query->where('created_at', '>=',  $timeLimit );
        })
        ->when($leavePeriod && ($targetBoard->private_flag != 3 || $targetBoard->private_flag != 1), function ($query) use ($leavePeriod) {
            $query->whereNotBetween('created_at', [$leavePeriod->leave_start, $leavePeriod->leave_end]);
        })
        ->when($keyword !== '', function ($q) use ($keyword) {
            $q->where(function ($qq) use ($keyword) {
                // name / extension
                $qq->whereRaw('name LIKE ?', ["%{$keyword}%"])
                ->orWhereRaw('extension LIKE ?', ["%{$keyword}%"]);

                // user name (exists)
                $qq->orWhereHas('user', function ($uq) use ($keyword) {
                    $uq->whereRaw('name LIKE ?', ["%{$keyword}%"]);
                });

                // "inactive user" virtual label (user is null)
                if (str_contains('非アクティブユーザー', $keyword) || str_contains($keyword, '非') || str_contains($keyword, 'アクティブ')) {
                    $qq->orWhereDoesntHave('user');
                }
            });
        })
        ->with(['user', 'unsignedUsers'])
        ->orderBy('created_at', 'desc')
        ->paginate(20);
        return response()->json($allFiles);
    }

}
