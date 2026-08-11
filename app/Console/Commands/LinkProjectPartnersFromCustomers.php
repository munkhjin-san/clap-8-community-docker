<?php

namespace App\Console\Commands;

use App\Models\PartnerRecord;
use App\Models\ProjectRecord;
use Illuminate\Console\Command;
use Normalizer;

/**
 * 既存の「顧客企業（正式名称）」(project_records.customers) を取引先マスタへ写し替える。
 *
 * customers は手入力の文字列配列で、取引先マスタとは名前でしか突き合わせられない。
 * 完全一致 → 表記ゆれを畳んだ一致（全角半角・空白・大小・株式会社の略記）の順で探し、
 * それでも見つからないものは **作らずに報告する**。取引先マスタは請求に使う台帳なので、
 * 綴りの怪しい行を自動で増やす方が高くつく。
 *
 * customers 自体は消さない（当面併存させる想定のため）。
 */
class LinkProjectPartnersFromCustomers extends Command
{
    protected $signature = 'projects:link-partners-from-customers
        {--dry-run : 保存せず、一致・不一致の内訳だけ表示する}
        {--detach : 先に既存の紐付けを外してから張り直す}';

    protected $description = '顧客企業（正式名称）の文字列を取引先マスタに突き合わせてプロジェクトへ紐付ける';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // 名前 => id の索引を2種類作る（完全一致用と、表記ゆれを畳んだ用）。
        $partners = PartnerRecord::query()->get(['id', 'name']);
        $exact = [];
        $loose = [];
        foreach ($partners as $partner) {
            $name = trim((string) $partner->name);
            $exact[$name] ??= $partner->id;
            $loose[$this->loosen($name)] ??= $partner->id;
        }

        $projects = ProjectRecord::query()->get(['id', 'name', 'customers']);

        $matchedExact = 0;
        $matchedLoose = 0;
        $unmatched = [];
        $plan = [];
        $projectsWithCustomers = 0;

        foreach ($projects as $project) {
            $customers = array_filter(array_map('trim', (array) ($project->customers ?? [])));
            if ($customers === []) {
                continue;
            }
            $projectsWithCustomers++;

            $ids = [];
            foreach ($customers as $customer) {
                if (isset($exact[$customer])) {
                    $ids[] = $exact[$customer];
                    $matchedExact++;

                    continue;
                }

                $key = $this->loosen($customer);
                if (isset($loose[$key])) {
                    $ids[] = $loose[$key];
                    $matchedLoose++;

                    continue;
                }

                $unmatched[$customer] = ($unmatched[$customer] ?? 0) + 1;
            }

            if ($ids !== []) {
                $plan[$project->id] = array_values(array_unique($ids));
            }
        }

        $this->table(
            ['顧客企業あり', '完全一致', '表記ゆれ一致', '未一致(実数)', '紐付け対象'],
            [[$projectsWithCustomers, $matchedExact, $matchedLoose, array_sum($unmatched), count($plan)]],
        );

        if ($unmatched !== []) {
            arsort($unmatched);
            $this->newLine();
            $this->warn('取引先マスタに見つからなかった顧客企業（作成していません）:');
            foreach (array_slice($unmatched, 0, 20, true) as $name => $count) {
                $this->warn("  {$name}（{$count}件）");
            }
            if (count($unmatched) > 20) {
                $this->warn('  ほか'.(count($unmatched) - 20).'種類');
            }
        }

        if ($dryRun) {
            $this->info('--dry-run のため保存しませんでした。');

            return self::SUCCESS;
        }

        $attached = 0;
        foreach ($plan as $projectId => $ids) {
            $project = ProjectRecord::find($projectId);
            if (! $project) {
                continue;
            }

            if ($this->option('detach')) {
                $project->partnerRecords()->sync($ids);
            } else {
                // 既存の紐付けは残したまま追加する。
                $project->partnerRecords()->syncWithoutDetaching($ids);
            }
            $attached += count($ids);
        }

        $this->info("紐付けを保存しました: {$attached}件（プロジェクト".count($plan).'件）');

        return self::SUCCESS;
    }

    /** 全角半角・空白・大小・法人格の表記ゆれを畳む。同一視の判定にだけ使う。 */
    private function loosen(string $value): string
    {
        if (class_exists(Normalizer::class)) {
            $value = Normalizer::normalize($value, Normalizer::FORM_KC) ?: $value;
        }

        $value = mb_strtolower($value);
        $value = preg_replace('/[\s\x{3000}]+/u', '', $value) ?? $value;

        // 「(株)」「㈱」などの略記を「株式会社」に寄せてから比較する。
        $value = str_replace(['㈱', '(株)', '（株）'], '株式会社', $value);
        $value = str_replace(['㈲', '(有)', '（有）'], '有限会社', $value);

        return $value;
    }
}
