<?php

// app/Infrastructure/Kintone/KintoneClient.php
namespace App\Infrastructure\Kintone;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
class KintoneClient
{
    private string $authHeader;
    public function __construct(private Client $http)
    {
        $user = config('app.kintone_user_name');
        $pass = config('app.kintone_password');

        $this->authHeader = base64_encode($user . ':' . $pass);
    }

    public function getRecords(string|int $appId, string $query, array $fields): array
    {
        $queryString = http_build_query([
            'app'        => $appId,
            'query'      => $query,
            'fields'     => $fields,       // arrays are fine; will become fields[0]=...
        ]);
        try {
            $resp = $this->http->get("records.json?{$queryString}", [
                'headers' => [
                    'X-Cybozu-Authorization' => $this->authHeader,
                    'X-Requested-With'       => 'XMLHttpRequest',
                    'Accept'                 => 'application/json',
                ],
                'timeout' => 15,
            ]);
            $data = json_decode((string)$resp->getBody(), true);

            return $data['records'] ?? [];
        } catch (ClientException $e) {
            $body = $e->hasResponse()
                ? (string) $e->getResponse()->getBody()
                : 'no response body';
            throw new \RuntimeException("Kintone API request failed: {$e->getMessage()} | Body: {$body}", 0, $e);
        }
    }
    public function getRecord(string|int $appId, string|int $recordId, array $fields = []): array
    {
        $params = [
            'app' => $appId,
            'id'  => $recordId,
        ];

        if (!empty($fields)) {
            $params['fields'] = $fields;
        }

        $queryString = http_build_query($params);

        $resp = $this->http->get("record.json?{$queryString}", [
            'headers' => [
                'X-Cybozu-Authorization' => $this->authHeader,
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
        $payload = [
            'app'    => (string) $appId,
            'id'     => (string) $recordId,
            'record' => $record,
        ];
       
        try {
            $resp = $this->http->put("record.json", [
                'headers' => [
                    'X-Cybozu-Authorization' => $this->authHeader,
                    'X-Requested-With'       => 'XMLHttpRequest',
                    'Accept'                 => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 15,
            ]);

            return json_decode((string) $resp->getBody(), true) ?? [];

        } catch (ClientException $e) {
            $body = $e->hasResponse()
                ? (string) $e->getResponse()->getBody()
                : 'no response body';
            throw new \RuntimeException("Kintone API request failed: {$e->getMessage()} | Body: {$body}", 0, $e);
        }
    }
    public function postRecord(string|int $appId, array $record): array
    {
        $payload = [
            'app'    => (string) $appId,
            'record' => $record,
        ];

        try {
            $resp = $this->http->post("record.json", [
                'headers' => [
                    'X-Cybozu-Authorization' => $this->authHeader,
                    'X-Requested-With'       => 'XMLHttpRequest',
                    'Accept'                 => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 15,
            ]);

            return json_decode((string) $resp->getBody(), true) ?? [];

        } catch (ClientException $e) {
            throw new \RuntimeException("Kintone API request failed: {$e->getMessage()}", 0, $e);
        }
    }
}

