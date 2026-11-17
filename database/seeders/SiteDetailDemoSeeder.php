<?php

namespace Database\Seeders;

use App\Enums\SiteStatus;
use App\Models\HeritageSite;
use App\Models\SiteAdoption;
use App\Models\SitePhoto;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data demo halaman detail situs (PBI-13–15). Aman dijalankan ulang.
 *
 *   php artisan db:seed --class=SiteDetailDemoSeeder
 *   php artisan storage:link   (sekali saja, agar foto galeri tampil)
 *
 * Situs dicocokkan lewat registration_id yang sama dengan HeritageSiteSeeder
 * dan ModerationDemoSeeder, jadi laporan demo moderasi ikut muncul di riwayat.
 * Nomor registrasi masih placeholder; data asli menyusul dari seeder Wikidata (TR-14).
 * Skor & status diisi manual sampai scheduler skor ancaman (Sprint 2) aktif.
 */
class SiteDetailDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->sites() as $i => $data) {
            $site = HeritageSite::firstOrCreate(
                ['registration_id' => $data['registration_id']],
                ['name' => $data['name'], 'latitude' => $data['latitude'], 'longitude' => $data['longitude'], 'heritage_note' => $data['category']],
            );

            $site->fill([
                'type' => $data['type'],
                'category' => $data['category'],
                'province' => $data['province'],
                'city' => $data['city'],
                'description' => $data['description'],
                'history' => $data['history'],
                'facts' => json_encode($data['facts'], JSON_UNESCAPED_UNICODE),
                'status' => SiteStatus::fromScore($data['threat_score'])->value,
                'threat_score' => $data['threat_score'],
                'score_updated_at' => now()->subMinutes(10 + $i * 7),
            ])->save();

