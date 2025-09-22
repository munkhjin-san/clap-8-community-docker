<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DriveNode;
use App\Models\DriveDownloadLog;
use App\Models\DriveNodeAcl;
use App\Models\ProjectRecord;
use App\Models\ProjectMember;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\ZipStream;
use Illuminate\Support\Str;
use DB;

class DriveController extends Controller
{
    protected function startLog(array $attrs): DriveDownloadLog
    {
        $attrs['user_id'] = auth()->id();
        $attrs['client_ip'] = request()->ip();
        $attrs['user_agent'] = request()->userAgent();
        $attrs['referer'] = request()->headers->get('referer');
        $attrs['started_at'] = now();
        return DriveDownloadLog::create($attrs);
    }

    protected function finishLog(DriveDownloadLog $log, array $overrides = []): void
    {
        $end = now();
        $log->fill(array_merge([
            'ended_at' => $end,
            'duration_ms' => $end->diffInMilliseconds($log->started_at),
            'success' => true,
        ], $overrides))->save();
    }
    protected function queueFinishLog(\App\Models\DriveDownloadLog $log, array $overrides): void
    {
        try {
            \App\Jobs\LogDownloadFinish::dispatchAfterResponse((int)$log->id, $overrides);
        } catch (\Throwable $e) {
            report($e); // never echo here
        }
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
            })->with('owner:id,name,icon_bg,icon_path'); // eager load owner relationship
       $items = $q->orderByRaw("FIELD(type,'folder','file')")
        ->orderBy('name')
        ->get(['id','type','name','size','mime','updated_at','storage_path','ext','owner_id','visibility']);

        return response()->json([
            'parent' => $parent ? ['id'=>$parent->id,'name'=>$parent->name] : ['id'=>null,'name'=>'Root'],
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
        array_unshift($crumbs, ['id'=>null,'name'=>'Root']);
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
                ->pluck('user_id')
                ->unique()    
                ->values()
                ->map(fn($id) => (int)$id)
                ->all();
            $this->update(new Request([
                'visibility' => 'public',
                'members' => $members,
                'cascade' => false,
            ]), $node->id);
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
        if (DriveNode::where('parent_id', $req->parent_id)->where('name',$req->name)->where('project_id', $req->project_id)->whereNull('deleted_at')->exists()) {
            return response()->json(['message'=>'同名の項目が既に存在します'], 409);
        }
        $node = DriveNode::create([
            'id' => (string) \Str::uuid(),
            'parent_id' => $req->parent_id,
            'project_id' => $req->project_id,
            'type' => 'folder',
            'name' => $req->name,
            'owner_id' => $req->user()->id,
        ]);
        $members = ProjectMember::where('project_id', $req->input('project_id'))
            ->pluck('user_id')
            ->unique()    
            ->values()
            ->map(fn($id) => (int)$id)
            ->all();          

        $this->update(new Request([
            'visibility' => 'public',
            'members' => $members,
            'cascade' => false,
        ]), $node->id);
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
        if (DriveNode::where('parent_id', $node->parent_id)->where('name',$req->name)->where('project_id', $req->project_id)->whereNull('deleted_at')->where('id','!=',$id)->exists()) {
            return response()->json(['message'=>'同名の項目が既に存在します'], 409);
        }

        $node->name = $req->name;
        $node->save();
        return response()->json($node);
    }
    public function destroy(Request $request, string $id)
    {
        $userId = $request->user()->id;

        // gather ids recursively
        $allIds = [$id];
        $queue = [$id];

        while (!empty($queue)) {
            $children = DriveNode::where('owner_id', $userId)
                ->whereIn('parent_id', $queue)
                ->pluck('id')
                ->all();
            $queue = $children;
            $allIds = array_merge($allIds, $children);
        }

        // fetch all affected nodes
        $nodes = DriveNode::where('owner_id', $userId)
            ->whereIn('id', $allIds)
            ->get();

        // delete files from storage, soft-delete rows
        foreach ($nodes as $n) {
            if ($n->type === 'file' && $n->storage_path) {
                Storage::disk('local')->delete($n->storage_path);
            }
            $n->delete();
        }

        return response()->noContent();
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
        $img = Image::read($abs)->resize($dim, $dim);
       
        // if (!$isOriginal) {
        //     // square thumbnail; switch to scaleDown() if you want letterboxing
        //     $img = $img->resize($dim, $dim);
        // }

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
        ];
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
        $log = $this->startLog([
            'node_id' => $id,
            'action' => 'file',
            'requested_name' => $filename,
            'file_count' => 1,
            'bytes_expected' => $size,
            'manifest' => [['id' => $id, 'name' => $filename, 'size' => $size]],
        ]);
        return response()->streamDownload(function () use ($path) {
            try {
                $stream = $this->readStream($path);
                if ($stream === false) throw new \RuntimeException('Failed to read file');
                fpassthru($stream);
                @fclose($stream);
                $this->finishLog($log, ['bytes_sent' => $size, 'status' => 200]);
            } catch (\Throwable $e) {
                $this->finishLog($log, ['success' => false, 'status' => 500]);
                throw $e;
            }
        }, $filename, [
            'Content-Type'        => $mime,
            'Content-Disposition' => $this->contentDisposition($filename),
        ]);
    }

    public function downloadFolderZip(Request $request, string $id): StreamedResponse
    {
        $folder = $this->findItemOrFail($id);
        abort_unless($folder['type'] === 'folder', 404);

        $dirKey = $this->normalizeStoragePath($folder['path']);

        // If the folder itself has no storage key, try to derive it from its children.
        if ($dirKey === '') {
            $dirKey = $this->inferBaseFromChildren($folder['id']);
        }

        $zipName = $this->safeName(($folder['name'] ?: ($dirKey !== '' ? basename($dirKey) : 'folder')) . '.zip');

        // Case A: still empty after inference and no directory exists → zip an empty folder entry
        if ($dirKey === '') {
            return $this->streamZip($zipName, function (\ZipStream\ZipStream $zip) use ($folder) {
                $root = $folder['name'] ?: 'folder';
                $zip->addFile(rtrim($root, '/') . '/', ''); // empty directory marker
            });
        }
        $stats = (object)['files' => 0, 'bytes' => 0, 'manifest' => []];
        $log = $this->startLog([
            'node_id' => $id,
            'action' => 'folder_zip',
            'requested_name' => $zipName,
            'file_count' => 0, // fill at end
            'bytes_expected' => null, // fill as we go
        ]);
        // Normal case: we have a concrete base in Storage
        abort_unless($this->directoryExists($dirKey), 404);

        
        return $this->streamZip($zipName, function (\ZipStream\ZipStream $zip) use ($dirKey, $folder, $stats, $log) {
        try {
            $root = $folder['name'] ?: basename($dirKey);
            $this->addFolderToZipStorage($zip, $dirKey, $root);
        } finally {
            // zip finished (success or error thrown above)
            $this->finishLog($log, [
                'file_count' => $stats->files,
                'bytes_expected' => $stats->bytes ?: null,
                'manifest' => $stats->manifest ?: null,
                'status' => 200,
            ]);
        }
    });
    }


    public function downloadMultiZip(Request $request): StreamedResponse
    {
        $ids = (array) $request->input('ids', []);
        abort_if(empty($ids), 422, 'ids is required');

        $zipName = 'selected-' . count($ids) . '-' . now()->format('Ymd_His') . '.zip';
        $stats = (object)['files' => 0, 'bytes' => 0, 'manifest' => []];

        $log = $this->startLog([
            'node_id' => null,
            'action' => 'multi_zip',
            'requested_name' => $zipName,
        ]);
        return $this->streamZip($zipName, function (\ZipStream\ZipStream $zip) use ($ids, $stats, $log) {
            try {
                 foreach ($ids as $id) {
                    $item = $this->findItemOrFail($id);
                    $path = $this->normalizeStoragePath($item['path']);

                    if ($item['type'] === 'file') {
                        $entry = $item['name'] ?: basename($path);
                        $this->addFileToZipStorage($zip, $path, $entry);
                        continue;
                    }

                    // folder
                    if ($path === '') {
                        // Try infer from children; if still empty, add empty dir entry and continue
                        $base = $this->inferBaseFromChildren($item['id']);
                        $root = $item['name'] ?: ($base !== '' ? basename($base) : 'folder');

                        if ($base === '') {
                            $zip->addFile(rtrim($root, '/') . '/', '');
                            continue;
                        }
                        $this->addFolderToZipStorage($zip, $base, $root);
                    } else {
                        $root = $item['name'] ?: basename($path);
                        $this->addFolderToZipStorage($zip, $path, $root);
                    }
                }
            } finally {
                $this->queueFinishLog($log, [
                    'file_count' => $stats->files,
                    'bytes_expected' => $stats->bytes ?: null,
                    'manifest' => $stats->manifest ?: null,
                    'status' => 200,
                ]);
            }
           
        });
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

            $zip = new \ZipStream\ZipStream(
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
    protected function addFolderToZipStorage(\ZipStream\ZipStream $zip, string $dir, string $prefix = ''): void
    {
        $dir = $this->normalizeStoragePath($dir);
        if ($prefix !== '') $zip->addFile(rtrim($prefix, '/') . '/', ''); // empty folder visible
        $this->walkStorage($zip, $dir, $prefix);
    }

    protected function walkStorage(\ZipStream\ZipStream $zip, string $dir, string $prefix): void
    {
        $disk = $this->disk();

        foreach ($disk->files($dir) as $file) {
            $name = basename($file);
            $entry = $prefix !== '' ? $prefix . '/' . $name : $name;
            $this->addFileToZipStorage($zip, $file, $entry);
        }

        foreach ($disk->directories($dir) as $sub) {
            $name = basename($sub);
            $subPrefix = $prefix !== '' ? $prefix . '/' . $name : $name;
            $zip->addFile($subPrefix . '/', '');
            $this->walkStorage($zip, $sub, $subPrefix);
        }
    }

    protected function addFileToZipStorage(ZipStream $zip, string $path, string $entryName): void
    {
        $path = $this->normalizeStoragePath($path);
        $stream = $this->readStream($path);
        if ($stream === false) {
            return; // skip unreadable files
        }
        $zip->addFileFromStream($entryName, $stream);
        @fclose($stream);
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
        $firstPath = \App\Models\DriveNode::query()
            ->where('parent_id', $folderId)
            ->whereNotNull('storage_path')
            ->where('storage_path', '!=', '')
            ->orderBy('id')
            ->value('storage_path');

        if (!$firstPath) return ''; // no children with paths
        $firstPath = ltrim((string) $firstPath, "/\\");
        return \Illuminate\Support\Str::beforeLast($firstPath, '/'); // e.g. drive/765
    }
    public function update(Request $r, string $id)
    {
        $data = $r->validate([
            'visibility' => 'required|in:public,private',
            'members'    => 'array',
            'members.*'  => 'integer|exists:users,id',
            'cascade'    => 'boolean',
        ]);

        $node = DriveNode::with(['project:id'])->findOrFail($id);
        $this->authorize('share', $node);

        DB::transaction(function () use ($node, $data) {
            // 1) Update the node's visibility
            $node->visibility = $data['visibility'];
            $node->save();

            // 2) Replace explicit ACLs on THIS node
            DriveNodeAcl::where('node_id',$node->id)->whereNull('inherited_from')->delete();

            if ($node->visibility === 'private') {
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
                ->with('members:id,name,email')
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
}
