<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\questionAndAnswerRecord;
use App\Models\qandaTagRecord;
use App\Models\qandaKeyWordRecord;
use App\Models\SupportMailFormRecord;
use App\Models\SupportMailRespondingLog;
use App\Models\RegulationRecord;
use App\Models\FileRecord;
use App\Models\RegulationFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;
use OpenAI;

class SupportController extends Controller
{
    private function active_user(){
        $sub = Auth::user()->linked()->where('main_id', Auth::id())->wherePivot('active', 1)->first();
        if($sub){
            return $sub;
        }else{
            return Auth::user();
        }
    }
    public function support_record_list(Request $request){
        $record_list = questionAndAnswerRecord::where('deleted_flag','=', 0)->with(['qanda_use_tags' => function($q){
            $q->where('deleted_flag','=', 0)->with(['qanda_tag_records' => function($q){
                $q->where('deleted_flag','=', 0);
            }]);
        }])->orderBy('created_at', 'desc')->get();


        $tag_list = qandaTagRecord::where('deleted_flag','=', 0)->with(['tags_use_qanda' => function($q){
            $q->where('deleted_flag','=', 0)->count('useful_count');
        }])->orderBy('useful_count', 'desc')->get();
        
        $key_word_list = qandaKeyWordRecord::where('deleted_flag','=', 0)->with(['key_words_use_qanda' => function($q){
            $q->where('deleted_flag','=', 0)->count('useful_count');
        }])->orderBy('useful_count', 'desc')->get();


        $record_dates_array = array("record_list" => $record_list, "tag_list" => $tag_list, "key_word_list" => $key_word_list);
        
        return response()->json($record_dates_array);

    }
    public function support_feedback(Request $request){
        $create = SupportMailFormRecord::create([
            "user_id" => Auth::id(),
            "kind_value" => $request->kind_value,
            "contact_address" => $request->contact_address,
            "consultation_content" => $request->consultation_content,
        ]);
        return response()->json($create);
    }
    public function support_resolve_decision(Request $request){
        $incement = questionAndAnswerRecord::findOrFail($request->id)->increment('useful_count');
        return response()->json($incement);
    }
    public function support_add_consult(Request $request){
        $user_id = $this->active_user()->id;
        $create = SupportMailFormRecord::create([
            "user_id" => $user_id,
            "kind_value" => $request->kind_value,
            "contact_address" => $request->contact_address,
            "consultation_content" => $request->consultation_content,
        ]);
        return response()->json($create);
    }
    public function get_recieved_consults(){

        
        $user_id = $this->active_user()->id;
        $has_privilage = in_array($user_id, [610, 608, 516, 517, 519, 518, 526, 494]);
        $record_list = supportMailFormRecord::where('deleted_flag','=', 0)
        ->when(!$has_privilage, function($q){
            $q->where('user_id', Auth::id());
        })
        ->with('user')
        ->with(['support_mail_responding_logs' => function($q){
            $q->where('deleted_flag','=', 0)->orderBy('created_at', 'desc')->with('user');
        }])->orderBy('created_at', 'desc')->get();
        return response()->json($record_list);
    }
    public function add_memo_to_consult(Request $request){
        $user_id = $this->active_user()->id;
        $create = SupportMailRespondingLog::create([
            "user_id" => $user_id,
            "text" => $request->text,
            "record_id" => $request->record_id,
        ]);
        return response()->json($create);
    }
    public function update_consult_status(Request $request){
        $update = SupportMailFormRecord::findOrFail($request->record_id)->update([
            "status_flag" => $request->value
        ]);
        return response()->json($update);
    }
    public function support_add_message(Request $request){
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);
        $message = $request->input('message');

