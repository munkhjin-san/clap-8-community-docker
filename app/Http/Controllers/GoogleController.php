<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client as Google_Client;
use Google\Service\Calendar as Google_Service_Calendar;
use Google\Service\Oauth2 as Google_Service_Oauth2;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\GoogleCalendarAuth;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Exception;

class GoogleController extends Controller
{    


    public function __construct(protected GoogleCalendarAuth $auth) {}

    public function redirect()
    {
        $client = $this->auth->makeClient();

        $client->setScopes([
            'https://www.googleapis.com/auth/calendar.readonly',
            'https://www.googleapis.com/auth/userinfo.profile',
        ]);
        $client->setIncludeGrantedScopes(true);
        $client->setAccessType('offline');
        $client->setPrompt('consent');


        return redirect()->away($client->createAuthUrl());
    }

    public function callback()
    {
        $client = $this->auth->makeClient();

        $next  = "/schedule?sync_success=true&stamp=".time();

        $code = request('code');
        if (!$code) {
            abort(400, 'Missing code');
        }

        // If this throws/returns invalid_grant, check the checklist above
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            logger()->error('Google OAuth exchange failed', [
                'error' => $token['error'],
                'error_description' => $token['error_description'] ?? null,
                'used_redirect' => config('google.redirect_uri'),
            ]);
            abort(400, 'OAuth exchange failed: '.$token['error']);
        }

        $oauth2 = new Google_Service_Oauth2($client);

        $userInfo = $oauth2->userinfo->get();
        $calendar_ids = [];
        // Store calendar IDs if available
        try {
            $service = new Google_Service_Calendar($client);
            $calendars = $service->calendarList->listCalendarList()->getItems();
            foreach ($calendars as $calendar) {
                $calendar_ids[] = $calendar->getId();
            }
        } catch (Exception $e) {
            $next  = "/schedule?sync_success=false&stamp=".time();
            return redirect($next);direct($next);
        }
        
