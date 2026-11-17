<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $provinces = $this->data['assigned_provinces'] ?? [];

        foreach ($provinces as $province) {
            $this->record->provinces()->create([
                'province' => $province,
            ]);
        }
    }
}
