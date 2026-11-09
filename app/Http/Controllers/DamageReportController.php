<?php

namespace App\Http\Controllers;

use App\Enums\DamageCategory;
use App\Http\Requests\StoreDamageReportRequest;
use App\Models\DamageReport;
use App\Models\HeritageSite;
use App\Support\ExifReader;
use Illuminate\Support\Facades\DB;

class DamageReportController extends Controller
{
    /** A photo counts as "EXIF Valid" if its GPS is within this distance of the pin. */
    private const EXIF_MAX_DISTANCE_M = 200;

    private const SEVERITY_LEVELS = [
        ['value' => 1, 'name' => 'Ringan', 'title' => 'Kerusakan Kosmetik',
            'description' => 'Kerusakan minor pada permukaan tanpa dampak pada struktur atau nilai sejarah; cukup dipantau berkala.'],
        ['value' => 2, 'name' => 'Rendah', 'title' => 'Kerusakan Permukaan Terbatas',
            'description' => 'Kerusakan terbatas yang perlu dijadwalkan dalam pemeliharaan rutin oleh pengelola situs.'],
        ['value' => 3, 'name' => 'Sedang', 'title' => 'Kerusakan Material Parsial',
            'description' => 'Kerusakan material parsial namun belum meruntuhkan struktur utama cagar budaya; membutuhkan penanganan restorasi kimiawi/konservasi khusus oleh tim BPK dalam kurun waktu 48 jam ke depan.'],
        ['value' => 4, 'name' => 'Tinggi', 'title' => 'Kerusakan Struktural Luas',
            'description' => 'Kerusakan luas pada elemen penting yang berisiko memburuk dengan cepat; butuh penanganan prioritas tim BPK.'],
        ['value' => 5, 'name' => 'Kritis/Darurat', 'title' => 'Ancaman Langsung',
            'description' => 'Ancaman langsung terhadap keutuhan struktur atau situs; butuh respons darurat dan pengamanan area segera.'],
    ];

    public function create()
    {
        return view('reports.create', [
            'categories' => collect(DamageCategory::cases())->map(fn (DamageCategory $c) => [
                'value' => $c->value,
                'label' => $c->label(),
                'description' => $c->description(),
                'icon' => $c->icon(),
            ]),
            'initial' => [
                'sites' => HeritageSite::orderBy('name')
                    ->get(['id', 'name', 'registration_id', 'heritage_note', 'latitude', 'longitude']),
                'levels' => self::SEVERITY_LEVELS,
                'old' => old(), // restores the form after a validation error
            ],
        ]);
    }

    public function store(StoreDamageReportRequest $request)
    {
        $isDraft = $request->isDraft();

        DB::transaction(function () use ($request, $isDraft) {
            $report = DamageReport::create([
                ...$request->safe()->only([
                    'heritage_site_id', 'latitude', 'longitude', 'gps_accuracy_m',
                    'category', 'severity', 'description',
                ]),
                'user_id' => $request->user()->id,
                'is_anonymous' => $request->boolean('is_anonymous'),
                'status' => $isDraft ? 'draft' : 'submitted',
                'declaration_accepted_at' => $isDraft ? null : now(),
                'submitted_at' => $isDraft ? null : now(),
            ]);

            foreach ($request->file('photos', []) as $file) {
                $sizeKb = (int) ceil($file->getSize() / 1024);
                $exif = ExifReader::read($file);

                $valid = $exif['lat'] !== null && $report->latitude !== null
                    && ExifReader::distanceMeters(
                        $exif['lat'], $exif['lng'],
                        (float) $report->latitude, (float) $report->longitude,
                    ) <= self::EXIF_MAX_DISTANCE_M;

                $report->photos()->create([
                    'path' => $file->store("reports/{$report->id}", 'public'),
                    'original_name' => $file->getClientOriginalName(),
                    'size_kb' => $sizeKb,
                    'exif_valid' => $valid,
                    'exif_latitude' => $exif['lat'],
                    'exif_longitude' => $exif['lng'],
                    'taken_at' => $exif['taken_at'],
                ]);
            }
        });

        return redirect()
            ->route('reports.create')
            ->with('status', $isDraft
                ? 'Draf laporan tersimpan.'
                : 'Laporan terkirim dan menunggu verifikasi BPK.');
    }
}
