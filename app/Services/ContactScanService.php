<?php

namespace App\Services;

use App\Models\ContactBatch;
use App\Models\ContactBatchItem;
use App\Models\ContactRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ContactScanService
{
    public function storeContactRecord(ContactBatch $batch, ContactBatchItem $item, array $cardData): ?ContactRecord
    {
        $normalized = $this->normalizeContactEntry($cardData);
        $existingRecord = $item->contact_record_id ? ContactRecord::find($item->contact_record_id) : null;

        $normalized['card_hash'] = $this->computeCardHash($item, $existingRecord?->card_hash);
        $duplicates = $this->findDuplicateContacts($normalized, $existingRecord?->id);
        $item->card_hash = $normalized['card_hash'];
        $item->duplicate_candidates = $duplicates;
        $item->needs_review = !empty($duplicates);
        $item->save();

        $cardPath = $this->promoteCardImage($batch, $item, $existingRecord?->card_path);

        $attributes = [
            'name' => $normalized['name'] ?? null,
            'company_name' => $normalized['company_name'] ?? null,
            'position' => $normalized['position'] ?? null,
            'address' => $normalized['address'] ?? null,
            'phone' => $normalized['phone'] ?? null,
            'email' => $normalized['email'] ?? null,
            'fax' => $normalized['fax'] ?? null,
            'url' => $normalized['url'] ?? null,
            'description' => 'Geminiバッチで処理された名刺' . now()->toDateTimeString(),
            'data' => $normalized['company_info'],
            'contact_type_id' => $batch->contact_type_id,
            'strategy' => null,
            'card_path' => $cardPath,
            'card_hash' => $normalized['card_hash'] ?? null,
            'is_duplicate' => !empty($duplicates),
            'duplicate_of' => $duplicates[0]['id'] ?? null,
        ];
        if ($existingRecord) {
            $existingRecord->fill($attributes);
            if ($batch->user_id) {
                $existingRecord->updated_by = $batch->user_id;
            }
            $existingRecord->save();

            $this->syncCollaborator($existingRecord, $batch->user_id);

            return $existingRecord;
        }

        $record = ContactRecord::create(array_merge($attributes, [
            'created_by' => $batch->user_id,
            'updated_by' => $batch->user_id,
        ]));

        $this->syncCollaborator($record, $batch->user_id);

        return $record;
    }
    public function normalizeContactEntry(array $entry): array
    {
        if (isset($entry['url'])) {
            $entry['url'] = $this->sanitizeUrl($entry['url']);
        }

        if (isset($entry['email']) && is_string($entry['email'])) {
            $entry['email'] = mb_strtolower(trim($entry['email']));
        }
        
        if (isset($entry['company_info']) && is_string($entry['company_info'])) {
            $entry['company_info'] = Str::markdown($entry['company_info']);
        }

        return $entry;
    }
    public function sanitizeUrl($url): string
    {
        if (!is_string($url)) {
            return '';
        }

        $trimmed = trim($url);
        if ($trimmed === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $trimmed)) {
            $trimmed = 'https://' . ltrim($trimmed, '/');
        }

        $parts = parse_url($trimmed);
        if ($parts === false || empty($parts['host'])) {
            return Str::limit($trimmed, 255, '');
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        if (!in_array($scheme, ['http', 'https'], true)) {
            $scheme = 'https';
        }

        $host = $parts['host'];
        $path = $parts['path'] ?? '';
        $queryString = '';

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
            foreach ($params as $key => $value) {
                if ($key === 'redirect_from') {
                    unset($params[$key]);
                    continue;
                }
                if (str_starts_with($key, 'utm_')) {
                    unset($params[$key]);
                    continue;
                }
                if (in_array($key, ['gclid', 'fbclid', 'yclid', 'mc_cid', 'mc_eid'], true)) {
                    unset($params[$key]);
                    continue;
                }
                if (is_array($value)) {
                    $params[$key] = reset($value);
                }
            }
            if (!empty($params)) {
                $queryString = http_build_query($params);
            }
        }

        $fragment = $parts['fragment'] ?? null;

        $cleanUrl = $scheme . '://' . $host . $path;
        if ($queryString !== '') {
            $cleanUrl .= '?' . $queryString;
        }
        if ($fragment) {
            $cleanUrl .= '#' . $fragment;
        }

        return Str::limit($cleanUrl, 255, '');
    }
    public function promoteCardImage(ContactBatch $batch, ContactBatchItem $item, ?string $currentPath = null): ?string
    {
        if ($currentPath) {
            return $currentPath;
        }

        $source = $item->stored_path;
        if (!$source) {
            return null;
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($source)) {
            return null;
        }

        $targetDir = 'card_files/' . now()->format('Y/m');
        if (!$disk->exists($targetDir) && !$disk->makeDirectory($targetDir)) {
            return null;
        }

        $extension = pathinfo($source, PATHINFO_EXTENSION) ?: 'webp';
        $target = $targetDir . '/' . Str::uuid()->toString() . '.' . $extension;

        if (!$disk->move($source, $target)) {
            return null;
        }

        $item->stored_path = $target;
        $item->save();

        return $target;
    }
    public function decodeJsonText(string $text): ?array
    {
        $clean = trim($text);
        $clean = preg_replace('/^json\s+/i', '', $clean);
        $clean = preg_replace('/^```json\s*/', '', $clean);
        $clean = preg_replace('/```$/', '', $clean);

        $decoded = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }
    public function normalizeParsedContacts(array $parsed): array
    {
        $normalized = [];
        $isList = array_keys($parsed) === range(0, count($parsed) - 1);

        if ($isList) {
            foreach ($parsed as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $normalized[] = $this->normalizeContactEntry($entry);
            }
        } else {
            if (is_array($parsed)) {
                $normalized[] = $this->normalizeContactEntry($parsed);
            }
        }

        return array_values(array_filter($normalized));
    }
    public function computeCardHash(ContactBatchItem $item, ?string $existingHash = null): ?string
    {
        if ($existingHash) {
            return $existingHash;
        }

        $path = $item->stored_path;
        if (!$path || !Storage::disk('local')->exists($path)) {
            return null;
        }

        return hash_file('sha256', Storage::disk('local')->path($path));
    }
    public function findDuplicateContacts(array $normalized, ?int $currentId = null): array
    {
        $query = ContactRecord::query();

        if (!empty($normalized['card_hash'])) {
            $query->where('card_hash', $normalized['card_hash']);
        } elseif (!empty($normalized['name']) && !empty($normalized['company_name'])) {
            $query->where(function ($q) use ($normalized) {
                $q->where('name', $normalized['name'])
                    ->where('company_name', $normalized['company_name']);

                if (!empty($normalized['email'])) {
                    $q->where('email', $normalized['email']);
                }
            });
        } else {
            return [];
        }

        if ($currentId) {
            $query->where('id', '!=', $currentId);
        }

        return $query->limit(5)->get(['id', 'name', 'company_name', 'email', 'card_hash'])
            ->map(fn ($contact) => $contact->toArray())
            ->all();
    }
    public function syncCollaborator(ContactRecord $record, ?int $userId): void
    {
        if (!$userId) {
            return;
        }

        $record->collaborators()->syncWithoutDetaching([$userId => ['role' => 'owner']]);
    }
}

