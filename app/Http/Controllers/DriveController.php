<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DriveNode;
use App\Models\DriveNodeAcl;
use App\Models\DriveActivityLog;
use App\Models\ProjectRecord;
use App\Models\ProjectMember;
use App\Services\FileLogService;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\ZipStream;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use DB;

class DriveController extends Controller
{
    protected $fileLogService;
    public function __construct(FileLogService $fileLogService)
    {
        $this->fileLogService = $fileLogService;
    }
    public function index(Request $request)
    {
        $u = $request->user();
        $projectId = $request->query('project_id'); // required or nullable — your call
        $parentId  = $request->query('parent_id');

        $request->validate([
            'project_id' => ['nullable'],
            'parent_id'  => ['nullable','uuid'],
        ]);
        $project = ProjectRecord::select('id')->findOrFail($projectId);
        // if required: abort_if(!$projectId, 422, 'project_id required');

        $parent = null;
        if ($parentId) {
            $parent = DriveNode::where('id',$parentId)
                ->where('project_id',$projectId)   // scope by project
                ->whereNull('deleted_at')
                ->firstOrFail();
            $this->authorize('view', $parent);
        }
        $q = DriveNode::query()
            ->where('project_id', $projectId)
            ->where('parent_id',  $parentId)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($u, $project) {
                $q->where('visibility', 'public')
                ->orWhere('owner_id', $u->id)
                // private via ACL
                ->orWhereExists(function ($sub) use ($u) {
                    $sub->selectRaw(1)
                        ->from('drive_node_acls as a')
                        ->whereColumn('a.node_id','drive_nodes.id')
                        ->where('a.user_id',$u->id);
                });
            })->with('owner:id,name,icon_bg,icon_path', 'acls:node_id,user_id');
       $items = $q->orderByRaw("FIELD(type,'folder','file')")
        ->orderBy('name')
        ->get(['id','type','name','size','mime','updated_at','storage_path','ext','owner_id','visibility','project_id']);

