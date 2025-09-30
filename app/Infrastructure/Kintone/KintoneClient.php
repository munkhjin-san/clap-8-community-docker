<?php

// app/Infrastructure/Kintone/KintoneClient.php
namespace App\Infrastructure\Kintone;

use GuzzleHttp\Client;

class KintoneClient
{
    public function __construct(private Client $http) {}

    public function getRecords(string|int $appId, string $query, array $fields): array
    {
        $base  = rtrim(config('app.kintone_base_url'), '/');
        $basic = base64_encode(config('app.kintone_user_name').':'.config('app.kintone_password'));
        $queryString = http_build_query([
            'app'        => $appId,
            'query'      => $query,
            'fields'     => $fields,       // arrays are fine; will become fields[0]=...
        ]);
        $resp = $this->http->get("{$base}/records.json?{$queryString}", [
            'headers' => [
                'X-Cybozu-Authorization' => $basic,
                'X-Requested-With'       => 'XMLHttpRequest',
                'Accept'                 => 'application/json',
            ],
            'timeout' => 15,
        ]);
        $data = json_decode((string)$resp->getBody(), true);
        $records = $data['records'] ?? [];



        return $records;
    }
}

