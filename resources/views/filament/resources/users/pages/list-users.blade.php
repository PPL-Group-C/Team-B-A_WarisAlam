<x-filament-panels::page>
    <style>
        .waris-users {
            --waris-dark: #064e3b;
            --waris-light: #e7f5eb;
            --waris-pale: #f4faf5;
        }

        .waris-users .fi-ta-ctn {
            border: 1px solid #dce9df;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(6, 78, 59, .05);
        }

        .waris-users .fi-ta-header-cell {
            background: var(--waris-light);
            color: #315b48;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .waris-users .fi-ta-row:hover {
            background: var(--waris-pale);
        }

        .waris-users .fi-badge {
            border-radius: 999px;
            font-weight: 600;
        }

        .waris-users .fi-pagination {
            background: var(--waris-light);
            padding: 12px 18px;
        }

        .waris-users .waris-add-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--waris-dark);
            color: white;
            padding: 11px 18px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
    
        .waris-users .fi-tabs {
            justify-content: flex-start;
            margin-left: 0;
        }

        .waris-users .fi-tabs-item {
            border-radius: 9px;
        }
    </style>

    <div class="waris-users">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="mb-2 text-xs font-semibold uppercase tracking-widest text-emerald-800">
                    WARIS ALAM / ADMINISTRASI
                </div>

                <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                    Tata Kelola Akun & Penugasan Yurisdiksi
                </h2>

                <p class="mt-2 text-sm text-gray-500">
                    Manajemen hak akses, penugasan wilayah Balai Pelestarian Kebudayaan (BPK) & Balai KSDA.
                </p>
            </div>

            <a href="{{ \App\Filament\Resources\Users\UserResource::getUrl('create') }}"
               class="waris-add-button">+ Tambah Pengguna Baru</a>
        </div>

        {{ $this->content }}
    </div>
</x-filament-panels::page>