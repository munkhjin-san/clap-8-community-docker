<?php

namespace App\Services\Learning;

use App\Models\LessonExam;
use App\Models\LessonMaterial;
use App\Models\LessonMaterialVersion;
use App\Models\LessonPortfolio;

class LearningParticipantProgressService
{
    private const MATERIAL_TYPE_BASIC = '基礎知識';

    private const MATERIAL_TYPE_CASE_STUDY = 'ケーススタディ';

    public function __construct(
        private LearningProgressService $learningProgressService
    ) {
    }

    public function caseStudyRows(int $themeId): array
    {
        $lessons = LessonMaterial::where('lesson_theme_id', $themeId)
            ->with('answers.user')
            ->get();

        $rows = [];

        foreach ($lessons as $lesson) {
            foreach ($lesson->answers as $answer) {
                $userId = (int) $answer->user_id;

                if (! isset($rows[$userId])) {
                    $rows[$userId] = [
                        'user' => $answer->user,
                        'answers' => [],
                        'cant_understand' => '',
                        'reason_dnt_und' => '',
                        'progress' => null,
                    ];
                }

                if ($lesson->material_type === self::MATERIAL_TYPE_BASIC) {
                    $rows[$userId]['cant_understand'] = $answer->cant_understand;
                    $rows[$userId]['reason_dnt_und'] = $answer->reason_dnt_und;
                } elseif ($lesson->material_type === self::MATERIAL_TYPE_CASE_STUDY) {
                    $rows[$userId]['answers'][$lesson->id] = [
                        'title' => $lesson->title,
                        'answer' => $answer->answer,
                    ];
                }
            }
        }

        $progressByUser = $this->learningProgressService->forThemeUsers($themeId, array_keys($rows));

        foreach ($rows as $userId => &$row) {
            $row['answers'] = array_values($row['answers']);
            $row['progress'] = $progressByUser[(int) $userId] ?? null;
        }
        unset($row);

        return $rows;
    }

    /**
     * Per-section (per-material) exam summary for a portfolio theme.
     *
     * Material exams live on individual sections, and attempts are keyed by
     * (exam, user) — not by portfolio attempt — so results are summarised once
     * per user. Returns the master list of the default version's section exams
     * plus a per-user results map (user_id => material_id => summary).
     */
    public function portfolioSectionExams(int $themeId): array
    {
        $defaultVersionId = LessonMaterialVersion::where('lesson_theme_id', $themeId)
            ->where('is_default', true)
            ->value('id');

        $exams = LessonExam::whereNotNull('lesson_material_id')
            ->whereHas('material', function ($q) use ($themeId, $defaultVersionId) {
                $q->where('lesson_theme_id', $themeId);
                if ($defaultVersionId) {
                    $q->where('lesson_material_version_id', $defaultVersionId);
                }
            })
            ->with(['material:id,title', 'attempts'])
            ->orderBy('id')
            ->get();

        $sectionExams = [];
        $results = [];

        foreach ($exams as $exam) {
            $materialId = (int) $exam->lesson_material_id;

            $sectionExams[] = [
                'material_id' => $materialId,
                'title' => $exam->material?->title,
                'passing_score' => (int) $exam->passing_score,
                'max_attempts' => (int) $exam->max_attempts,
            ];

            foreach ($exam->attempts->groupBy('user_id') as $userId => $attempts) {
                $latest = $attempts->sortByDesc('attempt_number')->first();
                $results[(int) $userId][$materialId] = [
                    'attempt_count' => $attempts->count(),
                    'latest_score' => $latest?->score,
                    'latest_status' => $latest?->status,
                    'passed' => $attempts->contains(fn ($attempt) => $attempt->status === 'passed'),
                ];
            }
        }

        return [
            'section_exams' => $sectionExams,
            'results' => $results,
        ];
    }

    public function portfolioRows(int $themeId)
    {
        // Multi-path / multi-attempt: return every attempt per user (path & attempt_no
        // are appended on the model), grouped by user and ordered chronologically.
        return LessonPortfolio::where('lesson_theme_id', $themeId)
            ->with('user')
            ->with('lesson_sections')
            ->with('salaryIssue')
            ->with(['lesson_form' => function ($q) use ($themeId) {
                $q->where('lesson_theme_id', $themeId);
            }])
            ->orderBy('user_id')
            ->orderBy('attempt_no')
            ->orderBy('id')
            ->get();
    }
}
