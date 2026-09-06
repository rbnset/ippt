<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')->label('Foto')->disk('public')->circular(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status_akun')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'aktif' ? 'Aktif' : 'Nonaktif')
                    ->color(fn (?string $state): string => $state === 'aktif' ? 'success' : 'danger'),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \App\Enums\UserRole
                        ? $state->getLabel()
                        : (\App\Enums\UserRole::tryFrom((string) $state)?->getLabel() ?? (string) $state))
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
