<?php

namespace Database\Seeders;

use App\Enums\DamageCategory;
use App\Enums\ReportStatus;
use App\Models\DamageReport;
use App\Models\HeritageSite;
use App\Models\User;
use App\Services\ReportStatusService;
use Database\Factories\DamageReportFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Data demo F08 Moderasi. Aman dijalankan ulang: laporan milik akun demo
 * dihapus dulu lalu dibuat kembali.
 *
 *   php artisan db:seed --class=ModerationDemoSeeder
 *
 * Akun moderator: moderator@warisalam.test (password: password).
 * Pastikan MODERATOR_EMAILS=moderator@warisalam.test ada di .env.
 */
class ModerationDemoSeeder extends Seeder
{
    public const MODERATOR_EMAIL = 'moderator@warisalam.test';

    public function run(ReportStatusService $statuses): void
    {
        $this->call(HeritageSiteSeeder::class);

        $sites = $this->sites();
        $moderator = $this->user('Akmal (Moderator)', self::MODERATOR_EMAIL);
        $warga = collect([
            ['Rian Hidayat', 'rian'], ['Dwi Santoso', 'dwi'], ['Siti Rahma', 'siti'],
            ['Komunitas Penjaga Muaro Jambi', 'muarojambi'], ['Budi Hartono', 'budi'],
        ])->map(fn ($u) => $this->user($u[0], "{$u[1]}@warisalam.test"));

        DamageReport::whereIn('user_id', $warga->pluck('id'))->get()->each->delete();

        $now = now();
        // [situs, pelapor, kategori, severity, menit lalu, anonim, offset meter dari titik situs, deskripsi]
        $rows = [
            ['prambanan', 0, DamageCategory::Vandalism, 3, 42, false, 0, 'Coretan cat semprot biru di kaki arca Ganesha, zona inti Candi Siwa.'],
            ['prambanan', 1, DamageCategory::Vandalism, 3, 18, true, 12, 'Ada vandalisme pilox biru di bagian selatan relief batu candi pelataran Siwa.'],
            ['muarojambi', 3, DamageCategory::LandUseChange, 5, 120, false, 40, 'Alih fungsi lahan & paparan debu stockpile batubara di sekitar Candi Gumpung. Ancaman erosi kimiawi bata merah kuno.'],
            ['cetho', 1, DamageCategory::Waste, 2, 210, false, 25, 'Akumulasi sisa persembahan plastik di teras VII.'],
            ['borobudur', 2, DamageCategory::PhysicalDamage, 4, 60 * 26, false, 30, 'Retakan baru pada batu andesit di lorong tingkat 3 sisi timur setelah hujan deras.'],
            ['borobudur', 4, DamageCategory::Waste, 1, 60 * 5, true, 150, 'Sampah botol plastik menumpuk di area parkir dekat pintu masuk zona 2.'],
            ['sangiran', 2, DamageCategory::Other, 4, 60 * 50, false, 300, 'Indikasi penggalian liar di tebing dekat museum, diduga pencarian fosil.'],
            ['sangiran', 0, DamageCategory::FloraFauna, 2, 60 * 8, false, 90, 'Penebangan beberapa pohon jati tua di kawasan penyangga.'],
            ['cetho', 4, DamageCategory::Vandalism, 3, 60 * 3, true, 10, 'Ukiran nama pengunjung pada arca di teras atas.'],
            ['prambanan', 2, DamageCategory::PhysicalDamage, 2, 60 * 72, false, 400, 'Pelapukan batu pada pagar langkan Candi Brahma.'],
            ['muarojambi', 3, DamageCategory::Waste, 3, 60 * 30, false, 60, 'Limbah oli dibuang ke kanal kuno di sisi barat kompleks.'],
            ['borobudur', 0, DamageCategory::Vandalism, 3, 60 * 96, false, 20, 'Coretan spidol hitam pada relief Karmawibhangga sisi utara.'],
        ];

        $reports = [];
        foreach ($rows as $i => [$siteKey, $userIdx, $category, $severity, $minutesAgo, $anonymous, $offset, $description]) {
            $at = $now->copy()->subMinutes($minutesAgo);
            $report = DamageReportFactory::new()
                ->atSite($sites[$siteKey], $offset)
                ->submittedAt($at)
                ->state([
                    'user_id' => $warga[$userIdx]->id,
                    'category' => $category,
                    'severity' => $severity,
                    'is_anonymous' => $anonymous,
                    'description' => $description,
                ])
                ->create();

            $this->attachPhotos($report, $i, $i % 3 === 0 ? 1 : 2, $at, withExif: $i % 4 !== 3);
            $reports[] = $report;
        }

        // Beberapa laporan yang sudah diproses agar statistik & rekam jejak pelapor terisi.
        $statuses->transition($reports[9], ReportStatus::Verified, $moderator, 'Pelapukan terkonfirmasi dari foto.');
        $reason = 'Foto tidak menunjukkan lokasi kanal yang dimaksud; mohon kirim ulang dengan titik GPS yang benar.';
        $statuses->transition($reports[10], ReportStatus::Rejected, $moderator, $reason, ['rejection_reason' => $reason]);

        // Contoh laporan yang sedang ditangani instansi → bisa diuji "Konfirmasi Selesai".
        $statuses->transition($reports[11], ReportStatus::Verified, $moderator, null, ['moderator_note' => 'Coretan terverifikasi, perlu pembersihan non-destruktif.']);
        $statuses->transition($reports[11], ReportStatus::Forwarded, null, 'Diteruskan ke BPK Wilayah X (simulasi F09).');
        $statuses->transition($reports[11], ReportStatus::InProgress, null, 'Tim BPK menjadwalkan pembersihan (simulasi instansi).');

        if (! in_array(self::MODERATOR_EMAIL, config('moderation.moderator_emails'), true)) {
            $this->command?->warn('Tambahkan MODERATOR_EMAILS='.self::MODERATOR_EMAIL.' ke .env agar akun ini bisa membuka /moderasi.');
        }

        $this->command?->info("Moderator: {$moderator->email} (id {$moderator->id}) → /dev/login-as/{$moderator->id}");
    }

