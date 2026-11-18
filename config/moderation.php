<?php

return [
    /*
    | Fallback otorisasi moderator selama spatie/laravel-permission belum
    | terpasang. Isi MODERATOR_EMAILS di .env, dipisah koma.
    | Setelah role tersedia, Gate "moderate-reports" otomatis memakai hasRole().
    */
    'moderator_emails' => array_values(array_filter(array_map(
        fn ($email) => strtolower(trim($email)),
        explode(',', (string) env('MODERATOR_EMAILS', '')),
    ))),

    /** Jumlah kartu per halaman di antrean. */
    'queue_per_page' => 15,

    /** Severity minimum yang dihitung sebagai "Darurat". */
    'emergency_severity' => 4,

    /** Kandidat duplikat (tanpa AI): situs sama, radius & selisih waktu kirim. */
    'duplicate_radius_m' => 50,
    'duplicate_window_days' => 7,
];