        // $userData = [
        //     'name' => $userInfo->getName(),
        //     'avatar_url' => $userInfo->getPicture(),
        // ];
        $google_user_name = $userInfo->getName();
        $google_user_avatar = $userInfo->getPicture();
        $this->auth->upsertCredentials(auth()->id(), $token, $google_user_name, $google_user_avatar, $calendar_ids);
        return redirect($next);
    }

    public function get_google_calendar_events(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:1970|max:2100',
        ]);
        $month = $request->month;
        $year = $request->year;
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cred = $user->googleCalendarCredential;
        if(!$cred) {
            return response()->json(['google_events' => []] );
        }

        $client = $this->auth->makeClient();

        $token = $cred->access_token_enc['access_token'] ?? null;
        
        if (!$token) abort(401, 'Not authorized');
        $client->setAccessToken($token);
        $client->setScopes([
            Google_Service_Calendar::CALENDAR_READONLY,
        ]);
        if ($client->isAccessTokenExpired()) {
            $refreshToken = $cred->refresh_token_enc['refresh_token'] ?? null;
            if (!$refreshToken) {
                abort(401, 'No refresh token, reauthorize');
            }
            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            if (!isset($newToken['error'])) {
                $client->setAccessToken($newToken);
                $user->google_token = $newToken;
                $user->save();
            }
        }

        $service = new Google_Service_Calendar($client);
        $calendars = $service->calendarList->listCalendarList()->getItems();
        $timeMin = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Tokyo')
            ->startOfMonth()->startOfWeek()
            ->toRfc3339String();

        $timeMax = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Tokyo')
            ->addMonthNoOverflow()
            ->startOfMonth()->endOfWeek()
            ->toRfc3339String();

        $activeCalendarIds = $cred->calendar_ids ?? [];
        $calendars = array_filter($calendars, function($c) use ($activeCalendarIds) {
            return empty($activeCalendarIds) || in_array($c->getId(), $activeCalendarIds);
        });

        function splitEventTimes( Event $event, string $displayTz = 'Asia/Tokyo', bool $inclusiveAllDayEnd = true ): array {
            [$sd, $st, $tz, $allDay] = parseGCalDateTime($event->getStart(), $displayTz);
            [$ed, $et, $tz2, $allDay2] = parseGCalDateTime($event->getEnd(), $displayTz);

            // Normalize timezone preference
            $tz = $tz ?? $tz2 ?? $displayTz;

            // Google all-day end is exclusive; make it inclusive if requested
            if ($allDay && $ed && $inclusiveAllDayEnd) {
                $ed = Carbon::createFromFormat('Y-m-d', $ed, $tz)->subDay()->toDateString();
            }

            return [
                'start_date' => $sd,           // 'YYYY-MM-DD'
                'start_time' => $st,           // 'HH:MM' or null
                'end_date'   => $ed,           // 'YYYY-MM-DD'
                'end_time'   => $et,           // 'HH:MM' or null
                'timezone'   => $tz,           // for your logs/UI
                'all_day'    => $allDay,       // handy flag
            ];
        }

        /**
         * Handle either dateTime (RFC3339) or date (all-day) from Google.
         */
        function parseGCalDateTime(?EventDateTime $dt, string $fallbackTz): array
        {
            if (!$dt) return [null, null, $fallbackTz, false];

            $iso = $dt->getDateTime(); // e.g. '2025-09-11T14:00:00+09:00'
            $date = $dt->getDate();    // e.g. '2025-09-09' (all-day)
            $tz = $dt->getTimeZone() ?: $fallbackTz;

            if ($iso) {
                // Timed event: respect the embedded offset, then display in $tz
                $c = Carbon::parse($iso)->setTimezone($tz);
                return [$c->toDateString(), $c->format('H:i'), $tz, false];
            }

            if ($date) {
                // All-day event: store just the date, null time
                $c = Carbon::createFromFormat('Y-m-d', $date, $tz);
                return [$c->toDateString(), null, $tz, true];
            }

            return [null, null, $tz, false];
        }
        function getTextColor($hexColor) {
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
                
        $googleEvents = [];
        foreach ($calendars as $c) {
            $cid = $c->getId();
            $pageToken = null;
            do {
                $resp = $service->events->listEvents($cid, [
                    'singleEvents' => true,
                    'orderBy'      => 'startTime',
                    'timeMin'      => $timeMin,
                    'timeMax'      => $timeMax,
                    'pageToken'    => $pageToken,
                ]);
                foreach ($resp->getItems() as $e) {
                    $start = $e->getStart();
                    $dateInfo = splitEventTimes($e, 'Asia/Tokyo', true);
                    $events = [
                        'calendarId' => $cid,
                        'calendarName'   => $c->getSummary(),
                        'color' => $c->getBackgroundColor(),
                        'foregroundColor' => $c->getForegroundColor(),
                        'textColor' => getTextColor($c->getBackgroundColor()),
                        'id'         => $e->getId(),
                        'summary'    => $e->getSummary(),
                        'description' => $e->getDescription(),
                        'user_info' => $user->googleCalendarCredential ? [
                            'name' => $user->googleCalendarCredential->account_name,
                            'avatar_url' => $user->googleCalendarCredential->avatar_url,
                        ] : [
                            'name' => '',
                            'avatar_url' => '',
                        ],
                    ];
                    $events = array_merge($events, $dateInfo);
                    $googleEvents[] = $events;
                }
                $pageToken = $resp->getNextPageToken();
            } while ($pageToken);
        }

        return response()->json(['google_events' => $googleEvents]);
    }
    public function check_google_calendars(Request $request){

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cred = $user->googleCalendarCredential;
        $emptyResponse = [
            'status' => '未設定',
            'calendars' => [],
            'user_info' => [
                'name' => '',
                'avatar_url' => '',
            ],
            'calendar_ids' => [],
        ];
        if(!$cred) {
            
            return response()->json($emptyResponse);
        }
        $client = $this->auth->makeClient();
        $token = $cred->access_token_enc['access_token'] ?? null;
        if (!$token) {
            return response()->json($emptyResponse);
        }
        $client->setAccessToken($token);
        
        $client->setScopes([
            Google_Service_Calendar::CALENDAR_READONLY,
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/userinfo.email',
        ]);

        $userData = $user->googleCalendarCredential ? [
            'name' => $user->googleCalendarCredential->account_name,
            'avatar_url' => $user->googleCalendarCredential->avatar_url,
        ] : [
            'name' => '',
            'avatar_url' => '',
        ];
        
        if ($client->isAccessTokenExpired()) {
            $refreshToken = $cred->refresh_token_enc['refresh_token'] ?? null;
            if (!$refreshToken) {
                abort(401, 'No refresh token, reauthorize');
            }
            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            if (!isset($newToken['error'])) {
                $client->setAccessToken($newToken);
                $user->google_token = $newToken;
                $user->save();
            }
        }

        $service = new Google_Service_Calendar($client);
        $calendars = $service->calendarList->listCalendarList()->getItems();
        $calendar_ids = $cred->calendar_ids ?? [];

        return response()->json(['status' => '接続済み', 'calendars' => $calendars, 'user_info' => $userData, 'calendar_ids' => $calendar_ids]);

    }
    public function save_google_calendar_settings(Request $request){
        $validated = $request->validate([
            'calendar_ids' => 'required|array',
            'calendar_ids.*' => 'string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cred = $user->googleCalendarCredential;
        if(!$cred) {
            return response()->json(['message' => 'Googleカレンダーの認証情報がありません'], 401);
        }

        $cred->calendar_ids = $validated['calendar_ids'];
        $cred->save();

        return response()->json(['message' => '設定が保存されました']);
    }
    public function disconnect_google_calendar(Request $request){
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cred = $user->googleCalendarCredential;
        if($cred) {
            $cred->delete();
        }
        return response()->json(['message' => 'Googleカレンダーとの連携を解除しました']);
    }
}
