<?php

// app/Infrastructure/Sheets/GoogleSheetsClient.php
namespace App\Infrastructure\Sheets;

use Google\Client as GoogleClient;
use Google\Service\Sheets;
use InvalidArgumentException;

class GoogleSheetsClient
{
    public Sheets $svc;

    public function __construct()
    {
        $client = new GoogleClient();
        $client->setApplicationName(config('app.name').' Google Sheets API');
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);

        if (config('google.sa_enabled')) {
            $client->useApplicationDefaultCredentials();
        } elseif ($credentials = config('services.google.credentials_json')) {
            $path = str_starts_with($credentials, DIRECTORY_SEPARATOR)
                ? $credentials
                : storage_path($credentials);

            if (! is_file($path)) {
                throw new InvalidArgumentException("Google Sheets credentials file not found: {$path}");
            }

            $client->setAuthConfig($path);
            $client->setAccessType('offline');
        }

        $this->svc = new Sheets($client);
    }
}
