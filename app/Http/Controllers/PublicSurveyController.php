<?php

namespace App\Http\Controllers;

use App\Models\CustomForm;
use App\Models\SurveyAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicSurveyController extends Controller
{
    public function show(string $token)
    {
        $this->findPublicFormOrFail($token);

        return view('public-survey', [
            'publicToken' => $token,
        ]);
    }

    public function data(string $token): JsonResponse
    {
        $form = $this->findPublicFormOrFail($token);

        abort_if(
            $form->blocks->contains(fn ($block) => $block->type === 'file'),
            422,
            '公開フォームではファイル質問をまだ利用できません。'
        );

        return response()->json($form);
    }

    public function submit(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'custom_form_id' => 'required|integer',
            'params' => 'required|array',
            'params.*.text_answer' => 'nullable|string',
            'params.*.custom_form_block_id' => 'required|integer',
            'params.*.files' => 'nullable|array',
            'params.*.element_answers' => 'nullable|array',
            'params.*.element_answers.*.custom_form_block_element_id' => 'required|integer',
            'params.*.element_answers.*.sub_text' => 'nullable|string',
            'params.*.element_answers.*.checked' => 'required|boolean',
            'status' => 'required|integer|in:2',
            'target_date' => 'nullable|date',
        ]);

        $form = $this->findPublicFormOrFail($token);
        abort_if(
            (int) $validated['custom_form_id'] !== (int) $form->id,
            422,
            '無効なフォームです。'
        );
        abort_if(
            $form->blocks->contains(fn ($block) => $block->type === 'file'),
            422,
            '公開フォームではファイル質問をまだ利用できません。'
        );
        abort_if(
            $form->repeat_setting == 1 && !Arr::get($validated, 'target_date'),
            422,
            '対象月を選択してください。'
        );

        $allowedBlockIds = $form->blocks->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allowedElementIds = $form->blocks
            ->flatMap(fn ($block) => $block->elements->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($validated['params'] as $block) {
            abort_if(
                !in_array((int) $block['custom_form_block_id'], $allowedBlockIds, true),
                422,
                '無効な質問データです。'
            );

            foreach (Arr::get($block, 'element_answers', []) as $element) {
                abort_if(
                    !in_array((int) $element['custom_form_block_element_id'], $allowedElementIds, true),
                    422,
                    '無効な選択肢データです。'
                );
            }
        }

        $guestUuid = $this->resolveGuestUuid($request);

        $survey = DB::transaction(function () use ($form, $validated, $guestUuid) {
            $survey = SurveyAnswer::create([
                'status' => (int) $validated['status'],
                'custom_form_id' => $form->id,
                'user_id' => null,
                'target_date' => $form->repeat_setting == 1 ? Arr::get($validated, 'target_date') : null,
                'guest_uuid' => $guestUuid,
            ]);

            foreach ($validated['params'] as $block) {
                $blockAnswer = $survey->block_answers()->create([
                    'user_id' => null,
                    'text_answer' => (string) Arr::get($block, 'text_answer', ''),
                    'custom_form_block_id' => (int) $block['custom_form_block_id'],
                ]);

                foreach (Arr::get($block, 'element_answers', []) as $element) {
                    $blockAnswer->element_answers()->create([
                        'user_id' => null,
                        'custom_form_block_element_id' => (int) $element['custom_form_block_element_id'],
                        'sub_text' => Arr::get($element, 'sub_text'),
                        'checked' => (bool) $element['checked'],
                    ]);
                }
            }

            return $survey;
        });

        return response()->json([
            'id' => $survey->id,
            'prize_eligible' => false,
        ]);
    }

    private function findPublicFormOrFail(string $token): CustomForm
    {
        return CustomForm::query()
            ->where('public_token', $token)
            ->where('is_public', true)
            ->where('usage', 'general')
            ->where('status', 0)
            ->with([
                'blocks' => fn ($query) => $query->with('elements'),
            ])
            ->firstOrFail();
    }

    private function resolveGuestUuid(Request $request): string
    {
        $key = 'public_survey_guest_uuid';
        $guestUuid = $request->session()->get($key);

        if (!$guestUuid) {
            $guestUuid = (string) Str::uuid();
            $request->session()->put($key, $guestUuid);
        }

        return $guestUuid;
    }
}
