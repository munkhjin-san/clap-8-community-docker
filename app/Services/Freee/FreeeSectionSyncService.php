<?php

namespace App\Services\Freee;

use App\Models\FreeeCredential;
use App\Models\ProjectRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Normalizer;

/**
 * プロジェクト ⇄ freee会計の部門（Section）の連携。
 *
 * 重複を作らないための前提:
 *  - freeeの部門APIに「重複を防ぐキー」は無い。`/sections/code/upsert` は code をキーに
 *    するが、既存部門552件すべて code が NULL だったため、upsertでは既存部門に一致せず
 *    新規作成になってしまう（＝重複を量産する）。よって upsert は使わない。
 *  - 実質的なキーは name。プロジェクト名・部門名ともに重複が無いことを確認済み。
 *  - よって「まず名前で突き合わせて紐付け、見つからないときだけ作成」する。
 *  - 突き合わせは必ず最新の一覧に対して行う（キャッシュ済みの古い一覧で判断すると、
 *    その間に作られた部門を見落として重複を作る）。
 */
class FreeeSectionSyncService
{
    public const RESULT_LINKED = 'linked';

    public const RESULT_CREATED = 'created';

    public function __construct(private readonly FreeeAccountingClient $accounting) {}

    /**
     * 連携を実行する。既存部門があれば紐付けるだけで、freeeへの書き込みは行わない。
     *
     * @return array{result: string, section_id: int, section_name: string, message: string}
     */
    public function sync(FreeeCredential $credential, ProjectRecord $project): array
    {
        $name = trim((string) $project->name);

        if ($name === '') {
            throw ValidationException::withMessages([
                'message' => 'プロジェクト名が空のため連携できません。',
            ]);
        }

        // 書き込み判断は必ず最新の一覧で行う。
        $sections = $this->accounting->sections($credential, fresh: true);

        if ($existing = $this->findByExactName($sections, $name)) {
            $this->link($project, (int) $existing['id']);

            return [
                'result' => self::RESULT_LINKED,
                'section_id' => (int) $existing['id'],
                'section_name' => (string) ($existing['name'] ?? ''),
                'message' => '既存の部門と紐付けました（freeeへの登録は行っていません）。',
            ];
        }

        // 全角/半角違いだけの部門が既にある場合は、どちらが正しいか機械では決められない。
        // 勝手に作ると既存の重複をさらに増やすので、人が判断するまで止める。
        $twins = $this->findNormalizedTwins($sections, $name);

        if ($twins !== []) {
            throw ValidationException::withMessages([
                'message' => 'freeeに表記が似た部門が既に存在します（'
                    .collect($twins)->pluck('name')->take(3)->implode('、')
                    .'）。同じ部門なら freee 側で名称を「'.$name.'」に統一してから再実行してください。',
            ]);
        }

        if (mb_strlen($name) > FreeeAccountingClient::SECTION_NAME_MAX) {
            throw ValidationException::withMessages([
                'message' => 'プロジェクト名が'.FreeeAccountingClient::SECTION_NAME_MAX
                    .'文字を超えているため、freeeの部門として登録できません。名称を短くしてください。',
            ]);
        }

        $created = $this->accounting->createSection($credential, $name);

        if (! filled($created['id'] ?? null)) {
            throw ValidationException::withMessages([
                'message' => 'freeeが部門IDを返しませんでした。freee側の部門一覧を確認してください。',
            ]);
        }

        $this->link($project, (int) $created['id']);

        Log::info('freee section created for project.', [
            'project_id' => $project->id,
            'section_id' => $created['id'],
            'name' => $name,
        ]);

        return [
            'result' => self::RESULT_CREATED,
            'section_id' => (int) $created['id'],
            'section_name' => (string) ($created['name'] ?? $name),
            'message' => 'freeeに部門を新規登録しました。',
        ];
    }

    /**
     * 連携先が実在するか、名称がずれていないかを確認する。
     *
     * @return array{exists: bool, section_id: int, section_name: string|null, name_matches: bool, message: string}
     */
    public function check(FreeeCredential $credential, ProjectRecord $project): array
    {
        if (! $project->isFreeeSynced()) {
            throw ValidationException::withMessages([
                'message' => 'このプロジェクトはfreeeと連携していません。',
            ]);
        }

        $sectionId = (int) $project->freee_section_id;
        $section = $this->accounting->section($credential, $sectionId);

        if ($section === null) {
            return [
                'exists' => false,
                'section_id' => $sectionId,
                'section_name' => null,
                'name_matches' => false,
                'message' => '部門ID '.$sectionId.' はfreeeに存在しません。連携を解除して再連携してください。',
            ];
        }

        $sectionName = (string) ($section['name'] ?? '');
        $matches = $sectionName === trim((string) $project->name);

        $project->forceFill(['freee_synced_at' => now()])->save();

        return [
            'exists' => true,
            'section_id' => $sectionId,
            'section_name' => $sectionName,
            'name_matches' => $matches,
            'message' => $matches
                ? '部門「'.$sectionName.'」と正しく連携しています。'
                : '連携先は存在しますが名称が異なります（freee:「'.$sectionName.'」/ 当システム:「'.$project->name.'」）。',
        ];
    }

    /**
     * 連携解除。freeeへのDELETEは行わず、こちらの紐付けだけを外す。
     */
    public function unlink(ProjectRecord $project): void
    {
        $project->forceFill([
            'freee_section_id' => null,
            'freee_synced_at' => null,
        ])->save();

        Log::info('freee section link removed (freee side untouched).', [
            'project_id' => $project->id,
        ]);
    }

    /**
     * 紐付けを保存する。同じ部門を2つのプロジェクトに割り当てさせない。
     */
    private function link(ProjectRecord $project, int $sectionId): void
    {
        DB::transaction(function () use ($project, $sectionId) {
            $taken = ProjectRecord::query()
                ->where('freee_section_id', $sectionId)
                ->whereKeyNot($project->getKey())
                ->lockForUpdate()
                ->first();

            if ($taken) {
                throw ValidationException::withMessages([
                    'message' => 'この部門は既にプロジェクト「'.$taken->name.'」と連携しています。',
                ]);
            }

            $project->forceFill([
                'freee_section_id' => $sectionId,
                'freee_synced_at' => now(),
            ])->save();
        });
    }

    private function findByExactName(array $sections, string $name): ?array
    {
        foreach ($sections as $section) {
            if ((string) ($section['name'] ?? '') === $name) {
                return $section;
            }
        }

        return null;
    }

    /**
     * 全角/半角・空白・大小の違いを畳んだうえで一致する部門。
     * freeeには既にこの手の重複が17組存在するため、作成前に必ず確認する。
     */
    private function findNormalizedTwins(array $sections, string $name): array
    {
        $needle = $this->normalize($name);

        return array_values(array_filter(
            $sections,
            fn (array $section) => $this->normalize((string) ($section['name'] ?? '')) === $needle,
        ));
    }

    private function normalize(string $value): string
    {
        if (class_exists(Normalizer::class)) {
            $value = Normalizer::normalize($value, Normalizer::FORM_KC) ?: $value;
        }

        return mb_strtolower(preg_replace('/\s+/u', '', $value) ?? $value);
    }
}
