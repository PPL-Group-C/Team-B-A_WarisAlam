<?php

namespace Tests\Browser\Moderation;

use App\Enums\ConfirmationType;
use App\Enums\DamageCategory;
use App\Enums\ReportStatus;
use App\Models\DamageReport;
use App\Models\HeritageSite;
use App\Models\User;
use Database\Factories\DamageReportFactory;
use Database\Factories\HeritageSiteFactory;
use Database\Factories\ReportConfirmationFactory;
use Database\Factories\ReportPhotoFactory;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\DuskTestCase;

/**
 * F08 Moderasi Laporan: dasar untuk semua test Dusk moderasi.
 *
 * Test ini MENULIS ke database (setujui, tolak, tandai duplikat), jadi
 * wajib dijalankan dengan .env.dusk.local yang menunjuk ke database
 * khusus Dusk (mis. warisalam_dusk). Setiap test mengosongkan tabel
 * database itu terlebih dulu. Server (php artisan serve) harus berjalan
 * di APP_URL, dan email moderator harus ada di MODERATOR_EMAILS.
 */
abstract class ModerationBrowserTestCase extends DuskTestCase
{
    use DatabaseTruncation;

    protected const PHOTO_DIR = 'reports/dusk';

    protected User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        $email = config('moderation.moderator_emails')[0] ?? null;

        if (! $email) {
            $this->fail('Isi MODERATOR_EMAILS di .env.dusk.local, mis. MODERATOR_EMAILS=moderator@warisalam.test');
        }

        $this->moderator = User::factory()->create(['name' => 'Rina Moderator', 'email' => $email]);
    }

    protected function tearDown(): void
    {
        Storage::disk('public')->deleteDirectory(self::PHOTO_DIR);

        parent::tearDown();
    }

    /** Pengaman: jangan pernah mengosongkan database kerja (mis. warisalam). */
    protected function beforeTruncatingDatabase(): void
    {
        $database = (string) DB::connection()->getDatabaseName();

        if (! str_ends_with($database, '_dusk')) {
            $this->fail("Test Dusk moderasi mengosongkan tabel, jadi hanya boleh memakai database *_dusk (sekarang: \"{$database}\"). Buat .env.dusk.local dengan DB_DATABASE=warisalam_dusk.");
        }
    }

    // ---- Pembuat data -----------------------------------------------------

    protected function site(string $name, array $attributes = []): HeritageSite
    {
        return HeritageSiteFactory::new()->create(['name' => $name] + $attributes);
    }

    /**
     * Laporan dengan waktu kirim tertentu. $minutesAgo makin besar = makin lama.
     */
    protected function report(array $attributes = [], int $minutesAgo = 60, ?HeritageSite $site = null, float $offsetMeters = 0): DamageReport
    {
        $factory = DamageReportFactory::new()->submittedAt(Carbon::now()->subMinutes($minutesAgo));

        if ($site) {
            $factory = $factory->atSite($site, $offsetMeters);
        }

        return $factory->create($attributes + [
            'category' => DamageCategory::Vandalism,
            'severity' => 3,
        ]);
    }

    protected function pending(array $attributes = [], int $minutesAgo = 60, ?HeritageSite $site = null, float $offsetMeters = 0): DamageReport
    {
        return $this->report($attributes + ['status' => ReportStatus::Pending->value], $minutesAgo, $site, $offsetMeters);
    }

    /** Laporan $child sudah ditandai duplikat dari $parent (status Ditolak). */
    protected function markedDuplicate(DamageReport $child, DamageReport $parent): DamageReport
    {
        $child->forceFill([
            'status' => ReportStatus::Rejected->value,
            'duplicate_of_id' => $parent->id,
            'rejected_as_duplicate' => true,
            'rejection_reason' => 'Duplikat dari laporan '.\App\Support\ReportPresenter::code($parent->id).'.',
        ])->save();

        return $child;
    }

    /** Foto bukti yang benar-benar ada di disk "public" supaya tampil di browser. */
    protected function photo(DamageReport $report, string $name = 'IMG_20261117_091500.JPG'): void
    {
        $path = self::PHOTO_DIR.'/'.$report->id.'-'.uniqid().'.jpg';
        Storage::disk('public')->put($path, $this->sampleJpeg($name));

        ReportPhotoFactory::new()->create([
            'damage_report_id' => $report->id,
            'path' => $path,
            'original_name' => $name,
            'exif_valid' => true,
        ]);
    }

    /** Gambar contoh berwarna (bukan kotak hitam) supaya terlihat jelas di screenshot bukti. */
    private function sampleJpeg(string $label): string
    {
        $image = imagecreatetruecolor(800, 600);

        for ($y = 0; $y < 600; $y++) {
            $shade = (int) (60 + $y / 600 * 120);
            imageline($image, 0, $y, 799, $y, imagecolorallocate($image, 4, $shade, 45 + (int) ($shade / 3)));
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 250, 220, 550, 380, imagecolorallocate($image, 232, 247, 236));
        imagestring($image, 5, 300, 280, 'FOTO BUKTI (DUSK)', imagecolorallocate($image, 4, 68, 45));
        imagestring($image, 4, 20, 570, $label, $white);

        ob_start();
        imagejpeg($image, null, 85);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    protected function votes(DamageReport $report, int $confirms, int $disputes = 0): void
    {
        ReportConfirmationFactory::new()->count($confirms)->create(['report_id' => $report->id, 'type' => ConfirmationType::Confirm]);
        ReportConfirmationFactory::new()->count($disputes)->create(['report_id' => $report->id, 'type' => ConfirmationType::Dispute]);
    }
}
