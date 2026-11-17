<?php

namespace Tests\Browser\Pages;

use App\Support\ReportPresenter;
use Laravel\Dusk\Browser;

/**
 * F08 Moderasi Laporan: dasbor antrean moderator (/moderasi).
 */
class ModerationPage extends Page
{
    public function __construct(private readonly array $query = []) {}

    public function url(): string
    {
        return '/moderasi'.($this->query ? '?'.http_build_query($this->query) : '');
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs('/moderasi')
            ->assertSee('Dasbor Moderasi Laporan Sains Warga');
    }

    /**
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@search' => 'input[name="q"]',
            '@queue' => '[aria-labelledby="queue-title"]',
            '@queue-total' => '[data-testid="queue-total"]',
            '@detail' => '[data-selected-report]',
            '@credibility' => 'aside[aria-label="Kredibilitas pelapor"]',
            '@approve' => '[data-action="approve"]',
            '@reject' => '[data-action="reject"]',
            '@resolve' => '[data-action="resolve"]',
            '@approve-modal' => '[aria-labelledby="approve-title"]',
            '@reject-modal' => '[aria-labelledby="reject-title"]',
            '@reject-submit' => 'button[formaction$="/tolak"]',
            '@lightbox' => '[aria-label="Foto diperbesar"]',
            '@duplicate-box' => '#tandai-duplikat',
            '@duplicate-link' => 'a[href="#tandai-duplikat"]',
            '@duplicate-form' => 'form[action$="/duplikat"]',
            '@duplicate-search' => 'form[action$="/duplikat"] input[type="search"]',
            '@duplicate-submit' => '[data-action="duplicate"]',
            '@log' => '[aria-labelledby="log-title"]',
            '@log-first' => '[aria-labelledby="log-title"] tbody tr:first-child',
            '@flash' => '[data-flash="success"]',
        ];
    }

    /** Isi bar filter lalu klik Terapkan. Kunci: q, status, category, severity, sort. */
    public function filter(Browser $browser, array $filters): void
    {
        foreach ($filters as $field => $value) {
            $field === 'q'
                ? $browser->type('@search', $value)
                : $browser->select($field, $value);
        }

        $browser->waitForReload(fn (Browser $b) => $b->press('Terapkan'))
            ->waitFor('@queue');
    }

    /** Klik kartu laporan di antrean dan tunggu panel detailnya terbuka. */
    public function openReport(Browser $browser, int $id): void
    {
        $browser->click(self::card($id))
            ->waitFor('[data-selected-report="'.$id.'"]');
    }

    /** Pilih severity L1–L5 di form penyesuaian. */
    public function chooseSeverity(Browser $browser, int $level): void
    {
        $browser->click('input[name="severity"][value="'.$level.'"] + span');
    }

    /** Urutan ID kartu di antrean, dari atas ke bawah. */
    public static function queueIds(Browser $browser): array
    {
        return $browser->script(
            'return [...document.querySelectorAll("[data-report-card]")].map(e => Number(e.dataset.reportCard));'
        )[0];
    }

    public static function card(int $id): string
    {
        return '[data-report-card="'.$id.'"]';
    }

    public static function stat(string $slug): string
    {
        return '[data-stat="'.$slug.'"]';
    }

    public static function code(int $id): string
    {
        return ReportPresenter::code($id);
    }
}