    /** @return array<string, HeritageSite> */
    private function sites(): array
    {
        $extra = [
            ['Candi Cetho (Karanganyar)', 'CB-JTG-KARANGANYAR-1993-001', 'Cagar Budaya Nasional', -7.5953, 111.1574],
            ['Kawasan Percandian Muaro Jambi', 'CB-JMB-MUAROJAMBI-2013-001', 'Kawasan Cagar Budaya Nasional', -1.4767, 103.6667],
        ];

        foreach ($extra as [$name, $reg, $note, $lat, $lng]) {
            HeritageSite::updateOrCreate(
                ['registration_id' => $reg],
                ['name' => $name, 'heritage_note' => $note, 'latitude' => $lat, 'longitude' => $lng],
            );
        }

        $byReg = HeritageSite::pluck('id', 'registration_id');

        return collect([
            'prambanan' => 'CB-DIY-SLEMAN-1991-001',
            'borobudur' => 'CB-JTG-MAGELANG-1991-001',
            'sangiran' => 'CB-JTG-SRAGEN-1996-001',
            'cetho' => 'CB-JTG-KARANGANYAR-1993-001',
            'muarojambi' => 'CB-JMB-MUAROJAMBI-2013-001',
        ])->map(fn ($reg) => HeritageSite::findOrFail($byReg[$reg]))->all();
    }

    private function user(string $name, string $email): User
    {
        return User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
    }

    private function attachPhotos(DamageReport $report, int $seed, int $count, $submittedAt, bool $withExif): void
    {
        for ($n = 1; $n <= $count; $n++) {
            $path = "reports/demo/report-{$report->id}-{$n}.jpg";
            $bytes = $this->placeholderJpeg($seed * 7 + $n, "#REP-{$report->id} • Foto {$n}");
            Storage::disk('public')->put($path, $bytes);

            $takenAt = $submittedAt->copy()->subMinutes(5 + $seed * 3 + $n);

            $report->photos()->create([
                'path' => $path,
                'original_name' => 'IMG_'.$takenAt->format('Ymd_His').'.JPG',
                'size_kb' => (int) ceil(strlen($bytes) / 1024) + 2000 + $seed * 37,
                'exif_valid' => $withExif,
                'exif_latitude' => $withExif ? (float) $report->latitude + 0.00002 * $n : null,
                'exif_longitude' => $withExif ? (float) $report->longitude - 0.00001 * $n : null,
                'taken_at' => $withExif ? $takenAt : null,
            ]);
        }
    }

    /** Gambar placeholder sederhana (butuh ekstensi gd). */
    private function placeholderJpeg(int $seed, string $label): string
    {
        $w = 640;
        $h = 480;
        $img = imagecreatetruecolor($w, $h);
        mt_srand($seed);
        $base = [mt_rand(60, 140), mt_rand(80, 150), mt_rand(60, 120)];

        for ($y = 0; $y < $h; $y += 4) {
            $shade = (int) (40 * $y / $h);
            $color = imagecolorallocate($img, min(255, $base[0] + $shade), min(255, $base[1] + $shade), min(255, $base[2] + $shade));
            imagefilledrectangle($img, 0, $y, $w, $y + 4, $color);
        }

        for ($i = 0; $i < 18; $i++) {
            $c = imagecolorallocatealpha($img, mt_rand(150, 230), mt_rand(140, 210), mt_rand(110, 180), mt_rand(40, 90));
            imagefilledellipse($img, mt_rand(0, $w), mt_rand(0, $h), mt_rand(40, 220), mt_rand(30, 160), $c);
        }

        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, $h - 50, $w, $h, imagecolorallocatealpha($img, 5, 69, 43, 30));
        imagestring($img, 5, 16, $h - 34, $label.' (foto demo)', $white);
        mt_srand();

        ob_start();
        imagejpeg($img, null, 80);
        imagedestroy($img);

        return (string) ob_get_clean();
    }
}
