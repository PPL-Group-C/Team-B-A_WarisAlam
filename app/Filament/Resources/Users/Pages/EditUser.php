<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['assigned_provinces'] = $this->record
            ->provinces()
            ->pluck('province')
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $provinces = $this->data['assigned_provinces'] ?? [];

        $this->record->provinces()->delete();

        foreach ($provinces as $province) {
            $this->record->provinces()->create([
                'province' => $province,
            ]);
        }
    }
}
