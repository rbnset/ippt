<?php

declare(strict_types=1);
namespace App\Filament\Resources\Pemohons\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class VerificationHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'verificationHistories';
    protected static ?string $title = 'Riwayat Verifikasi Data';
    protected static ?string $modelLabel = 'Riwayat Verifikasi';
    protected static ?string $pluralModelLabel = 'Riwayat Verifikasi';
    public static function canViewForRecord($ownerRecord, string $pageClass): bool { return auth()->user()?->hasAnyRole(['admin','staff']) ?? false; }
    public function form(Schema $schema): Schema { return $schema->components([]); }
    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('versi_data')->label('Versi')->badge(),
            TextColumn::make('aksi')->label('Aksi')->badge(),
            TextColumn::make('status_sebelumnya')->label('Sebelumnya')->placeholder('—'),
            TextColumn::make('status_sesudahnya')->label('Sesudahnya'),
            TextColumn::make('catatan')->label('Catatan')->limit(70)->wrap()->placeholder('—'),
            TextColumn::make('dilakukanOleh.name')->label('Petugas')->placeholder('—'),
            TextColumn::make('dilakukan_pada')->label('Waktu')->dateTime('d/m/Y H:i')->sortable(),
        ])->recordActions([])->toolbarActions([])->defaultSort('dilakukan_pada','desc');
    }
}
