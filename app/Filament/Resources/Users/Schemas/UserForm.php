<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),

                Select::make('roles')
                    ->label('User Role')
                    ->relationship(name: 'roles', titleAttribute: 'name')
                    ->multiple()
                    ->preload()
                    ->live()
                    ->required(),

                Select::make('assigned_provinces')
                    ->label('Assigned Provinces')
                    ->multiple()
                    ->options([
                        'Aceh' => 'Aceh',
                        'Sumatera Utara' => 'Sumatera Utara',
                        'Sumatera Barat' => 'Sumatera Barat',
                        'Riau' => 'Riau',
                        'Jambi' => 'Jambi',
                        'Sumatera Selatan' => 'Sumatera Selatan',
                        'Bengkulu' => 'Bengkulu',
                        'Lampung' => 'Lampung',
                        'Kepulauan Bangka Belitung' => 'Kepulauan Bangka Belitung',
                        'Kepulauan Riau' => 'Kepulauan Riau',
                        'DKI Jakarta' => 'DKI Jakarta',
                        'Jawa Barat' => 'Jawa Barat',
                        'Jawa Tengah' => 'Jawa Tengah',
                        'DI Yogyakarta' => 'DI Yogyakarta',
                        'Jawa Timur' => 'Jawa Timur',
                        'Banten' => 'Banten',
                        'Bali' => 'Bali',
                        'Nusa Tenggara Barat' => 'Nusa Tenggara Barat',
                        'Nusa Tenggara Timur' => 'Nusa Tenggara Timur',
                        'Kalimantan Barat' => 'Kalimantan Barat',
                        'Kalimantan Tengah' => 'Kalimantan Tengah',
                        'Kalimantan Selatan' => 'Kalimantan Selatan',
                        'Kalimantan Timur' => 'Kalimantan Timur',
                        'Kalimantan Utara' => 'Kalimantan Utara',
                        'Sulawesi Utara' => 'Sulawesi Utara',
                        'Sulawesi Tengah' => 'Sulawesi Tengah',
                        'Sulawesi Selatan' => 'Sulawesi Selatan',
                        'Sulawesi Tenggara' => 'Sulawesi Tenggara',
                        'Gorontalo' => 'Gorontalo',
                        'Sulawesi Barat' => 'Sulawesi Barat',
                        'Maluku' => 'Maluku',
                        'Maluku Utara' => 'Maluku Utara',
                        'Papua' => 'Papua',
                        'Papua Barat' => 'Papua Barat',
                        'Papua Selatan' => 'Papua Selatan',
                        'Papua Tengah' => 'Papua Tengah',
                        'Papua Pegunungan' => 'Papua Pegunungan',
                        'Papua Barat Daya' => 'Papua Barat Daya',
                    ])
                    ->searchable()
                    ->visible(function ($get): bool {
                        $roles = (array) ($get('roles') ?? []);
                        if (empty($roles)) {
                            return false;
                        }

                        $instansiId = \Spatie\Permission\Models\Role::where('name', 'instansi')->value('id');

                        return in_array($instansiId, array_map('intval', $roles), true)
                            || in_array('instansi', $roles, true);
                    }),

                Toggle::make('is_active')
                    ->label('Active Account')
                    ->default(true)
                    ->required(),
            ]);
    }
}
