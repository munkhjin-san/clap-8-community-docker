<?php

namespace App\Services\Learning;

use App\Models\LessonMaterial;
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
