<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.resources.users.pages.list-users';

    public function getTitle(): string
    {
        return 'Manajemen Pengguna';
    }

    public function getHeading(): ?string
    {
        return null;
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Peran'),

            'instansi' => Tab::make('Petugas BPK/BKSDA')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas(
                    'roles',
                    fn (Builder $roleQuery) => $roleQuery->where('name', 'instansi')
                )),

            'moderator' => Tab::make('Moderator')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas(
                    'roles',
                    fn (Builder $roleQuery) => $roleQuery->where('name', 'moderator')
                )),

            'warga' => Tab::make('Relawan/Citizen')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas(
                    'roles',
                    fn (Builder $roleQuery) => $roleQuery->where('name', 'warga')
                )),
        ];
    }
}