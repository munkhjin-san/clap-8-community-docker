<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * 「氏名の文字列」から社内ユーザーを引き当てる。
 *
 * kintoneから移ってきたデータは、担当者を**文字列**で持っていることが多い（ルックアップで
 * コピーした値など）。こちらのユーザー項目はユーザーIDを持つので、間に変換が要る。
 *
 * 突き合わせは空白を無視して行う：kintone側は「中野龍太郎」、users.name は「中野 龍太郎」と、
 * 姓名の間の空白だけが違うため。全角スペースも同じように無視する。
 */
class FlowUserResolver
{
    /** @var array<string, Collection<int, User>>|null 正規化した氏名 => 該当ユーザー */
    private ?array $index = null;

    /** @var array<string, int|null> 引き当て結果の控え（同じ名前を何度も引くため） */
    private array $memo = [];

    /** @var array<string, string> 引き当てられなかった／迷った名前 => 理由 */
    private array $problems = [];

    /**
     * 氏名からユーザーIDを引く。引けなければ null。
     *
     * **在籍中の人にしか結び付けない。** 退職者・削除済みしか該当しないときは空のままにする
     * （担当者欄に辞めた人が入っていると、その人に用があるかのように見えてしまう）。
     * 誰を選んだか／なぜ空にしたかは problems に残して、人が判断できるようにする。
     */
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
            $this->problems[$name] = '該当するユーザーがいません';

            return $this->memo[$name] = null;
        }

        $active = $hits->filter(fn ($u) => $u->deleted_at === null && ! $u->retire);

        if ($active->isEmpty()) {
            $states = $hits->map(fn ($u) => '#'.$u->id.($u->deleted_at ? '(削除済み)' : '(退職)'))->implode('、');
            $this->problems[$name] = "在籍中の該当者がいないため空にしました（{$states}）";

            return $this->memo[$name] = null;
        }

        if ($active->count() > 1) {
            $this->problems[$name] = '在籍中に同名が'.$active->count().'人います（'.$active->pluck('id')->implode(', ').'）';

            return $this->memo[$name] = null;
        }

        $picked = $active->first();
        if ($hits->count() > 1) {
            $this->problems[$name] = "同名{$hits->count()}人から在籍中の #{$picked->id} を選びました";
        }

        return $this->memo[$name] = (int) $picked->id;
    }

    /**
     * kintoneのユーザー選択項目（[{code, name}, …]）や氏名の文字列を、ユーザーIDの配列にする。
     */
    public function resolveMany($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        $names = [];
        foreach ((array) (is_array($value) ? $value : [$value]) as $one) {
            $names[] = is_array($one) ? ($one['name'] ?? $one['code'] ?? '') : (string) $one;
        }

        return collect($names)->map(fn ($n) => $this->resolve($n))->filter()->values()->all();
    }

    /** @return array<string, string> 名前 => 起きたこと（取り込みの報告用） */
    public function problems(): array
    {
        return $this->problems;
    }

    /** 姓名の間の空白（半角・全角）は無視して比べる。 */
    public static function normalize(string $name): string
    {
        return preg_replace('/[\s\x{3000}]+/u', '', $name) ?? $name;
    }

    /** @return array<string, Collection<int, User>> */
    private function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        // 退職者・削除済みも索引に入れる。結び付けはしないが、「その名前の人はいたが退職している」と
        // 「そんな人は元からいない」を区別して報告するために要る。
        return $this->index = User::withTrashed()
            ->get(['id', 'name', 'retire', 'deleted_at'])
            ->groupBy(fn ($u) => self::normalize((string) $u->name))
            ->all();
    }
}
