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
    public function sharedThumbnail(Request $request)
    {
        $boardId = (int) $request->board_id;
        $relativePath = ltrim($request->path, '/');

        // Original file (on storage)
        $originalRelPath = "shared_files/{$boardId}/{$relativePath}";
        
        if (!Storage::exists($originalRelPath)) {
            abort(404);
        }

        // Thumbnail path on storage
        $thumbDir  = "shared_files/{$boardId}/_thumbs";
        $thumbName = md5($relativePath . '|h=35') . '.webp';
        $thumbRelPath = "{$thumbDir}/{$thumbName}";

        // Generate thumbnail only if it does not exist yet
        if (!Storage::exists($thumbRelPath)) {
            $originalPath = Storage::path($originalRelPath);

            // read & resize ONCE
            $img = Image::read($originalPath)->scaleDown(height: 35);

            // store webp file in storage
            Storage::put($thumbRelPath, $img->toWebp(70)); // 70 = quality, tweak as you like
        }

        $thumbPath = Storage::path($thumbRelPath);

        return response()->file($thumbPath, [
            'Content-Type'  => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function image_response($img){
        return response($img->toWebp(), 200, );
    }
    public function user_icon_thumbnail($path, $size, $color = '000000')
    {
        $basePath = storage_path("app/profile_icon_migrated/{$path}_original.webp");

        if (!file_exists($basePath)) {
            $bg = '#'.ltrim($color, '#');
            $img = Image::create(200, 200)->fill($bg);
            $binary = (string) $img->toWebp(80);

            return response($binary, 200, [
                'Content-Type'  => 'image/webp',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        $thumbPath = storage_path("app/profile_icon_migrated/{$path}.webp");

        if (!file_exists($thumbPath)) {
            $img = Image::read($basePath)->coverDown(200, 200); // keep ratio, crop
            $img->save($thumbPath, 80, 'webp');
        }

        return response()->file($thumbPath, [
            'Content-Type'  => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }


    public function user_default_thumbnail($char, $size, $color = '#000')
    {
    
        $font_path = 'fonts/Noto_Sans_CJK-Bold.otf';

        $bg = $color;
        $text_color = '#fff';
        $resize = $size ? (int) $size : 30;

        $cacheKey = 'chat_image_7' . md5($char.$color.$resize);

        if ($cached = Cache::get($cacheKey)) {
            return response($cached, 200, [
                'Content-Type'  => 'image/webp',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        $img = Image::create($resize, $resize)->fill($bg);

        $img->text($char, $resize / 2, $resize / 2, function ($font) use ($font_path, $text_color, $resize) {
            $font->file(resource_path($font_path));
            $font->size((int)($resize * 0.65));
            $font->color($text_color);
            $font->align('center');
            $font->valign('middle');
        });

        $imagedata = (string) $img->toWebp(85);

        // use sane TTL: e.g. 30 days
        Cache::put($cacheKey, $imagedata, now()->addDays(30));

        return response($imagedata, 200, [
            'Content-Type'  => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
    /**
     * Calculate appropriate text color based on background luminance
     */
    private function getTextColor(string $hexColor): string
    {
        // Validate hex color
        if (!preg_match('/^#?[0-9A-Fa-f]{6}$/', $hexColor)) {
            return '#FFFFFF';
        }
        
        $hex = ltrim($hexColor, '#');
        
        // Extract RGB values
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Calculate relative luminance (W3C formula)
        $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
        
        // Return black for light backgrounds, white for dark
        return $luminance > 0.5 ? '#000000' : '#FFFFFF';
    }

    /**
     * Determine font path based on character set
     */
    private function getFontPath(string $text): string
    {
        $isCyrillic = preg_match('/[А-Яа-яЁёөү]/u', $text);
        
        return $isCyrillic 
            ? 'fonts/NotoSans-Bold.ttf' 
            : 'fonts/Noto_Sans_CJK-Bold.otf';
    }

    /**
     * Get text layout configuration based on text length
     */
    private function getTextLayout(string $text, int $length): array
    {
        $layouts = [
            1 => [['y' => 100, 'size' => 100, 'text' => mb_substr($text, 0, 1, 'UTF-8')]],
            2 => [['y' => 100, 'size' => 80, 'text' => mb_substr($text, 0, 2, 'UTF-8')]],
            3 => [['y' => 100, 'size' => 60, 'text' => mb_substr($text, 0, 3, 'UTF-8')]],
            4 => [
                ['y' => 70, 'size' => 60, 'text' => mb_substr($text, 0, 2, 'UTF-8')],
                ['y' => 130, 'size' => 60, 'text' => mb_substr($text, 2, 2, 'UTF-8')]
            ],
            5 => [
                ['y' => 75, 'size' => 50, 'text' => mb_substr($text, 0, 3, 'UTF-8')],
                ['y' => 135, 'size' => 50, 'text' => mb_substr($text, 3, 2, 'UTF-8')]
            ],
        ];
        
        // Default layout for 6+ characters
        $defaultLayout = [
            ['y' => 70, 'size' => 50, 'text' => mb_substr($text, 0, 3, 'UTF-8')],
            ['y' => 130, 'size' => 50, 'text' => mb_substr($text, 3, 3, 'UTF-8')]
        ];
        
        return $layouts[$length] ?? $defaultLayout;
    }

    /**
     * Create image response with proper headers
     */
    private function imageResponse(string $imageData): \Illuminate\Http\Response
    {
        return response($imageData, 200, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
    public function board_default_thumbnail($name, $size, $color)
    {
        $cacheKey = 'chat_image_' . md5($name . $color . $size);
        
        // Return cached image if exists
        if ($cachedImage = Cache::get($cacheKey)) {
            return $this->imageResponse($cachedImage);
        }

        // Prepare data
        $boardnameNoSpace = preg_replace('/\s+/', '', $name);
        $textColor = $this->getTextColor($color);
        $length = mb_strlen($boardnameNoSpace);
        
        // Use the configured ImageManager from container
        $img = Image::create(200, 200)->fill($color);
        
        // Determine font based on characters
        $firstChars = mb_substr($boardnameNoSpace, 0, 3, 'UTF-8');
        $fontPath = $this->getFontPath($firstChars);
        
        // Get text layout configuration
        $textLayout = $this->getTextLayout($boardnameNoSpace, $length);
        
        // Draw text on image
        foreach ($textLayout as $textConfig) {
            $img->text(
                $textConfig['text'],
                100,
                $textConfig['y'],
                function ($font) use ($fontPath, $textColor, $textConfig) {
                    $font->filename(resource_path($fontPath));
                    $font->size($textConfig['size']);
                    $font->color($textColor);
                    $font->align('center');
                    $font->valign('middle');
                }
            );
        }
        
        // Encode to WebP and cache
        $imageData = (string) $img->toWebp();
        Cache::put($cacheKey, $imageData, now()->addMonth());
        
        return $this->imageResponse($imageData);
    }
    public function board_icon_thumbnail($path, $size = 45, $color = 'light'){ 


        $cacheKey = 'chat_image_custom' . md5($path . $size);
        
        // Return cached image if exists
        if ($cachedImage = Cache::get($cacheKey)) {
            return $this->imageResponse($cachedImage);
        }
        $path = storage_path('app/board_icon_migrated/' . $path . '.webp' );
        if (!file_exists($path)) {
            $bg = $color === 'light' ? '#000' : '#ddd';
            $blank = Image::create(200, 200)->fill($bg)->resize($size, $size);
            return $this->imageResponse($blank);
        }
        $img = Image::read($path);
        $imageData = (string) $img->toWebp();
        Cache::put($cacheKey, $imageData, now()->addMonth());
        return $this->imageResponse($imageData);
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
    public function pdf_reader(Request $request, $path){
        // dd($path);
        $base = storage_path('app/pdfjs-5.4.449-dist'); // <-- your new location
        $full = realpath($base . DIRECTORY_SEPARATOR . $path);

        if ($full === false || !str_starts_with($full, realpath($base)) || !is_file($full)) {
            abort(404);
        }

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));

        $mime = match ($ext) {
            'mjs', 'js'   => 'application/javascript',
            'wasm'        => 'application/wasm',
            'css'         => 'text/css; charset=utf-8',
            'html', 'htm' => 'text/html; charset=utf-8',
            'json', 'map' => 'application/json; charset=utf-8',
            'svg'         => 'image/svg+xml',
            'png'         => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'woff2'       => 'font/woff2',
            default       => 'application/octet-stream',
        };

        return response()->make(file_get_contents($full), 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($full).'"',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
