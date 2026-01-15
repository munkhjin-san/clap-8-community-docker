<?php

// app/Infrastructure/Kintone/KintoneClient.php
namespace App\Infrastructure\Kintone;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
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
        try {
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
        } catch (ClientException $e) {
            $res  = $e->getResponse();
            $body = $res ? (string) $res->getBody() : null;

            // This is what you NEED to see
            dd([
                'status' => $res?->getStatusCode(),
                'body'   => $body,
                'json'   => $body ? json_decode($body, true) : null,
            ]);
        }
    }
    public function getRecord(string|int $appId, string|int $recordId, array $fields = []): array
    {
        $base  = rtrim(config('app.kintone_base_url'), '/');
        $basic = base64_encode(config('app.kintone_user_name') . ':' . config('app.kintone_password'));

        $params = [
            'app' => $appId,
            'id'  => $recordId,
        ];

        if (!empty($fields)) {
            $params['fields'] = $fields;
        }

        $queryString = http_build_query($params);

        $resp = $this->http->get("{$base}/record.json?{$queryString}", [
            'headers' => [
                'X-Cybozu-Authorization' => $basic,
                'X-Requested-With'       => 'XMLHttpRequest',
                'Accept'                 => 'application/json',
            ],
            'timeout' => 15,
        ]);

        $data = json_decode((string) $resp->getBody(), true);

        return $data['record'] ?? [];
    }
    public function putRecord(string|int $appId, string|int $recordId, array $record): array
    {
        $base  = rtrim(config('app.kintone_base_url'), '/');
        $basic = base64_encode(config('app.kintone_user_name') . ':' . config('app.kintone_password'));

        $payload = [
            'app'    => (string) $appId,
            'id'     => (string) $recordId,
            'record' => $record,
        ];
        
        try {
            $resp = $this->http->put("{$base}/record.json", [
                'headers' => [
                    'X-Cybozu-Authorization' => $basic,
                    'X-Requested-With'       => 'XMLHttpRequest',
                    'Accept'                 => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 15,
            ]);

            return json_decode((string) $resp->getBody(), true) ?? [];

        } catch (ClientException $e) {
            $res  = $e->getResponse();
            $body = $res ? (string) $res->getBody() : null;

            // This is what you NEED to see
            dd([
                'status' => $res?->getStatusCode(),
                'body'   => $body,
                'json'   => $body ? json_decode($body, true) : null,
            ]);
        }
    }
}

