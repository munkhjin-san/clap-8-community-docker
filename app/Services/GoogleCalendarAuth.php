<?php

namespace App\Services;

use App\Models\OAuthCredential;
use Illuminate\Support\Carbon;
use Google\Client as Google_Client;
use Google\Service\Calendar as Google_Service_Calendar;

class GoogleCalendarAuth
{
    public function makeClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(config('google.redirect_uri'));

        return $client;
    }

    public function upsertCredentials(int $userId, array $tokenPayload, ?string $accountName = null, ?string $accountAvatar = null, array $calendarIds = []): OAuthCredential
    {
        // tokenPayload is what Google_Client->getAccessToken() returns
        $expiresAt = isset($tokenPayload['expires_in'])
            ? Carbon::now()->addSeconds((int) $tokenPayload['expires_in'])
            : null;

        $cred = OAuthCredential::query()->firstOrNew([
            'user_id'  => $userId,
            'provider' => 'google',
            'service'  => 'calendar'
        ]);

        $cred->token_type = $tokenPayload['token_type'] ?? 'Bearer';
        $cred->scope      = $tokenPayload['scope']     ?? null;
        $cred->expires_at = $expiresAt;

        // Store access token bundle
        $cred->access_token_enc = [
            'access_token' => $tokenPayload['access_token'] ?? null,
            'created_at'   => now()->timestamp,
            'raw'          => $tokenPayload,  // optional, handy for debugging; still encrypted
        ];

        // Only set refresh if present; Google won’t always resend it
        if (!empty($tokenPayload['refresh_token'])) {
            $cred->refresh_token_enc = [
                'refresh_token' => $tokenPayload['refresh_token'],
            ];
        }

        // Optional: OIDC id_token
        if (!empty($tokenPayload['id_token'])) {
            $cred->id_token_enc = ['id_token' => $tokenPayload['id_token']];
        }
        if ($accountName !== null) {
            $cred->account_name = $accountName;
        }
        if ($accountAvatar !== null) {
            $cred->avatar_url = $accountAvatar;
        }
        if (!empty($calendarIds)) {
            $cred->calendar_ids = $calendarIds;
        }

        $cred->save();
        return $cred;
    }

    public function getAuthorizedCalendarService(OAuthCredential $cred): Google_Service_Calendar
    {
        $client = $this->makeClient();

        // Rehydrate client with current token
        $tokenArr = $cred->access_token_enc['raw'] ?? [
            'access_token' => $cred->access_token,
            'token_type'   => $cred->token_type,
            'scope'        => $cred->scope,
        ];
        $client->setAccessToken($tokenArr);

        // Refresh if expired and we have refresh token
        $expired = $cred->expires_at && $cred->expires_at->isPast();
        if ($expired && $cred->refresh_token) {
            $new = $client->fetchAccessTokenWithRefreshToken($cred->refresh_token);
            if (!isset($new['error'])) {
                // Merge and persist
                $this->upsertCredentials($cred->user_id, array_merge($tokenArr, $new), $cred->account_email);
                $client->setAccessToken(array_merge($tokenArr, $new));
            } else {
                // Mark as revoked to force re-consent later
                $cred->revoked_at = now();
                $cred->save();
                throw new \RuntimeException('Google refresh failed: '.$new['error']);
            }
        }

        return new Google_Service_Calendar($client);
    }
}