            $this->seedPhotos($site, $data['photos']);
            $this->seedAdoptions($site, $data['adopters']);
        }
    }

    /** @param  list<string>  $captions */
    private function seedPhotos(HeritageSite $site, array $captions): void
    {
        $folder = 'sites/demo/'.Str::slug($site->registration_id);
        SitePhoto::where('heritage_site_id', $site->id)->where('path', 'like', $folder.'/%')->delete();

        foreach ($captions as $n => $caption) {
            $path = "{$folder}/foto-".($n + 1).'.svg';
            Storage::disk('public')->put($path, $this->placeholderSvg($site->id * 10 + $n));

            SitePhoto::create([
                'heritage_site_id' => $site->id,
                'path' => $path,
                'caption' => $caption,
                'credit' => 'Foto demo (placeholder)',
                'sort_order' => $n,
            ]);
        }
    }

    /** Pakai akun yang sudah ada (mis. dari ModerationDemoSeeder); dilewati bila belum ada user. */
    private function seedAdoptions(HeritageSite $site, int $count): void
    {
        User::query()->orderBy('id')->limit($count)->pluck('id')->each(
            fn (int $userId) => SiteAdoption::firstOrCreate(['heritage_site_id' => $site->id, 'user_id' => $userId]),
        );
    }

    /** Ilustrasi sederhana; keterangan foto ditampilkan halaman, bukan di gambar. */
    private function placeholderSvg(int $seed): string
    {
        mt_srand($seed);
        $hue = mt_rand(90, 160);
        $hills = '';
        for ($i = 0; $i < 3; $i++) {
            $y = 300 + $i * 50;
            $hills .= sprintf(
                '<path d="M0 %d Q 200 %d 400 %d T 800 %d V 600 H 0 Z" fill="hsl(%d 35%% %d%%)"/>',
                $y, $y - mt_rand(40, 120), $y, $y - mt_rand(0, 60), $hue + $i * 8, 38 - $i * 8,
            );
        }
        mt_srand();

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600" width="800" height="600">
  <defs><linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0" stop-color="hsl({$hue} 45% 86%)"/><stop offset="1" stop-color="hsl({$hue} 40% 70%)"/>
  </linearGradient></defs>
  <rect width="800" height="600" fill="url(#sky)"/>
  <circle cx="640" cy="130" r="56" fill="#fff7d6" opacity=".8"/>
  {$hills}
  <text x="772" y="40" font-family="Inter, Arial, sans-serif" font-size="16" fill="#05452b" opacity=".6" text-anchor="end">foto demo</text>
</svg>
SVG;
    }

    /** @return list<array<string, mixed>> */
    private function sites(): array
    {
        return [
            [
                'registration_id' => 'CB-JTG-MAGELANG-1991-001',
                'name' => 'Candi Borobudur (Magelang)',
                'latitude' => -7.6079, 'longitude' => 110.2038,
                'type' => 'cultural', 'category' => 'UNESCO World Heritage',
                'province' => 'Jawa Tengah', 'city' => 'Kabupaten Magelang',
                'threat_score' => 1.4, 'adopters' => 3,
                'description' => 'Candi Buddha terbesar di dunia, berupa punden berundak dengan relief dan stupa yang menggambarkan perjalanan menuju pencerahan.',
                'history' => "Borobudur dibangun pada abad ke-8 hingga ke-9 Masehi pada masa Wangsa Syailendra. Setelah lama tertimbun abu vulkanik dan vegetasi, candi ini dilaporkan kembali pada 1814 saat pemerintahan Thomas Stamford Raffles.\n\nPemugaran besar dilakukan pemerintah Indonesia bersama UNESCO pada 1975–1982. Pada 1991 kawasan Candi Borobudur, Mendut, dan Pawon ditetapkan sebagai Warisan Dunia UNESCO.",
                'facts' => [
                    ['label' => 'Periode', 'value' => 'Abad ke-8 – 9 M (Wangsa Syailendra)'],
                    ['label' => 'Struktur', 'value' => '6 teras bujur sangkar dan 3 pelataran melingkar'],
                    ['label' => 'Relief', 'value' => '2.672 panel'],
                    ['label' => 'Arca Buddha', 'value' => '504 arca'],
                    ['label' => 'Warisan Dunia UNESCO', 'value' => '1991'],
                ],
                'photos' => ['Stupa pelataran melingkar', 'Relief lorong tingkat 1', 'Pemandangan dari bukit'],
            ],
            [
                'registration_id' => 'CB-DIY-SLEMAN-1991-001',
                'name' => 'Candi Prambanan (Kompleks Percandian Roro Jonggrang, Sleman)',
                'latitude' => -7.7520, 'longitude' => 110.4914,
                'type' => 'cultural', 'category' => 'UNESCO World Heritage',
                'province' => 'DI Yogyakarta', 'city' => 'Kabupaten Sleman',
                'threat_score' => 2.1, 'adopters' => 2,
                'description' => 'Kompleks candi Hindu terbesar di Indonesia yang dipersembahkan untuk Trimurti: Siwa, Wisnu, dan Brahma.',
                'history' => "Prambanan dibangun sekitar pertengahan abad ke-9 Masehi pada masa Kerajaan Mataram Kuno. Candi utama Siwa menjulang sekitar 47 meter dan dikelilingi ratusan candi perwara.\n\nKompleks ini ditetapkan sebagai Warisan Dunia UNESCO pada 1991. Gempa Yogyakarta 2006 merusak sebagian struktur sehingga dilakukan pemugaran bertahap.",
                'facts' => [
                    ['label' => 'Periode', 'value' => 'Abad ke-9 M (Mataram Kuno)'],
                    ['label' => 'Tinggi candi Siwa', 'value' => '± 47 m'],
                    ['label' => 'Dewa utama', 'value' => 'Trimurti (Siwa, Wisnu, Brahma)'],
                    ['label' => 'Warisan Dunia UNESCO', 'value' => '1991'],
                ],
                'photos' => ['Candi Siwa dari pelataran', 'Relief Ramayana', 'Candi perwara'],
            ],
            [
                'registration_id' => 'CB-JTG-SRAGEN-1996-001',
                'name' => 'Situs Sangiran (Sragen)',
                'latitude' => -7.4500, 'longitude' => 110.8333,
                'type' => 'cultural', 'category' => 'UNESCO World Heritage',
                'province' => 'Jawa Tengah', 'city' => 'Kabupaten Sragen',
                'threat_score' => 6.2, 'adopters' => 1,
                'description' => 'Situs manusia purba dengan temuan fosil Homo erectus dan alat batu yang penting bagi studi evolusi manusia.',
                'history' => "Penelitian di Sangiran berkembang sejak 1930-an, antara lain oleh G.H.R. von Koenigswald, yang menemukan alat batu dan fosil hominid.\n\nPada 1996 Sangiran ditetapkan sebagai Warisan Dunia UNESCO (Sangiran Early Man Site). Penggalian liar untuk mencari fosil menjadi salah satu ancaman yang dipantau.",
                'facts' => [
                    ['label' => 'Temuan utama', 'value' => 'Fosil Homo erectus dan alat batu'],
                    ['label' => 'Wilayah', 'value' => 'Kabupaten Sragen dan Karanganyar'],
                    ['label' => 'Warisan Dunia UNESCO', 'value' => '1996'],
                ],
                'photos' => ['Lapisan tanah purba', 'Museum Klaster Krikilan'],
            ],
            [
                'registration_id' => 'CB-JTG-KARANGANYAR-1993-001',
                'name' => 'Candi Cetho (Karanganyar)',
                'latitude' => -7.5953, 'longitude' => 111.1574,
                'type' => 'cultural', 'category' => 'Cagar Budaya Nasional',
                'province' => 'Jawa Tengah', 'city' => 'Kabupaten Karanganyar',
                'threat_score' => 8.7, 'adopters' => 2,
                'description' => 'Candi Hindu berundak di lereng barat Gunung Lawu dari masa akhir Majapahit.',
                'history' => "Candi Cetho berasal dari abad ke-15 Masehi, masa akhir Kerajaan Majapahit. Bentuknya berupa punden berundak yang memadukan unsur Hindu dengan tradisi lokal.\n\nCandi ini masih dipakai untuk kegiatan peribadatan, sehingga sampah sisa persembahan dan vandalisme menjadi perhatian pengelola.",
                'facts' => [
                    ['label' => 'Periode', 'value' => 'Abad ke-15 M (akhir Majapahit)'],
                    ['label' => 'Ketinggian', 'value' => '± 1.496 mdpl'],
                    ['label' => 'Bentuk', 'value' => 'Punden berundak'],
                ],
                'photos' => ['Gapura teras atas', 'Teras punden berundak'],
            ],
            [
                'registration_id' => 'CB-JMB-MUAROJAMBI-2013-001',
                'name' => 'Kawasan Percandian Muaro Jambi',
                'latitude' => -1.4767, 'longitude' => 103.6667,
                'type' => 'cultural', 'category' => 'Kawasan Cagar Budaya Nasional',
                'province' => 'Jambi', 'city' => 'Kabupaten Muaro Jambi',
                'threat_score' => 18.3, 'adopters' => 4,
                'description' => 'Salah satu kompleks percandian Buddha terluas di Asia Tenggara, di tepi Sungai Batanghari.',
                'history' => "Kawasan Muaro Jambi berkembang sekitar abad ke-7 hingga ke-13 Masehi dan diduga menjadi pusat pendidikan agama Buddha. Di dalamnya terdapat Candi Gumpung, Candi Tinggi, dan Candi Kedaton yang terbuat dari bata merah.\n\nKawasan ini masuk daftar sementara Warisan Dunia UNESCO. Alih fungsi lahan dan aktivitas industri di sekitarnya menjadi ancaman utama.",
                'facts' => [
                    ['label' => 'Periode', 'value' => 'Abad ke-7 – 13 M'],
                    ['label' => 'Bahan', 'value' => 'Bata merah'],
                    ['label' => 'Candi utama', 'value' => 'Gumpung, Tinggi, Kedaton'],
                    ['label' => 'Status UNESCO', 'value' => 'Daftar sementara (Tentative List)'],
                ],
                'photos' => ['Candi Gumpung', 'Kanal kuno', 'Candi Kedaton'],
            ],
            [
                'registration_id' => 'TN-NTT-KOMODO-1980-001',
                'name' => 'Taman Nasional Komodo',
                'latitude' => -8.5500, 'longitude' => 119.4890,
                'type' => 'natural', 'category' => 'UNESCO World Heritage',
                'province' => 'Nusa Tenggara Timur', 'city' => 'Kabupaten Manggarai Barat',
                'threat_score' => 7.9, 'adopters' => 2,
                'description' => 'Habitat asli komodo (Varanus komodoensis) yang meliputi Pulau Komodo, Rinca, Padar, dan perairan sekitarnya.',
                'history' => "Taman Nasional Komodo dibentuk pada 1980 untuk melindungi komodo dan habitatnya. Pada 1991 kawasan ini ditetapkan sebagai Warisan Dunia UNESCO.\n\nSelain satwa darat, perairannya memiliki terumbu karang yang kaya. Tekanan pariwisata dan sampah laut termasuk ancaman yang dipantau.",
                'facts' => [
                    ['label' => 'Ditetapkan', 'value' => '1980'],
                    ['label' => 'Satwa kunci', 'value' => 'Komodo (Varanus komodoensis)'],
                    ['label' => 'Pulau utama', 'value' => 'Komodo, Rinca, Padar'],
                    ['label' => 'Warisan Dunia UNESCO', 'value' => '1991'],
                ],
                'photos' => ['Bukit Pulau Padar', 'Pantai Pulau Komodo'],
            ],
        ];
    }
}
