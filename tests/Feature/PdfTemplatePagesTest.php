<?php

namespace Tests\Feature;

use App\Services\FlowService;
use App\Services\PdfRenderService;
use Mpdf\Mpdf;
use ReflectionMethod;
use Tests\TestCase;

/**
 * PDF帳票テンプレートのページ分け。DBは使わない。
 *
 * 一番大事なのは後方互換：ページという考えが無かった頃のテンプレートは `page` も
 * `paper.pages` も持たないので、それが黙って1ページ目に集まることを固定する。
 */
class PdfTemplatePagesTest extends TestCase
{
    /** @return array<int, array<int, array>> */
    private function pages(array $template): array
    {
        $m = new ReflectionMethod(PdfRenderService::class, 'pagesOf');
        $m->setAccessible(true);

        return $m->invoke(new PdfRenderService(app(FlowService::class)), $template);
    }

    /** 旧テンプレート（pageキー無し）は、まるごと1ページ目。 */
    public function test_a_template_without_pages_stays_one_page(): void
    {
        $pages = $this->pages(['elements' => [
            ['id' => 'a', 'type' => 'text'],
            ['id' => 'b', 'type' => 'field'],
        ]]);

        $this->assertCount(1, $pages);
        $this->assertSame(['a', 'b'], array_column($pages[0], 'id'));
    }

    /** 要素が無くても1ページは在る（描くものが無いだけ）。 */
    public function test_an_empty_template_is_still_one_page(): void
    {
        $this->assertCount(1, $this->pages([]));
    }

    public function test_elements_are_split_by_their_page(): void
    {
        $pages = $this->pages(['paper' => ['pages' => 2], 'elements' => [
            ['id' => 'a', 'page' => 2],
            ['id' => 'b'],            // 未設定は1ページ目
            ['id' => 'c', 'page' => 1],
        ]]);

        $this->assertCount(2, $pages);
        $this->assertSame(['b', 'c'], array_column($pages[0], 'id'));
        $this->assertSame(['a'], array_column($pages[1], 'id'));
    }

    /** 間の空ページは詰めない——2ページ目を空にした構成をそのまま出す。 */
    public function test_a_blank_middle_page_is_preserved(): void
    {
        $pages = $this->pages(['paper' => ['pages' => 3], 'elements' => [
            ['id' => 'a', 'page' => 1],
            ['id' => 'c', 'page' => 3],
        ]]);

        $this->assertCount(3, $pages);
        $this->assertSame([], $pages[1]);
        $this->assertSame(['c'], array_column($pages[2], 'id'));
    }

    /**
     * ページ数を減らした設定が残っていても、要素は落とさない。
     * paper.pages だけを信じると、その先に置いた要素が黙って消える。
     */
    public function test_pages_count_never_drops_an_element(): void
    {
        $pages = $this->pages(['paper' => ['pages' => 1], 'elements' => [
            ['id' => 'a', 'page' => 1],
            ['id' => 'z', 'page' => 4],
        ]]);

        $this->assertCount(4, $pages);
        $this->assertSame(['z'], array_column($pages[3], 'id'));
    }

    /**
     * 下敷きに使えるPDFかを、受け取る前に確かめられること。
     * ここで弾けないと、出力を押した誰かが初めて気づくことになる。
     */
    public function test_a_readable_pdf_reports_its_page_count(): void
    {
        $mpdf = new Mpdf(['tempDir' => storage_path('app/mpdf')]);
        $mpdf->WriteHTML('<p>1</p><pagebreak /><p>2</p>');
        $file = tempnam(sys_get_temp_dir(), 'bg').'.pdf';
        file_put_contents($file, $mpdf->Output('', 'S'));

        $svc = new PdfRenderService(app(FlowService::class));
        $this->assertSame(2, $svc->probeBackground($file));

        unlink($file);
    }

    /** PDFでないもの・存在しないものは null（例外にしない——上げた人に理由を返したい）。 */
    public function test_unusable_input_reports_null_instead_of_throwing(): void
    {
        $svc = new PdfRenderService(app(FlowService::class));

        $notPdf = tempnam(sys_get_temp_dir(), 'bg');
        file_put_contents($notPdf, 'これはPDFではありません');

        $this->assertNull($svc->probeBackground($notPdf));
        $this->assertNull($svc->probeBackground('/存在しない/経路.pdf'));

        unlink($notPdf);
    }

    /** 0や負のページ番号（手で書き換えた設定）は1ページ目に寄せる。 */
    public function test_out_of_range_page_numbers_fall_back_to_the_first_page(): void
    {
        $pages = $this->pages(['elements' => [
            ['id' => 'a', 'page' => 0],
            ['id' => 'b', 'page' => -3],
        ]]);

        $this->assertCount(1, $pages);
        $this->assertSame(['a', 'b'], array_column($pages[0], 'id'));
    }
}
