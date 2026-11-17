<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Pengguna / Identitas')
                    ->formatStateUsing(function ($state, $record) {
                        $initials = collect(explode(' ', trim($state)))
                            ->filter()
                            ->take(2)
                            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                            ->implode('');

                        return new HtmlString(
                            '<div style="display:flex;align-items:center;gap:12px;">' .
                                '<span style="width:38px;height:38px;min-width:38px;border-radius:50%;background:#dff2e5;color:#075b43;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;">' .
                                    e($initials) .
                                '</span>' .
                                '<span style="display:flex;flex-direction:column;gap:3px;">' .
                                    '<strong style="font-weight:600;color:#243b32;">' . e($state) . '</strong>' .
                                    '<small style="color:#718078;">' . e($record->email) . '</small>' .
                                '</span>' .
                            '</div>'
                        );
                    })
                    ->searchable(['name', 'email'])
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('Peran Otoritas')
                    ->badge()
                    ->separator(',')
                    ->color(fn ($state) => match (strtolower((string) $state)) {
                        'admin' => 'danger',
                        'instansi' => 'info',
                        'moderator' => 'warning',
                        'warga' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('provinces.province')
                    ->label('Yurisdiksi Wilayah')
                    ->badge()
                    ->separator(','),

                TextColumn::make('xp')
                    ->label('Aktivitas / XP')
                    ->state(fn ($record) => $record->getAttribute('xp') ?? '—'),

                TextColumn::make('is_active')
                    ->label('Status Akun')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Suspended')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status Akun')
                    ->options([
                        1 => 'Active',
                        0 => 'Suspended',
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label('Edit'),
            ]);
    }
}