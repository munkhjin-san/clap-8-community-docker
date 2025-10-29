<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
        return response($img->toWebp(), 200, );
    }
    public function user_icon_thumbnail($path, $size, $color = '000000'){   
        if($size == 'original'){
            $path = storage_path('app/profile_icon_migrated/' . $path . '_original.webp' );
            if (!file_exists($path)) {
                $bg = $color;
                $blank = Image::create(200, 200)->fill($bg)->resize(200, 200);
                return $this->image_response($blank);  
            }
            $img = Image::read($path);
            return $this->image_response($img);  
        }else{
            $path = storage_path('app/profile_icon_migrated/' . $path . '.webp' );
            if (!file_exists($path)) {
                $bg = $color;
                $blank = Image::create(200, 200)->fill($bg)->resize($size, $size);
                return $this->image_response($blank);  
            }
            $img = Image::read($path)->resize($size, $size);
            return $this->image_response($img);          
        }        
    }
    public function user_default_thumbnail($char, $size, $color = '#000'){      
        
        $regex = '/[А-Яа-яЁёөү]/u';
        $is_mn = preg_match($regex, $char);
        $font_path = $is_mn ? 'fonts/NotoSans-Bold.ttf' : 'fonts/Noto_Sans_CJK-Bold.otf';
        $bg = $color;
        $text_color = '#fff';
        $resize = $size ? (int) $size : 30;
        $cacheKey = 'chat_image_7' . md5($char.$color.$size);
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
    private function pickTextColor(string $hexColor) {
        //check valid hex color
        if (!preg_match('/^#?[0-9A-Fa-f]{6}$/', $hexColor)) {
            return '#FFFFFF'; // Default to white if invalid
        }
        $hex = str_replace('#', '', $hexColor);

        // Get RGB values from the hex code
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Calculate luminance using the W3C formula
        $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;

        // Use a threshold to decide on the text color
        // A common threshold is 0.5, but you can adjust it
        if ($luminance > 0.5) {
            return '#000000'; // Black text for light backgrounds
        } else {
            return '#FFFFFF'; // White text for dark backgrounds
        }
    }
    private function normalizeHex(string $color): ?string
    {
        $color = trim($color);
        if ($color === '') return null;

        // add # if missing
        if ($color[0] !== '#') {
            $color = '#' . $color;
        }

        // expand shorthand #abc → #aabbcc
        if (preg_match('/^#([0-9A-Fa-f]{3})$/', $color, $m)) {
            return '#' . $m[1][0] . $m[1][0]
                    . $m[1][1] . $m[1][1]
                    . $m[1][2] . $m[1][2];
        }

        // accept only valid 6-digit hex
        if (preg_match('/^#([0-9A-Fa-f]{6})$/', $color)) {
            return strtoupper($color);
        }

        return null;
    }
    private function layoutBucket(string $text, int $length, int $size): array
    {
        // normalize reference size to 200 (original scale)
        $scale = $size / 200;

        // baseline buckets roughly matching your previous ones
        $bucket = [
            [], // index 0 unused
            [ ['y' => 100, 'size' => 100, 'text' => mb_substr($text, 0, 3, 'UTF-8')] ],
            [ ['y' => 100, 'size' => 80,  'text' => mb_substr($text, 0, 3, 'UTF-8')] ],
            [ ['y' => 100, 'size' => 60,  'text' => mb_substr($text, 0, 3, 'UTF-8')] ],
            [ 
                ['y' => 70,  'size' => 60, 'text' => mb_substr($text, 0, 2, 'UTF-8')],
                ['y' => 130, 'size' => 60, 'text' => mb_substr($text, 2, 2, 'UTF-8')],
            ],
            [ 
                ['y' => 75,  'size' => 50, 'text' => mb_substr($text, 0, 3, 'UTF-8')],
                ['y' => 135, 'size' => 50, 'text' => mb_substr($text, 3, 2, 'UTF-8')],
            ],
            [ 
                ['y' => 70,  'size' => 50, 'text' => mb_substr($text, 0, 3, 'UTF-8')],
                ['y' => 130, 'size' => 50, 'text' => mb_substr($text, 3, 3, 'UTF-8')],
            ],
        ];

        $index = $length >= 6 ? 6 : $length;
        $layout = $bucket[$index];

        // scale y and font size proportionally to target image size
        foreach ($layout as &$item) {
            $item['y'] = (int) round($item['y'] * $scale);
            $item['size'] = (int) round($item['size'] * $scale);
        }

        return $layout;
    }
    public function board_default_thumbnail(string $name, int $size, string $color)
    {
        // clamp and normalize size
        $s = max(16, min(512, (int)$size));

        // normalize bg color (#RRGGBB) and pick text color
        $bg = $this->normalizeHex($color) ?? '#000000';
        $textColor = $this->pickTextColor($bg);

        // sanitize name slices only once
        $noSpace = preg_replace('/\s+/', '', (string)$name) ?? '';
        $len = mb_strlen($noSpace, 'UTF-8');
        $first3 = mb_substr($noSpace, 0, 3, 'UTF-8');
        $isMn = (bool) preg_match('/[А-Яа-яЁёөү]/u', $first3);
        $fontPath = resource_path($isMn ? 'fonts/NotoSans-Bold.ttf'
                                        : 'fonts/Noto_Sans_CJK-Bold.otf');

        // 2) Cache on filesystem by content hash, not RAM
        $hash = md5($noSpace.$bg.$textColor.$s.filemtime($fontPath));
        $cacheDir = storage_path('app/board_title_cache');
        $cached = "{$cacheDir}/{$hash}.webp";

        if (is_file($cached)) {
            return response()->file($cached, [
                'Cache-Control' => 'public, max-age=31536000, immutable'
            ]);
        }

        @mkdir($cacheDir, 0775, true);

        // 3) Render at requested size, not 200x200
        $img = Image::create($s, $s)->fill($bg);

        // choose layout bucket once
        $bucket = $this->layoutBucket($noSpace, $len, $s);
        foreach ($bucket as $plate) {
            $img->text($plate['text'], (int)($s/2), $plate['y'], function ($font) use ($fontPath, $textColor, $plate) {
                $font->file($fontPath);
                $font->size($plate['size']);
                $font->color($textColor);
                $font->align('center');
                $font->valign('middle');
            });
        }

        // 4) Save to disk and serve as a file
        $img->save($cached, 85, 'webp');

        return response()->file($cached, [
            'Cache-Control' => 'public, max-age=31536000, immutable'
        ]);
    }
    public function board_icon_thumbnail($path, $size = 45, $color = 'light'){ 
        $path = storage_path('app/board_icon_migrated/' . $path . '.webp' );
        if (!file_exists($path)) {
            $bg = $color === 'light' ? '#000' : '#ddd';
            $blank = Image::create(200, 200)->fill($bg)->resize($size, $size);
            return $this->image_response($blank);
        }
        $img = Image::read($path)->resize($size, $size);
        return $this->image_response($img);
    }
    public function kintone_file(Request $request){
        $request->validate([
            'key' => 'required',
            'name' => 'required',
        ]);
        $file_key = $request->key;
        $file_name = $request->name;
        $user_name = config('app.kintone_user_name');
        $password = config('app.kintone_password');
        $string = $user_name. ':'. $password;
        $x_token = base64_encode($string);
        $url = "https://glowd-hldgs.cybozu.com/k/v1/file.json";

        $response = Http::withHeaders([
            'Authorization' => 'Basic', 
            'X-Cybozu-Authorization' => $x_token,
        ])->get($url, [
            'fileKey' => $file_key
        ]);
        $fileContent = $response->body();
        // dd($response->body());
        return response()->streamDownload(function () use ($fileContent) {
            echo $fileContent;
        }, $file_name);
        
    }
}
