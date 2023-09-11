<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Socialite;
use Auth;
use Exception;
use Illuminate\Support\Facades\Storage;
use App\Models\Icons;
use App\Models\boardRecord;
use App\Models\boardToUser;
use Intervention\Image\Facades\Image;
use App\Services\SharedService;
use App\Models\userDetail;

class SocialLoginController extends Controller
{
    public function __construct(SharedService $sharedService)
    {
        $this->sharedService = $sharedService;
    }

    public function redirectToFacebook(Request $request)
    {
        if($request->intended){
            session(['url.intended' => $request->intended]);

        }
        return Socialite::driver('facebook')->redirect();
    }
    public function facebookSignin()
    {
        try {
    
            $user = Socialite::driver('facebook')->user();
            $u_id = $user->getId();
            $existUser = User::where('social_login', $u_id)->first();
            $profile_url = $user->avatar;

            if($existUser){
                Auth::login($existUser);
                return redirect()->intended(session('url.intended', '/'));
            }else{
                $createUser = new User;
                $createUser->name = $user->name;
                $createUser->is_public = 1;
                $createUser->login = intval(strtotime("now"));
                $createUser->social_login = $u_id;
                if($user->getEmail()){
                    $createUser->email = $user->getEmail();
                }
                $createUser->password = Hash::make('john123');
                $createUser->email_verified_at = date('Y-m-d H:i:s');
                $createUser->save();
                $this->createProfileIcon($createUser, $profile_url);
                Auth::login($createUser);
                return redirect()->intended(session('url.intended', '/'));
            }
    
        } catch (Exception $exception) {
            return redirect('/auth');
        }
    }
    public function redirectToGoogle(Request $request)
    {
        if($request->intended){
            session(['url.intended' => $request->intended]);

        }
        return Socialite::driver('google')->redirect();
    }

    public function googleSignin()
    {
        try {
        
            $user = Socialite::driver('google')->user();
            $u_id = $user->getId();
            $existUser = User::where('social_login', $u_id)->first();
            $profile_url = $user->avatar;

            if($existUser){
                Auth::login($existUser);
                return redirect()->intended(session('url.intended', '/'));
            }else{
                $createUser = new User;
                $createUser->name = $user->name;
                $createUser->is_public = 1;
                $createUser->login = intval(strtotime("now"));
                $createUser->social_login = $u_id;
                if($user->getEmail()){
                    $createUser->email = $user->getEmail();
                }
                $createUser->password = Hash::make('john123');
                $createUser->email_verified_at = date('Y-m-d H:i:s');
                $createUser->save();
                $this->createProfileIcon($createUser, $profile_url);
                Auth::login($createUser);
                return redirect()->intended(session('url.intended', '/'));
                
            }
    
        } catch (Exception $exception) {
            return redirect('/auth');
        }
    }
    public function redirectToTwitter(Request $request)
    {
        if($request->intended){
            session(['url.intended' => $request->intended]);
        }
        return Socialite::driver('twitter')->redirect();
    }

    public function twitterSignin()
    {
        try {
    
            $user = Socialite::driver('twitter')->user();
            $u_id = $user->getId();
            $existUser = User::where('social_login', $u_id)->first();
            $profile_url = $user->attributes['avatar_original'];

            if($existUser){
                Auth::login($existUser);
                return redirect()->intended(session('url.intended', '/'));
            }else{
                $createUser = new User;
                $createUser->name = $user->name;
                $createUser->social_login = $u_id;
                $createUser->is_public = 1;
                $createUser->login = intval(strtotime("now"));
                if($user->getEmail()){
                    $createUser->email = $user->getEmail();
                }
                $createUser->password = Hash::make('john123');
                $createUser->email_verified_at = date('Y-m-d H:i:s');
                $createUser->save();
                $this->createProfileIcon($createUser, $profile_url);
                Auth::login($createUser);
                return redirect()->intended(session('url.intended', '/'));
            }
    
        } catch (Exception $exception) {
            return redirect('/auth');
        }
    }

    public function createProfileIcon($user, $profile_url){
        $userdetail = new userDetail;
        $userdetail->user_id = $user->id;
        $userdetail->save();

        $board = new boardRecord;
        $board->user_id = $user->id;
        $board->title = 'My chat';
        $board->last_activity = now();
        $board->private_flag = 3;
        $board->save();

        $self = new boardToUser;
        $self->record_id = $board->id;
        $self->user_id = $user->id;
        $self->invited_by = $user->id;
        $self->joined_at = now();
        $self->member_status = 1;
        $self->invited_at = now();
        $self->admin_flag = 1;
        $self->save();
        if($profile_url){
            $unique_number = rand(1000, 9999); 
            $current_timestamp = time(); 
            $new_a_path = $current_timestamp . $unique_number; 
            $imageContents = file_get_contents($profile_url);
            $img = Image::make(imagecreatefromstring($imageContents));
            $user->update(['a_version' => $user->a_version + 1]);
            $size_variants = [200, 120, 80, 45, 30, 25, 20, 15];
            if (!Storage::disk('local')->exists('temp')) {
                Storage::disk('local')->makeDirectory('temp');
            }
            foreach($size_variants as $size){
                $img_rsz = $img->resize($size, $size);
                Storage::disk('s3')->delete('profile_icon/' . $user->id . '_' . $user->a_path . '_' . $size . '.jpg');   
                $set_path = $user->id . '_' . $new_a_path . '_' . $size . '.jpg';
                $temp_path = storage_path('app/temp/'.$set_path);
                $img_rsz->save($temp_path);

                if (!Storage::disk('s3')->exists('profile_icon')) {
                    Storage::disk('s3')->makeDirectory('profile_icon');
                }
                Storage::disk('s3')->put('profile_icon/' . $user->id . '_' . $new_a_path . '_' . $size . '.jpg', file_get_contents($temp_path));
                unlink($temp_path); 
            }
            $user->update(['a_path' => $new_a_path]);
        }else{
            try {
                $createIcon = $this->sharedService->createUserDefaultIcon($user);             
               
                if ($createIcon) {
                    $user->save();
                } else {
                    $user->delete();
                    throw ValidationException::withMessages(['message' => 'Icon create failed.']);
                }   
            } catch (\Exception $e) {           
                $user->delete();       
                throw ValidationException::withMessages(['message' => 'Icon create failed.']);
            } 
        }
        try {
            $type = 'user_qr_code';
            $id = $user->id;
            $current_token = $user->q_token;
            $newCode = $this->sharedService->newUserQrCode($type, $id, $current_token);
            if($newCode){
                $user->update(['q_token' => $newCode]);  
            }
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['message' => 'commonError']);
        }
        
    }
}