        $apiKey = config('services.openai.api_key');
        $client = OpenAI::client($apiKey);
        $response = $client->responses()->create([
            'model' => 'gpt-4o-mini',
            'tools' => [
                [
                    'type' => 'file_search',
                    'vector_store_ids' => ["vs_68a7c6b10f048191b5fa9cd63fefefde"],
                ]
            ],
            'input' => $message,
            'store' => true,
            'metadata' => [
                'user_id' => '123',
                'session_id' => 'abc456'
            ]
        ]);

        $reply = '';
        if($response->status !== 'completed') {
            $reply = '申し訳ございませんが現在、リクエストを処理できません。後でもう一度お試しください。';
        } else {
            foreach ($response->output as $output) {
                if(isset($output['role']) && $output['role'] === 'assistant') {
                    foreach ($output['content'] as $content) {
                        $reply .= $content['text'];
                    }
                }
                
            }
        }
        return response()->json(['reply' => $reply]);
    }

    // Regulation methods
    public function get_regulations(Request $request)
    {
   
        // $vectorStoreId = 'vs_68a7c6b10f048191b5fa9cd63fefefde';
        // $apiKey = config('services.openai.api_key');
        // $client = OpenAI::client($apiKey);
        // $files_list = $client->vectorStores()->files()->list($vectorStoreId);
        $regulations = RegulationRecord::with(['user', 'regulation_files'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($regulations);

    }

    public function save_regulation(Request $request)
    {

            $request->validate([
                'title' => 'required|string',
                'content' => 'required|string',
            ]);


            $user_id = $this->active_user()->id;
            $id = $request->id ?? null;
            $regulation = RegulationRecord::updateOrCreate(
                ['id' => $id],
                [
                    'user_id' => $user_id,
                    'title' => $request->title,
                    'content' => $request->content,
                ]
            );

            $files = $request['regulation_files'] ?? [];
           
                
            $vectorStoreId = 'vs_68a7c6b10f048191b5fa9cd63fefefde';
            $apiKey = config('services.openai.api_key');
            $client = OpenAI::client($apiKey);
            $files_list = $client->vectorStores()->files()->list($vectorStoreId);
            $v_file_ids_in_store = array_map(fn($file) => $file->id, $files_list->data);

            $current_files = $regulation->regulation_files()->pluck('id')->toArray();
            $files_to_detach = array_diff($current_files, array_map(fn($file) => $file['id'], $files));
            if(!empty($files_to_detach)){
                $remove_targets = RegulationFile::whereIn('id', $files_to_detach)->get();
                foreach ($remove_targets as $removeFileRecord) {
                    if($removeFileRecord->vector_file_id){
                        $this->delete_from_vector_store($removeFileRecord->vector_file_id, $client, $vectorStoreId);
                    }
                    $removeFileRecord->delete();
                }
            }

            foreach ($files as $file) {
                
                if (isset($file['id'])) {
                    $fileRecord = RegulationFile::find($file['id']);
                    if ($fileRecord) {
                        // dd($file);
                        
                        if (isset($file['chat_supported'])) {
                            $fileRecord->chat_supported = $file['chat_supported'];
                            $is_exist_in_store = $fileRecord->vector_file_id && in_array($fileRecord->vector_file_id, $v_file_ids_in_store);
                            if(!$is_exist_in_store){
                                $vector_id = $this->save_into_vector_store($fileRecord, $client, $vectorStoreId);
                                $fileRecord->vector_file_id = $vector_id;
                                $fileRecord->save();
                            }
                        }
                    }
                }
                $fileRecord->regulation_record_id = $regulation->id;
                $fileRecord->save();
            }

            return response()->json([
                'success' => true,
                'message' => $request->id ? 'Regulation updated successfully' : 'Regulation created successfully',
                'data' => $regulation,
            ]);


    }
    private function save_into_vector_store(RegulationFile $fileRecord, $client, $vectorStoreId)
    {
        $relPath = "/regulation_files/{$fileRecord['path']}.{$fileRecord['extension']}";
        if(file_exists(storage_path('app' . $relPath))) {
            $absPath = Storage::disk('local')->path($relPath);
            $file = $client->files()->upload([
                'purpose' => 'assistants',
                'file'    => fopen($absPath, 'r'),
            ]);
            $vsFile = $client->vectorStores()->files()->create(
                vectorStoreId: $vectorStoreId,
                parameters: ['file_id' => $file->id]
            );
            $status = $vsFile->status;
            $tries  = 20;
            while ($status === 'in_progress' && $tries-- > 0) {
                sleep(1);
                $check = $client->vectorStores()->files()->retrieve(
                    vectorStoreId: $vectorStoreId,
                    fileId: $vsFile->id
                );
                $status = $check->status;
            }
            if ($status === 'completed') {
                // Update pivot table with vector_file_id
                $fileRecord->vector_file_id = $vsFile->id;
                $fileRecord->save();
                return $vsFile->id;
            } else {
                // Handle failure case if needed
                return null;
            }
        }
        return null;
    }
    private function delete_from_vector_store($vector_file_id, $client, $vectorStoreId)
    {
        try {
            $client->vectorStores()->files()->delete(
                vectorStoreId: $vectorStoreId,
                fileId: $vector_file_id
            );
            $client->files()->delete($vector_file_id);
        } catch (\Exception $e) {
            // Log the error or handle it as needed
        }
    }
    public function delete_regulation(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer|exists:regulation_records,id'
            ]);

            DB::beginTransaction();

            $regulation = RegulationRecord::findOrFail($request->id);
            
            // Soft delete the regulation
            $regulation->delete();

            $regulationFiles = $regulation->regulation_files;
            if($regulationFiles){
                $vectorStoreId = 'vs_68a7c6b10f048191b5fa9cd63fefefde';
                $apiKey = config('services.openai.api_key');
                $client = OpenAI::client($apiKey);
                foreach ($regulationFiles as $file) {
                    if($file->vector_file_id){
                        $this->delete_from_vector_store($file->vector_file_id, $client, $vectorStoreId);
                    }
                    $file->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Regulation deleted successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete regulation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function regulation_file_upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // Max 50MB
        ]);

        $file = $request->file('file');
        $path = '/regulation_files';
        $file_path = date("YmdHis") . md5(uniqid());           
        $file_extension = $file->getClientOriginalExtension();
        $file_real_name = $file->getClientOriginalName();            
        $mime_type = $file->getMimeType();
        $mime_type_array = explode('/',$mime_type);
        $file_type = $mime_type_array[0];            
        $file_size = $file->getSize();

        $fileRecord = RegulationFile::create([
            'path' => $file_path,
            'name' => $file_real_name,
            'mime_type' => $file_type,
            'extension' => $file_extension,
            'size' => $file_size,
        ]);

        $set_path = $file_path . '.' . $file_extension;
        $thumbnail_path = 'thumbnail/' . $file_path . '_thumbnail.webp';
        $height = 130;
        if($file_type == 'image' && $file_extension !== 'svg'){
            $img = Image::read($file);
                
            File::isDirectory(storage_path('app') . $path) or File::makeDirectory(storage_path('app') . '/' . $path, 0755, true, true);                      
            $img->save(storage_path('app') . $path .'/'. $set_path, 30);  
            File::isDirectory(storage_path('app') . $path .'/thumbnail') or File::makeDirectory(storage_path('app') . '/' . $path .'/thumbnail', 0755, true, true);
            $thumbnail = $img->scale(height: 130);  
            $thumbnail->toWebp()->save(storage_path('app') . $path .'/'. $thumbnail_path);
        }else{
            Storage::disk('local')->putFileAs(
                $path, $file, $set_path
            );
        }
        $sizeAfter = File::size(storage_path('app' . $path . '/' . $set_path));
    
        // $fileRecord->size = $sizeAfter;
        $fileRecord->update(['size' => $sizeAfter]);

        return response()->json($fileRecord);
    }
}
