<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('is_active')
                    ->label('Account Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (bool $state): string => $state ? 'Active' : 'Inactive'
                    )
                    ->color(
                        fn (bool $state): string => $state ? 'success' : 'danger'
                    ),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Account Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
