<?php

namespace App\Support;

use App\Models\ProjectRecord;
use Illuminate\Support\Collection;
use Normalizer;

/**
 * 「部門名の文字列」からプロジェクトを引き当てる。
 *
 * kintoneから移ってきた契約書は、部門を**文字列**で持っている。こちらのプロジェクト項目は
 * プロジェクトIDを持つので、間に変換が要る。
 *
 * 突き合わせは半角カナ・全角英数・空白の違いを無視する：kintone側は「ﾃﾚｺﾝ(ｽﾏｰﾄﾒｰﾀｰ)」、
 * こちらは「テレコン（スマートメーター）」のように、同じ部門が違う書き方で入っている。
 *
 * **消えたプロジェクトには結び付けない。** 過去の部門は今のプロジェクトに無いことが多く、
 * 削除済みまで拾いにいくと同名が増えてどれが正か分からなくなる（実データでは、拾える数は
 * 5件しか増えないのに9件が同名衝突になった）。引けないものは空のままにする。
 */
class FlowProjectResolver
{
    /** @var array<string, Collection<int, ProjectRecord>>|null 正規化した名前 => 該当プロジェクト */
    private ?array $index = null;

    /** @var array<string, int|null> 引き当て結果の控え */
    private array $memo = [];

    /** @var array<string, string> 引けなかった名前 => 理由 */
    private array $problems = [];

    /** 部門名からプロジェクトIDを引く。引けなければ null。 */
    public function resolve(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }
        if (array_key_exists($name, $this->memo)) {
            return $this->memo[$name];
        }

        $hits = $this->index()[self::normalize($name)] ?? collect();

        if ($hits->isEmpty()) {
            $this->problems[$name] = '該当するプロジェクトがありません';

            return $this->memo[$name] = null;
        }

        if ($hits->count() > 1) {
            $this->problems[$name] = '同名のプロジェクトが'.$hits->count().'件あります（'.$hits->pluck('id')->implode(', ').'）';

            return $this->memo[$name] = null;
        }

        return $this->memo[$name] = (int) $hits->first()->id;
    }

    /** @return array<string, string> 名前 => 起きたこと（取り込みの報告用） */
    public function problems(): array
    {
        return $this->problems;
    }

    /**
     * 半角カナ・全角英数・空白の違いを無視した形にする。
     *
     * NFKC は「ｽﾏｰﾄ」→「スマート」、「（）」→「()」のように互換文字を揃えてくれる。
     * 部門名の表記ゆれはほぼこれで吸収できる。
     */
    public static function normalize(string $name): string
    {
        $n = class_exists(Normalizer::class)
            ? (Normalizer::normalize($name, Normalizer::FORM_KC) ?: $name)
            : $name;

        return mb_strtolower(preg_replace('/[\s\x{3000}]+/u', '', $n) ?? $n);
    }

    /** @return array<string, Collection<int, ProjectRecord>> */
    private function index(): array
    {
        return $this->index ??= ProjectRecord::query()
            ->get(['id', 'name'])
            ->groupBy(fn ($p) => self::normalize((string) $p->name))
            ->all();
    }
}