        return response()->json([
            'parent' => $parent ? ['id'=>$parent->id,'name'=>$parent->name] : ['id'=>null,'name'=>'ホーム'],
            'path'   => $this->buildPath($parent, $projectId),
            'items'  => $items,
        ]);
    }

    private function buildPath(?DriveNode $node, ?string $projectId): array
    {
        $crumbs = [];
        while ($node) {
            array_unshift($crumbs, ['id'=>$node->id,'name'=>$node->name]);
            if (!$node->parent_id) break;
            $node = DriveNode::where('id',$node->parent_id)
                ->where('project_id',$projectId)
                ->whereNull('deleted_at')
                ->first();
        }
        array_unshift($crumbs, ['id'=>null,'name'=>'ホーム']);
        return $crumbs;
    }

    public function upload(Request $req) {
        $req->validate([
            'parent_id' => 'nullable|uuid',
            'file.*' => 'required|file|max:51200', // 50MB each
            'project_id' => 'required'
        ]);
        
        $ownerId = $req->user()->id;
        $uploaded = [];
        foreach ($req->file('file', []) as $f) {
            $id = (string) \Str::uuid();
            $ext = $f->getClientOriginalExtension();
            $name = $f->getClientOriginalName(); // consider sanitizing & collision handling
            $mime = $f->getClientMimeType();
            $size = $f->getSize();

            $storagePath = "drive/{$ownerId}/{$id}".($ext ? ".{$ext}" : '');
            Storage::disk('local')->put($storagePath, file_get_contents($f->getRealPath()));

            $node = DriveNode::create([
                'id' => $id,
                'parent_id' => $req->input('parent_id'),
                'project_id' => $req->input('project_id'),
                'type' => 'file',
                'name' => $name,
                'mime' => $mime,
                'ext' => $ext,
                'size' => $size,
                'storage_path' => $storagePath,
                'owner_id' => $ownerId,
            ]);

            $members = ProjectMember::where('project_id', $req->input('project_id'))
                ->where('user_id', '!=', $ownerId)
                ->pluck('user_id')
                ->unique()    
                ->values()
                ->map(fn($id) => (int)$id)
                ->all();
            $this->update(new Request([
                'visibility' => 'public',
                'members' => $members,
                'cascade' => false,
                'initial' => true
            ]), $node->id);
            $this->fileLogService->logNode($node, 'uploaded', [
                'to_path' => $this->buildLogicalPath($node->id),
                'size_bytes' => $size,
                'context' => [
                    'storage_path' => $storagePath,
                ],
            ]);

            $uploaded[] = ['id'=>$node->id,'name'=>$node->name,'type'=>'file','size'=>$size,'mime'=>$mime];
        }
        return response()->json(['uploaded'=>$uploaded]);
    }
    public function createFolder(Request $req) {
        $req->validate([
            'parent_id' => 'nullable|uuid',
            'name' => 'required|string|max:255',
            'project_id' => 'required'
        ]);
        // enforce unique per parent
        // if (DriveNode::where('parent_id', $req->parent_id)->where('name',$req->name)->where('project_id', $req->project_id)->whereNull('deleted_at')->exists()) {
        //     return response()->json(['message'=>'同名の項目が既に存在します'], 409);
        // }
        $node = DriveNode::create([
            'id' => (string) \Str::uuid(),
            'parent_id' => $req->parent_id,
            'project_id' => $req->project_id,
            'type' => 'folder',
            'name' => $req->name,
            'owner_id' => $req->user()->id,
        ]);
        $excludeId = (int) $req->user()->id;   
        $members = ProjectMember::where('project_id', $req->input('project_id'))
            ->where('user_id', '!=', $excludeId)
            ->pluck('user_id')
            ->unique()    
            ->values()
            ->map(fn($id) => (int)$id)
            ->all();          

        $this->update(new Request([
            'visibility' => 'public',
            'members' => $members,
            'cascade' => false,
            'initial' => true
        ]), $node->id);

        $this->fileLogService->logNode($node, 'created', [
            'to_path' => $this->buildLogicalPath($node->id),
            'context' => [
                'parent_id' => $node->parent_id,
            ],
        ]);

        return response()->json($node);
    }
    public function rename(Request $req, string $id) {
        $req->validate([
            'name' => 'required|string|max:255',
            'project_id' => 'required'
        ]);
        $userId = $req->user()->id;
        $node = DriveNode::where('id',$id)
            ->where('owner_id',$userId)
            ->whereNull('deleted_at')
            ->firstOrFail();
        // enforce unique per parent
        // if (DriveNode::where('parent_id', $node->parent_id)->where('name',$req->name)->where('project_id', $req->project_id)->whereNull('deleted_at')->where('id','!=',$id)->exists()) {
        //     return response()->json(['message'=>'同名の項目が既に存在します'], 409);
        // }

        $beforePath = $this->buildLogicalPath($node->id);

        $node->name = $req->name;
        $node->save();

        $afterPath = $this->buildLogicalPath($node->id);
        if ($beforePath !== $afterPath) {
            $this->fileLogService->logNode($node, 'moved', [
                'from_path' => $beforePath,
                'to_path'   => $afterPath,
            ]);
        }

        return response()->json($node);
    }
    public function destroy(Request $request)
    {
        $userId = (string) $request->user()->id;
        $ids = Arr::wrap($request->ids);
        // Prefetch nodes
        $nodes = DriveNode::where('owner_id', $userId)
            ->whereIn('id', $ids)
            ->whereNull('deleted_at') 
            ->get(['id','parent_id','type','name','project_id','storage_path','size']);

        if ($nodes->isEmpty()) return response()->noContent();

        // Get all descendant nodes recursively
        $allNodes = collect();
        $stack = $nodes->all();

        while (!empty($stack)) {
            $current = array_shift($stack);
            $allNodes->push($current);
            
            // Get direct children
            $children = DriveNode::where('parent_id', $current->id)
                ->whereNull('deleted_at')
                ->get(['id','parent_id','type','name','project_id','storage_path','size']);
                
            // Add children to stack to process their children
            $stack = array_merge($stack, $children->all());
        }

        // Log deletions for all nodes
        foreach ($allNodes as $node) {
            $path = $this->buildLogicalPath($node->id);
            $this->fileLogService->logDeleted(
                (string) $node->id,
                $node->type,
                (string) $node->name,
                $node->project_id,
                [
                    'from_path' => $path,
                    'size_bytes' => $node->type === 'file' ? (int) ($node->size ?? 0) : null,
                    'context' => array_filter([
                        'storage_path' => $node->storage_path,
                    ]),
                ]
            );
        }

        $files = $allNodes->where('type', 'file');

        // Soft delete all nodes
        \DB::transaction(function () use ($allNodes) {
            foreach ($allNodes as $n) {
                $n->delete();
            } 
        });

        // Delete storage files
        foreach ($files as $f) {
            if (!$f->storage_path) continue;
            try {
                Storage::disk(config('files.drive_disk', 'local'))->delete($f->storage_path);
            } catch (\Throwable $e) {
                \Log::warning('File storage delete failed', [
                    'node_id' => $f->id,
                    'path'    => $f->storage_path,
                    'err'     => $e->getMessage(),
                ]);
            }
        }

        return response()->json('success', 200);

    }

    public function drive_thumbnail(string $b64path, string $size, string $color = '000000')
    {
        // 1) decode base64url safely (restore padding)
        $storageKey = $this->b64urlDecode($b64path);
        if ($storageKey === '') abort(404);

        // 2) size + color
        $isOriginal = $size === 'original';
        $dim = $isOriginal ? null : max(16, min(512, (int)$size));
        $bg  = preg_match('/^[A-Fa-f0-9]{6}$/', $color) ? $color : '000000';
        // 3) file check
        if (!Storage::disk('local')->exists($storageKey)) {
            // fallback square (mirror your user_icon_thumbnail behavior)
            $img = $isOriginal
                ? Image::create(200, 200)->fill($bg)
                : Image::create($dim, $dim)->fill($bg);
            return $this->image_response($img);
        }

        // 4) read + orient + size
        $abs = Storage::disk('local')->path($storageKey);
        $img = Image::read($abs);
        $img->scaleDown(null, 30);
        $size = (int) $dim;

        // 5) return; your helper should encode and set headers
        return $this->image_response($img);
    }

    private function b64urlDecode(string $s): string
    {
        $s = strtr($s, '-_', '+/');
        $pad = strlen($s) % 4;
        if ($pad) $s .= str_repeat('=', 4 - $pad); // restore stripped '='
        $decoded = base64_decode($s, true);
        return $decoded === false ? '' : $decoded;
    }
    private function image_response($img){
        return response($img->toWebp(), 200, );
    }
    protected function findItemOrFail(string $id): array
    {
        $item = DriveNode::findOrFail($id);
        // $this->authorize('view', $item); // if you use policies

        return [
            'id'   => (string) $item->id,
            'name' => $item->name,
            'type' => $item->type,          // 'file' | 'folder'
            'path' => (string) $item->storage_path, // e.g. 'drive/765/uuid.png' or 'drive/765'
            'project_id' => $item->project_id,
            'parent_id' => $item->parent_id,
        ];
    }
    protected function buildLogicalPath(string $nodeId, ?string $stopAtId = null): string
    {
        $parts = [];
        $seen  = [];
        $curId = $nodeId;

        for ($i = 0; $i < 128 && $curId !== null; $i++) {
            if (isset($seen[$curId])) break;
            $seen[$curId] = true;

            $n = DriveNode::query()
                ->where('id', $curId)
                ->whereNull('deleted_at')
                ->first(['id','parent_id','name','type']);

            if (!$n) break;

            $parts[] = $this->safeSegment($n->name ?: ($n->type === 'folder' ? "folder-{$n->id}" : "file-{$n->id}"));

            if ($stopAtId !== null && $n->id === $stopAtId) break;
            $curId = $n->parent_id ?: null; // both are UUIDs
        }

        return implode('/', array_reverse($parts));
    }


    protected function safeSegment(string $name): string
    {
        $name = trim($name);
        // Replace path separators with spaces to avoid fake hierarchy injection
        $name = str_replace(['/', '\\'], ' ', $name);
        // Optionally collapse multiple spaces
        $name = preg_replace('/\s{2,}/', ' ', $name) ?? $name;
        return $name !== '' ? $name : 'untitled';
    }

    public function downloadFile(Request $request, string $id)
    {
        $item = $this->findItemOrFail($id);
        abort_unless($item['type'] === 'file', 404);

        $path = $this->normalizeStoragePath($item['path']);
        abort_unless($this->fileExists($path), 404);

        $filename = $item['name'] ?: basename($path);
        $mime = $this->mimeTypeFor($path);
        $size = $this->disk()->size($path);
        $projectId = $item['project_id'] ? (int)$item['project_id'] : null;

        $logicalPath = $this->buildLogicalPath($id);
        $logService = $this->fileLogService;

        $payload = [
            'item_id' => $id,
            'item_type' => 'file',
            'item_name' => $filename,
            'project_id' => $projectId,
            'action' => 'downloaded',
            'from_path' => $logicalPath,
            'size_bytes' => $size,
            'context' => [
                'storage_path' => $path,
            ],
        ];

        return response()->streamDownload(function () use ($path, $logService, $payload) {
            $stream = $this->readStream($path);
            if ($stream === false) {
                throw new \RuntimeException('Failed to read file');
            }
            fpassthru($stream);
            @fclose($stream);
            $logService->log($payload);
        }, $filename, [
            'Content-Type'        => $mime,
            'Content-Disposition' => $this->contentDisposition($filename),
        ]);
    }

    public function downloadFolderZip(Request $request, string $id): StreamedResponse
    {
        $folder = $this->findItemOrFail($id);
        abort_unless($folder['type'] === 'folder', 404);

        $displayName = $folder['name'] ?: 'folder';
        $zipName = $this->safeName($displayName . '.zip');
        $projectId = $folder['project_id'] ? (int)$folder['project_id'] : null;

        [$files, $emptyDirs] = $this->collectDescendantsForZip($folder['id'], $displayName);

        foreach ($files as &$fileEntry) {
            $fileEntry['logical_path'] = $this->buildLogicalPath($fileEntry['node_id']);
        }
        unset($fileEntry);
        $logService = $this->fileLogService;

        return $this->streamZip($zipName, function (ZipStream $zip) use ($files, $emptyDirs, $projectId, $logService) {
            foreach ($emptyDirs as $dirZipPath) {
                $zip->addFile(rtrim($dirZipPath, '/') . '/', '');
            }

            foreach ($files as $f) {
                $stream = $this->readStream($f['storage_path']);
                if (!$stream) {
                    continue;
                }

                $zip->addFileFromStream($f['zip_path'], $stream);
                fclose($stream);

                $logService->log([
                    'item_id' => $f['node_id'],
                    'item_type' => 'file',
                    'item_name' => $f['name'],
                    'project_id' => $projectId,
                    'action' => 'downloaded',
                    'from_path' => $f['logical_path'] ?? null,
                    'size_bytes' => $f['size'],
                    'context' => [
                        'storage_path' => $f['storage_path'],
                        'zip_rel_path' => $f['zip_path'],
                    ],
                ]);
            }
        });
    }


    public function downloadMultiZip(Request $request): StreamedResponse
    {
        $ids = (array) $request->input('ids', []);
        abort_if(empty($ids), 422, 'ids is required');

        $nodes = DriveNode::query()
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->get(['id', 'type', 'name', 'size', 'project_id', 'storage_path'])
            ->keyBy('id');

        $ordered = collect($ids)
            ->map(fn ($id) => $nodes->get($id))
            ->filter();

        abort_if($ordered->isEmpty() || $ordered->count() !== count($ids), 404);

        $projectIds = $ordered->pluck('project_id')->filter()->unique();
        $projectId = $projectIds->count() === 1 ? (int) $projectIds->first() : null;
        
        [$entries, $emptyDirs] = $this->collectEntriesForMultiZip($ordered);

        foreach ($entries as &$entry) {
            $entry['logical_path'] = $this->buildLogicalPath($entry['node_id']);
        }
        unset($entry);

        $zipName = 'selected-' . count($ids) . '-' . now()->format('Ymd_His') . '.zip';

        $logService = $this->fileLogService;

        return $this->streamZip($zipName, function (ZipStream $zip) use ($entries, $emptyDirs, $projectId, $logService) {
            foreach ($emptyDirs as $dirZipPath) {
                $zip->addFile(rtrim($dirZipPath, '/') . '/', '');
            }

            foreach ($entries as $e) {
                $stream = $this->readStream($e['storage_path']);
                if (!$stream) {
                    continue;
                }

                $zip->addFileFromStream($e['zip_path'], $stream);
                fclose($stream);

                $logService->log([
                    'item_id' => $e['node_id'],
                    'item_type' => 'file',
                    'item_name' => $e['name'],
                    'project_id' => $projectId,
                    'action' => 'downloaded',
                    'from_path' => $e['logical_path'] ?? null,
                    'size_bytes' => (int) ($e['size'] ?? 0),
                    'context' => [
                        'storage_path' => $e['storage_path'],
                        'zip_rel_path' => $e['zip_path'],
                    ],
                ]);
            }
        });
    }

    public function logs(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|integer',
            'item_id'    => 'nullable|uuid',
            'limit'      => 'nullable|integer|min:1|max:200',
        ]);

        $projectId = (int) $data['project_id'];
        $limit = min($data['limit'] ?? 100, 200);
        $itemId = $data['item_id'] ?? null;

        $user = $request->user();
        $isMember = ProjectMember::where('project_id', $projectId)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isMember || $user->isProjectManager($projectId), 403);

        $query = DriveActivityLog::with(['user:id,name,icon_path,icon_bg'])
            ->where('project_id', $projectId)
            ->latest('occurred_at');

        if ($itemId) {
            $query->where('item_id', $itemId);
        }

        $logs = $query->limit($limit)->get();

        $data = $logs->map(function (DriveActivityLog $log) {
            return [
                'id'         => (int) $log->id,
                'action'     => $log->action,
                'item_id'    => $log->item_id,
                'item_type'  => $log->item_type,
                'item_name'  => $log->item_name,
                'user'       => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'icon_path' => $log->user->icon_path,
                    'icon_bg' => $log->user->icon_bg,
                ] : null,
                'from_path'  => $log->from_path,
                'to_path'    => $log->to_path,
                'size_bytes' => $log->size_bytes,
                'client_ip'  => $log->client_ip,
                'user_agent' => $log->user_agent,
                'context'    => $log->context,
                'timestamp'  => optional($log->occurred_at ?? $log->created_at)->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
    /* ------------------------------ zip core ------------------------------ */

    protected function streamZip(string $zipName, \Closure $builder): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $zipName = $this->safeName($zipName);

        return response()->stream(function () use ($builder) {
            // kill anything that would prepend/append bytes
            @ini_set('zlib.output_compression', '0');
            if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1');
            while (ob_get_level() > 0) { @ob_end_clean(); }   // no stray buffers

            // turn off reverse proxy buffering if present
            echo ''; // nudge PHP to actually start output
            if (!headers_sent()) header('X-Accel-Buffering: no');

            $zip = new ZipStream(
                outputName: null,
                sendHttpHeaders: false,
                defaultEnableZeroHeader: true,
                enableZip64: true,
                flushOutput: true
            );

            try {
                $builder($zip);
            } finally {
                // ensure central directory is written even if builder throws
                $zip->finish();
            }
        }, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => $this->contentDisposition($zipName),
            // absolutely no extra content encodings
            'Content-Encoding'    => 'identity',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }



    // Walk a folder on the default Storage disk (no massive prelisting)
    protected function addFolderToZipStorage(ZipStream $zip, string $dir, string $prefix = '', ?object $stats = null): void
    {
        $dir = $this->normalizeStoragePath($dir);
        if ($prefix !== '') $zip->addFile(rtrim($prefix, '/') . '/', ''); // empty folder visible
        $this->walkStorage($zip, $dir, $prefix, $stats);
    }

    protected function walkStorage(ZipStream $zip, string $dir, string $prefix, ?object $stats = null): void
    {
        $disk = $this->disk();

        foreach ($disk->files($dir) as $file) {
            $name = basename($file);
            $entry = $prefix !== '' ? $prefix . '/' . $name : $name;
            $this->addFileToZipStorage($zip, $file, $entry, $stats);
        }

        foreach ($disk->directories($dir) as $sub) {
            $name = basename($sub);
            $subPrefix = $prefix !== '' ? $prefix . '/' . $name : $name;
            $zip->addFile($subPrefix . '/', '');
            $this->walkStorage($zip, $sub, $subPrefix, $stats);
        }
    }

    protected function addFileToZipStorage(ZipStream $zip, string $path, string $entryName, ?object $stats = null): void
    {
        $path = $this->normalizeStoragePath($path);
        $stream = $this->readStream($path);
        if ($stream === false) {
            return; // skip unreadable files
        }
        $zip->addFileFromStream($entryName, $stream);
        @fclose($stream);

        if ($stats) {
            $stats->files++;
            $size = $this->disk()->size($path);
            $stats->bytes += $size;
            $stats->manifest[] = [
                'type' => 'file',
                'entry' => $entryName,
                'size' => $size,
            ];
        }
    }

    /* ---------------------------- storage helpers ---------------------------- */

    protected function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(config('filesystems.default'));
    }

    protected function normalizeStoragePath(string $path): string
    {
        // Ensure a relative key on the default disk, e.g. 'drive/765/..'
        // Strip any accidental leading slashes/backslashes.
        return ltrim($path, "/\\");
    }

    protected function fileExists(string $path): bool
    {
        $path = $this->normalizeStoragePath($path);
        $disk = $this->disk();

        // Laravel 10+ has fileExists; older versions fall back to exists()
        return method_exists($disk, 'fileExists') ? $disk->fileExists($path) : $disk->exists($path);
    }

    protected function directoryExists(string $path): bool
    {
        $path = $this->normalizeStoragePath($path);
        $disk = $this->disk();

        if (method_exists($disk, 'directoryExists')) {
            return $disk->directoryExists($path);
        }

        // Fallback: consider it a directory if it has files or subdirs
        return count($disk->files($path)) + count($disk->directories($path)) > 0;
    }

    protected function readStream(string $path)
    {
        $path = $this->normalizeStoragePath($path);
        return $this->disk()->readStream($path);
    }

    protected function mimeTypeFor(string $path): string
    {
        $path = $this->normalizeStoragePath($path);
        return $this->disk()->mimeType($path) ?: 'application/octet-stream';
    }

    protected function contentDisposition(string $filename): string
    {
        // RFC 6266: include ASCII fallback and UTF-8 filename*
        $fallback = str_replace(['"', '\\'], '_', $filename);
        return "attachment; filename=\"{$fallback}\"; filename*=UTF-8''" . rawurlencode($filename);
    }

    protected function safeName(string $name): string
    {
        return preg_replace('/[\\\\\\/:*?"<>|]/', '_', $name);
    }
    protected function inferBaseFromChildren(string $folderId): string
    {
        // Find one child with a storage_path and use its parent directory as the base.
        $firstPath = DriveNode::query()
            ->where('parent_id', $folderId)
            ->whereNotNull('storage_path')
            ->where('storage_path', '!=', '')
            ->orderBy('id')
            ->value('storage_path');

        if (!$firstPath) return ''; // no children with paths
        $firstPath = ltrim((string) $firstPath, "/\\");
        return \Illuminate\Support\Str::beforeLast($firstPath, '/'); // e.g. drive/765
    }
    protected function ensureUniqueName(string $name, array &$seen): string
    {
        $base = $name;
        $i = 2;
        while (isset($seen[$name])) {
            $name = $base . ' (' . $i . ')';
            $i++;
        }
        $seen[$name] = true;
        return $name;
    }
    protected function collectEntriesForMultiZip(\Illuminate\Support\Collection $ordered)
    {
        $entries = [];
        $emptyDirs = [];

        // root-level name de-duplication: "Folder", "Folder (2)" etc.
        $rootNameSeen = [];

        foreach ($ordered as $node) {
            if ($node->type === 'file') {
                $rootName = $this->ensureUniqueName($node->name ?: 'file', $rootNameSeen);
                $entries[] = [
                    'node_id'      => $node->id,
                    'zip_path'     => $rootName,
                    'storage_path' => ltrim((string) $node->storage_path, "/\\"),
                    'size'         => (int) ($node->size ?? 0),
                    'name'         => $node->name ?: $rootName,
                ];
                continue;
            }

            // Folder: collect descendants under this folder’s display name
            $base = $this->ensureUniqueName($node->name ?: 'folder-' . $node->id, $rootNameSeen);
            [$files, $dirs] = $this->collectDescendantsForZip($node->id, $base);

            $entries = array_merge($entries, $files);
            $emptyDirs = array_merge($emptyDirs, $dirs);
        }

        return [$entries, $emptyDirs];
    }
    protected function collectDescendantsForZip(string $rootFolderId, string $rootDisplay): array
    {
        $files = [];
        $emptyDirs = [];

        // BFS/DFS over your DriveNode tree. This assumes a simple parent_id relation.
        $stack = [[ 'id' => $rootFolderId, 'zipBase' => $rootDisplay ]];

        while ($stack) {
            $cur = array_pop($stack);
            $children = DriveNode::query()
                ->where('parent_id', $cur['id'])
                ->orderBy('type')  // folders first, maybe
                ->orderBy('name')
                ->get(['id','type','name','storage_path','size']);

            if ($children->isEmpty()) {
                // optional: preserve empty folder in ZIP
                $emptyDirs[] = $cur['zipBase'];
                continue;
            }

            foreach ($children as $ch) {
                $zipPath = $cur['zipBase'] . '/' . $ch->name;

                if ($ch->type === 'folder') {
                    $stack[] = [ 'id' => $ch->id, 'zipBase' => $zipPath ];
                } else {
                    // only add files that actually belong to this node
                    if (!empty($ch->storage_path)) {
                        $files[] = [
                            'node_id'     => $ch->id,
                            'zip_path'    => $zipPath,
                            'storage_path'=> ltrim($ch->storage_path, '/\\'),
                            'size'        => (int)($ch->size ?? 0),
                            'name'        => $ch->name,
                        ];
                    }
                }
            }
        }

        return [$files, $emptyDirs];
    }
    public function update(Request $r, string $id)
    {
        $data = $r->validate([
            'visibility' => 'required|in:public,private',
            'members'    => 'array',
            'members.*'  => 'integer|exists:users,id',
            'cascade'    => 'boolean',
            'initial'    => 'boolean' 
        ]);

        $node = DriveNode::with(['project:id'])->findOrFail($id);
        $this->authorize('share', $node);

        DB::transaction(function () use ($node, $data) {
            // 1) Update the node's visibility
            $node->visibility = $data['visibility'];
            $node->save();

            // 2) Replace explicit ACLs on THIS node
            DriveNodeAcl::where('node_id',$node->id)->whereNull('inherited_from')->delete();

            if ($node->visibility === 'private' || ($data['initial'] ?? false)) {
                $rows = collect($data['members'] ?? [])
                    ->unique()
                    ->map(fn($uid) => [
                        'node_id'        => $node->id,
                        'user_id'        => $uid,
                        'role'           => 'viewer',    // always viewer in your world
                        'inherited_from' => null,
                        'granted_by'     => auth()->id(),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ])->values()->all();

                if (!empty($rows)) DriveNodeAcl::insert($rows);
            }

            // 3) Cascade to subtree if asked
            if ($data['cascade'] ?? false) {
                $descendantIds = $this->descendantsOf($node->id);

                // wipe inherited rows that originated from THIS node
                if (!empty($descendantIds)) {
                    DriveNodeAcl::whereIn('node_id',$descendantIds)
                        ->where('inherited_from',$node->id)
                        ->delete();
                }

                if ($node->visibility === 'private' && !empty($descendantIds)) {
                    // re-create inherited rows from the explicit members we just set
                    $explicit = DriveNodeAcl::where('node_id',$node->id)
                        ->whereNull('inherited_from')
                        ->get(['user_id','role']);

                    if ($explicit->isNotEmpty()) {
                        $batch = [];
                        foreach ($descendantIds as $childId) {
                            foreach ($explicit as $a) {
                                $batch[] = [
                                    'node_id'        => $childId,
                                    'user_id'        => $a->user_id,
                                    'role'           => $a->role,
                                    'inherited_from' => $node->id,
                                    'granted_by'     => auth()->id(),
                                    'created_at'     => now(),
                                    'updated_at'     => now(),
                                ];
                            }
                        }
                        foreach (array_chunk($batch, 1000) as $chunk) {
                            DriveNodeAcl::insert($chunk);
                        }
                    }
                }
                // if visibility == public: nothing to do; subtree opens via policy
            }
        });

        return response()->noContent();
    }
    public function show(string $id)
    {
        $node = DriveNode::with(['project:id'])->findOrFail($id);
        $this->authorize('share', $node);

        $members = DriveNodeAcl::where('node_id', $node->id)
                ->whereNull('inherited_from')
                ->with('members:id,name,email,icon_bg,icon_path') // eager load user relationship
                ->get()
                ->pluck('members')      // collection of User models
                ->filter()           // drop nulls, just in case
                ->values();


        return response()->json([
            'nodeId'     => (string)$node->id,
            'visibility' => $node->visibility,          // 'public' | 'private'
            'members'    => $members,                   // array of userIds (explicit only)
        ]);
    }
    public function previewFile(string $id) 
    {
        $node = DriveNode::findOrFail($id);
        abort_unless($node->type === 'file', 404);
        $logicalPath = $this->buildLogicalPath($node->id);
        $this->fileLogService->logNode($node, 'accessed', [
            'from_path' => $logicalPath,
            'context' => [
                'zip_rel_path' => $logicalPath,
            ],
        ]);

        return response()->json([
            'id' => (string)$node->id,
            'name' => $node->name,
            'mime' => $node->mime,
            'size' => $node->size,
            'ext'  => $node->ext,
            'storage_path' => $node->storage_path,
            'type' => $node->type,
        ]);
    }

    public function move(Request $req)
    {
        $data = $req->validate([
            'project_id' => 'required',
            'ids'        => 'required|array|min:1',
            'ids.*'      => 'uuid',
            'dest_id'    => 'nullable|uuid',
        ]);

        $projectId = $data['project_id'];
        $destId = $data['dest_id'] ?? null;
        $user = $req->user();

        $dest = null;
        if ($destId) {
            $dest = DriveNode::where('id', $destId)
                ->where('project_id', $projectId)
                ->whereNull('deleted_at')
                ->firstOrFail();
            abort_unless($dest->type === 'folder', 422, '移動先はフォルダである必要があります');
            // authorize view/update on destination context
            $this->authorize('view', $dest);
        }

        // fetch all nodes to move
        $nodes = DriveNode::whereIn('id', $data['ids'])
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->get();

        if ($nodes->count() !== count($data['ids'])) {
            abort(404);
        }

        // authorization on each node
        foreach ($nodes as $n) {
            $this->authorize('update', $n);
        }

        // prevent cycles: destination cannot be inside any of moving folders
        if ($dest) {
            $movingIds = $nodes->pluck('id')->all();
            $cursor = $dest;
            while ($cursor) {
                if (in_array($cursor->id, $movingIds, true)) {
                    abort(422, 'フォルダを自身またはその子孫に移動できません');
                }
                if (!$cursor->parent_id) break;
                $cursor = DriveNode::where('id', $cursor->parent_id)
                    ->where('project_id', $projectId)
                    ->whereNull('deleted_at')
                    ->first();
            }
        }

        $originalPaths = [];
        foreach ($nodes as $node) {
            $originalPaths[$node->id] = $this->buildLogicalPath($node->id);
        }

        DB::transaction(function () use ($nodes, $destId) {
            foreach ($nodes as $n) {
                $n->parent_id = $destId; // allow null = root
                $n->save();
            }
        });

        foreach ($nodes as $n) {
            $toPath = $this->buildLogicalPath($n->id);
            $fromPath = $originalPaths[$n->id] ?? null;
            if ($fromPath !== $toPath) {
                $this->fileLogService->logNode($n, 'moved', [
                    'from_path' => $fromPath,
                    'to_path'   => $toPath,
                ]);
            }
        }

        return response()->noContent();
    }
    public function writeAccessLogs(Request $req) 
    {
        $data = $req->validate([
            'id'        => 'required|uuid',
            'name'      => 'required|string',
            'project_id'=> 'required|integer',
        ]);
        $id = $data['id'];
        $filename = $data['name'];
        $projectId = $data['project_id'];

        $node = DriveNode::find($id);
        $path = $node ? $this->buildLogicalPath($node->id) : null;

        if ($node) {
            $this->fileLogService->logNode($node, 'accessed', [
                'from_path' => $path,
                'context' => [
                    'zip_rel_path' => $path,
                ],
            ]);
        } else {
            $this->fileLogService->log([
                'item_id' => $id,
                'item_type' => 'file',
                'item_name' => $filename,
                'project_id' => $projectId,
                'action' => 'accessed',
                'from_path' => $path,
            ]);
        }

        return response()->json(['zip_path' => $path]);

    }
    public function writeDownloadLogs(Request $req)
    {
        $data = $req->validate([
            'id'        => 'required|uuid',
            'name'      => 'required|string',
            'project_id'=> 'required|integer',
            'path'      => 'required|string'
        ]);
        $id = $data['id'];
        $filename = $data['name'];
        $projectId = $data['project_id'];
        $storagePath = ltrim($data['path'], '/\\');
        $size = $this->disk()->size($storagePath);

        $node = DriveNode::find($id);
        $path = $node ? $this->buildLogicalPath($node->id) : null;

        if ($node) {
            $this->fileLogService->logNode($node, 'downloaded', [
                'from_path' => $path,
                'size_bytes' => $size,
                'context' => [
                    'storage_path' => $storagePath,
                ],
            ]);
        } else {
            $this->fileLogService->log([
                'item_id' => $id,
                'item_type' => 'file',
                'item_name' => $filename,
                'project_id' => $projectId,
                'action' => 'downloaded',
                'from_path' => $path,
                'size_bytes' => $size,
                'context' => [
                    'storage_path' => $storagePath,
                ],
            ]);
        }

        return response()->noContent();

    }
}
