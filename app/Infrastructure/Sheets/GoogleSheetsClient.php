<?php

// app/Infrastructure/Sheets/GoogleSheetsClient.php
namespace App\Infrastructure\Sheets;

use Google\Client as GoogleClient;
use Google\Service\Sheets;

class GoogleSheetsClient {
  public Sheets $svc;
  public function __construct() {
    $c = new GoogleClient();
    $c->setApplicationName(config('app.name').' Google Sheets API');
    $c->setScopes([Sheets::SPREADSHEETS_READONLY]);

    if (config('google.sa_enabled')) {
      $c->useApplicationDefaultCredentials(); // needs GOOGLE_APPLICATION_CREDENTIALS
    } else {
      $path = storage_path(config('services.google.credentials_json'));;

      $c->setAuthConfig($path);
      $c->setAccessType('offline');
    }
    $this->svc = new Sheets($c);
  }
}
