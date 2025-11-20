<?php

namespace App\Http\Controllers;

use App\Models\LessonExam;
use App\Models\LessonExamAnswer;
use App\Models\LessonExamAttempt;
use App\Models\LessonExamOption;
use App\Models\LessonExamQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LessonExamController extends Controller
{
    public function get_exam(Request $request)
    {
        $request->validate([
            'lesson_theme_id' => 'required|exists:lesson_themes,id',
        ]);

        $exam = LessonExam::with(['questions.options'])
            ->where('lesson_theme_id', $request->lesson_theme_id)
            ->first();

        return response()->json([
        'exists' => (bool) $exam,
        'exam'   => $exam,
    ]);
    }

    public function save_exam(Request $request)
    {
        $validated = $request->validate([
            'lesson_theme_id' => 'required|exists:lesson_themes,id',
            'exam_id' => 'nullable|exists:lesson_exams,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'passing_score' => 'required|integer|min:1|max:100',
            'max_attempts' => 'required|integer|min:1|max:10',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|exists:lesson_exam_questions,id',
            'questions.*.prompt' => 'required|string',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.correct_explanation' => 'nullable|string',
            'questions.*.position' => 'nullable|integer|min:0',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*.id' => 'nullable|exists:lesson_exam_options,id',
            'questions.*.options.*.label' => 'required|string',
            'questions.*.options.*.is_correct' => 'boolean',
        ]);

        foreach ($validated['questions'] as $question) {
            $hasCorrect = collect($question['options'])->contains(function ($option) {
                return !empty($option['is_correct']);
            });
            if (!$hasCorrect) {
                throw ValidationException::withMessages([
                    'questions' => ['Each question must have at least one correct answer.'],
                ]);
            }
        }

        $userId = Auth::id();

        $exam = DB::transaction(function () use ($validated, $userId) {
            $payload = [
                'lesson_theme_id' => $validated['lesson_theme_id'],
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
                'passing_score' => $validated['passing_score'],
                'max_attempts' => $validated['max_attempts'],
            ];

            if (!empty($validated['exam_id'])) {
                $payload['updated_by'] = $userId;
            } else {
                $payload['created_by'] = $userId;
            }

            $exam = LessonExam::updateOrCreate(
                ['id' => $validated['exam_id'] ?? null],
                $payload
            );

            $questionIds = [];

            foreach ($validated['questions'] as $index => $questionData) {
                $question = LessonExamQuestion::updateOrCreate(
                    ['id' => $questionData['id'] ?? null],
                    [
                        'lesson_exam_id' => $exam->id,
                        'prompt' => $questionData['prompt'],
                        'explanation' => $questionData['explanation'] ?? null,
                        'correct_explanation' => $questionData['correct_explanation'] ?? null,
                        'position' => $questionData['position'] ?? $index,
                    ]
                );

                $questionIds[] = $question->id;
                $optionIds = [];

                foreach ($questionData['options'] as $optionData) {
                    $option = LessonExamOption::updateOrCreate(
                        ['id' => $optionData['id'] ?? null],
                        [
                            'lesson_exam_question_id' => $question->id,
                            'label' => $optionData['label'],
                            'is_correct' => !empty($optionData['is_correct']),
                        ]
                    );
                    $optionIds[] = $option->id;
                }

                LessonExamOption::where('lesson_exam_question_id', $question->id)
                    ->whereNotIn('id', $optionIds)
                    ->delete();
            }

            LessonExamQuestion::where('lesson_exam_id', $exam->id)
                ->whereNotIn('id', $questionIds)
                ->delete();

            return $exam->fresh(['questions.options']);
        });

        return response()->json($exam);
    }

    public function delete_exam(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:lesson_exams,id',
        ]);

        LessonExam::where('id', $request->exam_id)->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function get_learning_exam(Request $request)
    {
        $request->validate([
            'lesson_theme_id' => 'required|exists:lesson_themes,id',
        ]);

        $exam = LessonExam::where('lesson_theme_id', $request->lesson_theme_id)->first();

        if (!$exam) {
            return response()->json([
                'exam' => null,
                'attempts' => [],
                'remaining_attempts' => 0,
                'final_attempt_answers' => [],
                'reveal_answers' => false,
            ]);
        }

        $userId = Auth::id();
        $attempts = LessonExamAttempt::where('lesson_exam_id', $exam->id)
            ->where('user_id', $userId)
            ->orderByDesc('attempt_number')
            ->get();

        $remainingAttempts = max($exam->max_attempts - $attempts->count(), 0);
        $revealAnswers = $remainingAttempts === 0;

        $questionSelect = ['id', 'lesson_exam_id', 'prompt', 'explanation', 'position'];
        if ($revealAnswers) {
            $questionSelect[] = 'correct_explanation';
        }

        $optionSelect = ['id', 'lesson_exam_question_id', 'label'];
        if ($revealAnswers) {
            $optionSelect[] = 'is_correct';
        }

        $exam->load([
            'questions' => function ($query) use ($questionSelect) {
                $query->select($questionSelect)->orderBy('position');
            },
            'questions.options' => function ($query) use ($optionSelect) {
                $query->select($optionSelect);
            },
        ]);

        $finalAnswers = [];
        if ($revealAnswers) {
            $latestAttempt = $attempts->first();
            if ($latestAttempt) {
                $finalAnswers = LessonExamAnswer::where('lesson_exam_attempt_id', $latestAttempt->id)
                    ->get(['lesson_exam_question_id', 'lesson_exam_option_id', 'is_correct'])
                    ->map(function ($answer) {
                        return [
                            'question_id' => $answer->lesson_exam_question_id,
                            'option_id' => $answer->lesson_exam_option_id,
                            'is_correct' => (bool) $answer->is_correct,
                        ];
                    })
                    ->values()
                    ->all();
            }
        }

        return response()->json([
            'exam' => $exam,
            'attempts' => $attempts,
            'remaining_attempts' => $remainingAttempts,
            'final_attempt_answers' => $finalAnswers,
            'reveal_answers' => $revealAnswers,
        ]);
    }

    public function get_exam_attempts(Request $request)
    {
        $request->validate([
            'lesson_theme_id' => 'required|exists:lesson_themes,id',
        ]);

        $exam = LessonExam::where('lesson_theme_id', $request->lesson_theme_id)->first();

        if(!$exam){
            return response()->json([]);
        }

        $attempts = LessonExamAttempt::with(['user'])
            ->where('lesson_exam_id', $exam->id)
            ->orderByDesc('submitted_at')
            ->get();

        return response()->json($attempts);
    }

    public function submit_exam(Request $request)
    {
        $validated = $request->validate([
            'lesson_theme_id' => 'required|exists:lesson_themes,id',
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|exists:lesson_exam_questions,id',
            'answers.*.option_id' => 'required|exists:lesson_exam_options,id',
        ]);

        $exam = LessonExam::where('lesson_theme_id', $validated['lesson_theme_id'])
            ->with(['questions.options'])
            ->firstOrFail();

        $userId = Auth::id();
        $previousAttempts = LessonExamAttempt::where('lesson_exam_id', $exam->id)
            ->where('user_id', $userId)
            ->count();

        if ($previousAttempts >= $exam->max_attempts) {
            throw ValidationException::withMessages([
                'answers' => ['Maximum number of attempts reached.'],
            ]);
        }

        $questions = $exam->questions;
        if ($questions->count() !== count($validated['answers'])) {
            throw ValidationException::withMessages([
                'answers' => ['All questions must be answered.'],
            ]);
        }

        $answersByQuestion = collect($validated['answers'])->keyBy('question_id');
        $correctCount = 0;

        foreach ($questions as $question) {
            if (!$answersByQuestion->has($question->id)) {
                throw ValidationException::withMessages([
                    'answers' => ['All questions must be answered.'],
                ]);
            }

            $answer = $answersByQuestion->get($question->id);
            $option = $question->options->firstWhere('id', $answer['option_id']);

            if (!$option) {
                throw ValidationException::withMessages([
                    'answers' => ['Invalid answer submitted.'],
                ]);
            }

            if ($option->is_correct) {
                $correctCount++;
            }
        }

        $scorePercent = (int) round(($correctCount / max($questions->count(), 1)) * 100);
        $status = $scorePercent >= $exam->passing_score ? 'passed' : 'failed';

        $attempt = LessonExamAttempt::create([
            'lesson_exam_id' => $exam->id,
            'user_id' => $userId,
            'score' => $scorePercent,
            'attempt_number' => $previousAttempts + 1,
            'status' => $status,
            'submitted_at' => now(),
        ]);

        foreach ($questions as $question) {
            $answer = $answersByQuestion->get($question->id);
            $option = $question->options->firstWhere('id', $answer['option_id']);

            LessonExamAnswer::create([
                'lesson_exam_attempt_id' => $attempt->id,
                'lesson_exam_question_id' => $question->id,
                'lesson_exam_option_id' => $option->id,
                'is_correct' => $option->is_correct,
            ]);
        }

        return response()->json([
            'status' => $status,
            'score' => $scorePercent,
            'attempt_number' => $attempt->attempt_number,
            'remaining_attempts' => max($exam->max_attempts - $attempt->attempt_number, 0),
        ]);
    }
}
