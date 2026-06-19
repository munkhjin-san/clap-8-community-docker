<?php

// app/Infrastructure/Kintone/KintoneClient.php
namespace App\Infrastructure\Kintone;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;

class KintoneClient
{
    private string $authHeader;

    public function __construct(private Client $http)
    {
        $user = config('app.kintone_user_name');
        $pass = config('app.kintone_password');

        $this->authHeader = base64_encode($user . ':' . $pass);
    }

    /**
     * @param array<int, string> $fields
     * @return array<int, array<string, mixed>>
     */
    public function getAllRecords(string|int $appId, string $query = '', array $fields = [], int $limit = 500): array
    {
        $records = [];
        $offset = 0;
        $limit = min(max($limit, 1), 500);

        do {
            $pageQuery = trim($query . ' limit ' . $limit . ' offset ' . $offset);
            $page = $this->getRecords($appId, $pageQuery, $fields);
            $records = array_merge($records, $page);
            $offset += $limit;
        } while (count($page) === $limit);

        return $records;
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
                'headers' => $this->headers(),
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
            'headers' => $this->headers(),
            'timeout' => 15,
        ]);

        $data = json_decode((string) $resp->getBody(), true);

        return $data['record'] ?? [];
    }

    public function getApp(string|int $appId): array
    {
        $queryString = http_build_query(['id' => $appId]);

        try {
            $resp = $this->http->get("app.json?{$queryString}", [
                'headers' => $this->headers(),
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

    public function getAppFields(string|int $appId): array
    {
        $queryString = http_build_query(['app' => $appId]);

        try {
            $resp = $this->http->get("app/form/fields.json?{$queryString}", [
                'headers' => $this->headers(),
                'timeout' => 15,
            ]);

            $data = json_decode((string) $resp->getBody(), true) ?? [];

            return $data['properties'] ?? [];
        } catch (ClientException $e) {
            $body = $e->hasResponse()
                ? (string) $e->getResponse()->getBody()
                : 'no response body';
            throw new \RuntimeException("Kintone API request failed: {$e->getMessage()} | Body: {$body}", 0, $e);
        }
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
                'headers' => $this->headers(),
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
                'headers' => $this->headers(),
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
    public function getComments(string|int $appId, string|int $recordId): array
    {
        $queryString = http_build_query([
            'app' => $appId,
            'record'  => $recordId,
        ]);

        try {
            $resp = $this->http->get("record/comments.json?{$queryString}", [
                'headers' => $this->headers(),
                'timeout' => 15,
            ]);

            $data = json_decode((string) $resp->getBody(), true);

            return $data['comments'] ?? [];
        } catch (ClientException $e) {
            $body = $e->hasResponse()
                ? (string) $e->getResponse()->getBody()
                : 'no response body';
            throw new \RuntimeException("Kintone API request failed: {$e->getMessage()} | Body: {$body}", 0, $e);
        }
    }
    public function getFiles(string $fileKey): ResponseInterface
    {
        $queryString = http_build_query([
            'fileKey' => $fileKey,
        ]);

        try {
            $resp = $this->http->get("file.json?{$queryString}", [
                'headers' => $this->headers('*/*'),
                'timeout' => 15,
            ]);

            return $resp;
        } catch (ClientException $e) {
            $body = $e->hasResponse()
                ? (string) $e->getResponse()->getBody()
                : 'no response body';
            throw new \RuntimeException("Kintone API request failed: {$e->getMessage()} | Body: {$body}", 0, $e);
        }
    }

    private function headers(string $accept = 'application/json'): array
    {
        return [
            'X-Cybozu-Authorization' => $this->authHeader,
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => $accept,
        ];
    }
}
