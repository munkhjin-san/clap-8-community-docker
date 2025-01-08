<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Cache;
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

  


  
    public function fileTransferAllExternal(Request $request){     
        if($request->user_id){
            $user = User::findOrFail($request->user_id);
            if($request->keyword == $user->file_key){
                try {
                    $filePath = $request->any;
                    return response()->file(storage_path('app/' . $filePath ));
                } catch (FileNotFoundException $exception) {
                    abort(404);
                }
            }else{
                abort(404);
            }
        }      
    }   
   
   
    public function fileTransferAll(Request $request){   
        try {     
            return response()->file(storage_path('app/' . $request->any));
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
    } 
    public function lessonFileTransfer(Request $request){   
        try {
            $root_path = storage_path('app');
            $filePath = $request->user_id . '/' . $request->path;
            $p1 = $root_path . '/lesson_files/' . $filePath;
            return response()->file($p1);
        } catch (FileNotFoundException $exception) {
            abort(404);
        }       

    }
    public function sharedThumbnail(Request $request){
       
        try {     
            $filePath = storage_path('app/shared_files/' . $request->board_id . '/' . $request->path);
            $mimeType = Storage::mimeType('shared_files/' . $request->board_id . '/' . $request->path);
            // return $filePath;
            if (Str::startsWith($mimeType, 'image/')) {
                $img = Image::read($filePath)->scaleDown(height: 35);
                return $this->image_response($img);
            } 
            
        } catch (FileNotFoundException $exception) {
            abort(404);
        }
    }
    private function image_response($img){
        return response($img->toWebp(), 200, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=2628000',
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 2628000),
        ]);
    }
    public function user_default_thumbnail($char, $size, $color = 'light'){      
        
        $regex = '/[А-Яа-яЁёөү]/u';
        $is_mn = preg_match($regex, $char);
        $font_path = $is_mn ? 'fonts/NotoSans-Bold.ttf' : 'fonts/Noto_Sans_CJK-Bold.otf';
        $bg = $color === 'light' ? '#000' : '#ddd';
        $text_color = $color === 'light' ? '#fff' : '#000';
        $resize = $size ? (int) $size : 30;
        $cacheKey = 'chat_image_' . md5($char.$color.$size);
        if (Cache::has($cacheKey)) {
            $cachedImage = Cache::get($cacheKey);
            if(!empty($cachedImage)){
                $img = Image::read($cachedImage);
                return $this->image_response($img);
            }            
        }
        $img = Image::create(200, 200)->fill($bg);

        $img->text($char, 100, 100, function ($font) use ($font_path, $text_color) {
            $font->file(resource_path($font_path));
            $font->size(130);
            $font->color($text_color);
            $font->align('center');
            $font->valign('middle');
            
        })->resize($resize, $resize);
        $imagedata = (string) $img->toWebp();     
        Cache::put($cacheKey, (string) $imagedata, 2628000);

        return $this->image_response($img);  
    }
    public function board_default_thumbnail($name, $size, $color){
        $boardname_no_space = preg_replace('/\s+/', '', $name);
        $bg = $color;
        $text_color = '#fff';
        $firstChar = mb_substr($boardname_no_space, 0, 3, "UTF-8"); 
        $img = Image::create(200, 200)->fill($bg);   
        $length = mb_strlen($boardname_no_space);       
        $regex = '/[А-Яа-яЁёөү]/u';
        $is_mn = preg_match($regex, $firstChar);
        $font_path = $is_mn ? 'fonts/NotoSans-Bold.ttf' : 'fonts/Noto_Sans_CJK-Bold.otf';
        $cacheKey = 'chat_image_' . md5($name.$color.$size);
        if (Cache::has($cacheKey)) {
            $cachedImage = Cache::get($cacheKey);
            if(!empty($cachedImage)){
                $img = Image::read($cachedImage);
                return $this->image_response($img);
            }            
        }
        $bucket = [
            [],
            [ ['y' => 100, 'size' => 100, 'text' => mb_substr($boardname_no_space, 0, 3, "UTF-8")]],
            [ ['y' => 100, 'size' => 80, 'text' => mb_substr($boardname_no_space, 0, 3, "UTF-8")]],
            [ ['y' => 100, 'size' => 60, 'text' => mb_substr($boardname_no_space, 0, 3, "UTF-8")]],
            [ ['y' => 70, 'size' => 60, 'text' => mb_substr($boardname_no_space, 0, 2, "UTF-8")],  ['y' => 130, 'size' => 60, 'text' => mb_substr($boardname_no_space, 2, 2, "UTF-8")]],
            [ ['y' => 75, 'size' => 50, 'text' => mb_substr($boardname_no_space, 0, 3, "UTF-8")], ['y' => 135, 'size' => 50, 'text' => mb_substr($boardname_no_space, 3, 2, "UTF-8")]],
            [ ['y' => 70, 'size' => 50, 'text' => mb_substr($boardname_no_space, 0, 3, "UTF-8")], ['y' => 130, 'size' => 50, 'text' => mb_substr($boardname_no_space, 3, 3, "UTF-8")]],           
        ];

        $index = $length >= 6 ? 6 : $length;        
        $pot = $bucket[$index];
        foreach($pot as $plate){
            $img->text($plate['text'], 100, $plate['y'], function ($font) use($font_path, $text_color, $plate) {
                $font->file(resource_path($font_path));
                $font->size($plate['size']);
                $font->color($text_color);
                $font->align('center');
                $font->valign('middle');                
            });
        }     
        $imagedata = (string) $img->toWebp();     
        Cache::put($cacheKey, (string) $imagedata, 2628000);
        return $this->image_response($img);
    }
}
