<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File; 
use Intervention\Image\Facades\Image;
use App\Models\User;
use App\Models\Report;
use App\Mail\ReportFeedBack;
use App\Mail\ReportToAdmin;
use Illuminate\Support\Facades\Mail;

class ContentController extends Controller
{
    public function iconTransferApi(Request $request){   
        try {       
            // $filePath = $request->which .'/' . $request->path;
            $exists = Storage::disk('local')->exists($request->which .'/' . $request->path);
            if($exists){            
                $fileContents = Storage::disk('local')->get($request->which .'/' . $request->path);            
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $contentType = finfo_buffer($finfo, $fileContents);
                finfo_close($finfo);
                return response($fileContents)->header('Content-Type', $contentType);
            }else{
                return response()->file(public_path('images/backup.png'));
            }
            
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
    }
    public function iconTransfer(Request $request){   
        try {       
            // $filePath = $request->which .'/' . $request->path;
            $exists = Storage::disk('local')->exists($request->which .'/' . $request->path);
            if($exists){            
                $fileContents = Storage::disk('local')->get($request->which .'/' . $request->path);            
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $contentType = finfo_buffer($finfo, $fileContents);
                finfo_close($finfo);
                return response($fileContents)->header('Content-Type', $contentType);
            }else{
                return response()->file(public_path('images/backup.svg'));
            }
            
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
    }
    public function qrTransfer(Request $request){
        try {
            $filePath = $request->token .'_' . $request->id;

            // if (!Storage::disk('local')->exists('user_qr_code/' . $filePath)) {
            //     if (!Storage::disk('local')->exists('user_qr_code')) {
            //         Storage::disk('local')->makeDirectory('user_qr_code');
            //     }
            //     $client = new \GuzzleHttp\Client();        
            //     $qrCodeUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($url = url('/?external=true&token=' . $request->token . '&id=' . $request->id));           
            //     $response = $client->get($qrCodeUrl);    
            //     Storage::disk('local')->put('user_qr_code/'.$filePath, $response->getBody());    
            // }
            $fileContents = Storage::disk('local')->get('user_qr_code/' . $filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);
            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
    }    
    public function chatQrTransfer(Request $request){
        try {
            $filePath = $request->token .'_' . $request->id;
            $fileContents = Storage::disk('local')->get('board_qr_code/' . $filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);
            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
    }
    public function sharedFileTransfer(Request $request){     
        

        try {
            $filePath = $request->board_id .'/' . $request->path;
            $fileContents = Storage::disk('local')->get('shared_files/' . $filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);
            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
        

    }
    public function sharedFileThumbTransfer(Request $request){     
        

        try {
            $filePath = $request->board_id .'/thumbs/' . $request->path;
            $fileContents = Storage::disk('local')->get('message_files/' . $filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);
            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
        

    }
    public function managedFileTransfer(Request $request){     
        

        try {
            
            

            $filePath = $request->board_id .'/' . $request->path;
            $fileContents = Storage::disk('local')->get('managed_files/' . $filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);
            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
        

    }
    public function managedFileThumbTransfer(Request $request){     
        

        try {
            
            

            $filePath = $request->board_id . '/' . $request->sub_folder .'/' . $request->path;
            $fileContents = Storage::disk('local')->get('managed_files/' . $filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);
            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
        

    }
    public function tempUploadFile(Request $request){     
       
        try {
            $p1 = storage_path('app/temp_upload/' . $request->path);  
            return response()->file($p1);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
    }     
    public function docTransfer(Request $request){     
        if($request->user_id){
            $user = User::findOrFail($request->user_id);
            if($request->keyword == $user->file_key){
                try {
                    $filePath = $request->board_id .'/' . $request->path;
                    // $fileContents = Storage::disk('local')->get('shared_files/' . $filePath);
                    // $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    // $contentType = finfo_buffer($finfo, $fileContents);
                    // finfo_close($finfo);
                    // return response($fileContents)->header('Content-Type', $contentType);
                    return response()->file(storage_path('app/shared_files/' . $filePath ));
                } catch (FileNotFoundException $exception) {
                    abort(404);
                }
            }else{
                abort(404);
            }
        }
        
        

    }   
    public function cdnExtractDocsPost(Request $request){
        if($request->user_id){
            $user = User::findOrFail($request->user_id);
            if($request->keyword == $user->file_key){
                try {
                    $filePath = $request->sub_folder . '/' . $request->path;
                    return response()->file(storage_path('app/' . $filePath ));
                } catch (FileNotFoundException $exception) {
                    abort(404);
                }
            }else{
                abort(404);
            }
        }
    }
    public function getSignature(Request $request){   
        try {       
            $fileContents = Storage::disk('local')->get('user_signature/' . $request->path);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);

            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
    }
    public function reportSend (Request $request){
        $params = json_decode($request->params); 
        // return response()->json($params->id);
        $report = new Report;
        if($params->id){
            $report->user_id = $params->id;
        }
        
        $report->title = $params->title;
        $report->description = $params->description;
        $report->email = $params->email;
        $report->save();
        $hasFile = 'なし';
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $hasFile = 'あり';
            foreach($request->file('files') as $file ){
                // return response()->json('got_File');
                $file_extension = $file->getClientOriginalExtension();
                $p = 'report_files/case_' . $report->id;

                Storage::disk('local')->makeDirectory($p);
                $unique_path = uniqid('', true);
                
                $set_path = $unique_path . '.' .$file_extension;
                Storage::disk('local')->putFileAs(
                    $p , $file, $set_path
                );               
            }
            
        }
        Mail::to($params->email)
        ->send(new ReportFeedBack(
            $report->id, 
            $report->title, 
            $report->description, 
            $params->language, 
        ));
        // Mail::to('system_development@glowd.co.jp')
        // ->send(new ReportToAdmin(
        //     $report->id, 
        //     $report->title, 
        //     $report->description, 
        //     $hasFile
        // ));


        return response()->json($params);

        
    }
    public function postFileTransfer(Request $request){     
        

        try {           
            

            $filePath = $request->path;
            $fileContents = Storage::disk('local')->get('post_files/' . $filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);
            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }      
        

    }
    public function calendarFileTransfer(Request $request){     
        

        try {           
            

            $filePath = $request->path;
            $fileContents = Storage::disk('local')->get('calendar_files/' . $filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);
            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }      
        

    }
    
    public function userFileTransfer(Request $request){     
        

        try {     
            $root_path = storage_path('app');
            $filePath = $request->user_id . '/' . $request->path;
            $p1 = $root_path . '/user_album/' . $filePath;
            return response()->file($p1);
            $fileContents = Storage::disk('local')->get('user_album/' . $filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);
            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }      
        

    }   
    public function noticeTempFileTransfer(Request $request){     
        

        try {
            $filePath = $request->path;
            $fileContents = Storage::disk('local')->get('notice_temp/' . $filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);
            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
        

    }
    public function noticeFileTransfer(Request $request){     
        

        try {
            $filePath = $request->path;
            $fileContents = Storage::disk('local')->get('notice_files/' . $filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);
            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
        

    }
    public function lessonFileTransfer(Request $request){     
        

        try {
            $filePath = $request->path;
            $fileContents = Storage::disk('local')->get('lesson_files/' . $filePath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_buffer($finfo, $fileContents);
            finfo_close($finfo);
            return response($fileContents)->header('Content-Type', $contentType);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
        

    }
    public function embedded_video(Request $request){
        return '<img src="/lesson_files/65a63399c6b75.webp"/>';
    }

}
