<?php

namespace App\Filament\Resources\Permohonans\RelationManagers;

use App\Enums\JenisDokumen;
use App\Enums\StatusDokumen;
use App\Filament\Concerns\HasFileViewAction;
use App\Models\DokumenPermohonan;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DokumenPermohonansRelationManager extends RelationManager
{
    use HasFileViewAction;

    protected static string $relationship = 'dokumenPermohonan';

    protected static ?string $title = 'Dokumen Persyaratan';

    protected static ?string $modelLabel = 'Dokumen';

    protected static ?string $pluralModelLabel = 'Dokumen Persyaratan';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dokumen Persyaratan')
                    ->description('Unggah dokumen sesuai jenis yang dipersyaratkan.')
                    ->schema([
                        Select::make('jenis_dokumen')
                            ->label('Jenis Dokumen')
                            ->options(JenisDokumen::class)
                            ->required()
                            ->searchable()
                            ->native(false),

                        FileUpload::make('lokasi_file')
                            ->label('File Dokumen')
                            ->disk('private')
                            ->directory('dokumen-ippt')
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                            ])
                            ->maxSize(10240)
                            ->downloadable(false)
                            ->openable(false)
                            ->required()
                            ->helperText('Format PDF, JPG, atau PNG. Maksimal 10 MB.'),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_file')
            ->columns([
                TextColumn::make('jenis_dokumen')
                    ->label('Jenis Dokumen')
                    ->badge()
                    ->searchable(),

                TextColumn::make('nama_file')
                    ->label('Nama File')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn(DokumenPermohonan $record): ?string => $record->nama_file),

                TextColumn::make('tipe_file')
                    ->label('Tipe')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn(?string $state): string => strtoupper($state ?? '-')),

                TextColumn::make('ukuran_file')
                    ->label('Ukuran')
                    ->formatStateUsing(fn(?int $state): string => $state
                        ? number_format($state / 1024, 2) . ' KB'
                        : '-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('diunggahOleh.name')
                    ->label('Diunggah Oleh')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Tanggal Upload')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis_dokumen')
                    ->label('Jenis Dokumen')
                    ->options(JenisDokumen::class),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusDokumen::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Dokumen')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['diunggah_oleh'] = Auth::id();
                        $data['status'] = StatusDokumen::Menunggu->value;

                        if (! empty($data['lokasi_file'])) {
                            $data['nama_file'] = basename($data['lokasi_file']);
                            $data['tipe_file'] = strtolower(
                                pathinfo($data['lokasi_file'], PATHINFO_EXTENSION)
                            );

                            if (Storage::disk('private')->exists($data['lokasi_file'])) {
                                $data['ukuran_file'] = Storage::disk('private')->size($data['lokasi_file']);
                            }
                        }

                        return $data;
                    })
                    ->after(function (DokumenPermohonan $record): void {
                        Notification::make()
                            ->success()
                            ->title('Dokumen berhasil diunggah')
                            ->body("Dokumen \"{$record->nama_file}\" berhasil ditambahkan.")
                            ->send();
                    }),
            ])
            ->recordActions([
                static::fileViewAction(label: 'Lihat'),

                // Hanya staff/admin yang boleh memverifikasi dokumen.
                // Pemohon (pengunggah) tidak boleh menyetujui dokumennya sendiri.
                Action::make('terima')
                    ->label('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(DokumenPermohonan $record): bool => $record->status === StatusDokumen::Menunggu
                        && auth()->user()->hasAnyRole(['staff', 'admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Terima Dokumen')
                    ->modalDescription('Apakah dokumen ini sudah sesuai dan dapat diterima?')
                    ->action(function (DokumenPermohonan $record): void {
                        $record->update([
                            'status' => StatusDokumen::Diterima,
                            'catatan' => null,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Dokumen diterima')
                            ->body("Dokumen \"{$record->nama_file}\" telah diterima.")
                            ->send();
                    }),

                Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(DokumenPermohonan $record): bool => $record->status === StatusDokumen::Menunggu
                        && auth()->user()->hasAnyRole(['staff', 'admin']))
                    ->form([
                        Textarea::make('catatan')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(4)
                            ->maxLength(1000),
                    ])
                    ->modalHeading('Tolak Dokumen')
                    ->modalSubmitActionLabel('Tolak Dokumen')
                    ->action(function (DokumenPermohonan $record, array $data): void {
                        $record->update([
                            'status' => StatusDokumen::Ditolak,
                            'catatan' => $data['catatan'],
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Dokumen ditolak')
                            ->body("Dokumen \"{$record->nama_file}\" telah ditolak.")
                            ->send();
                    }),

                ActionGroup::make([
                    // Dokumen yang sudah diterima/ditolak sebaiknya tidak bisa diubah lagi
                    // kecuali oleh admin, supaya jejak verifikasi tetap terjaga.
                    EditAction::make()
                        ->visible(fn(DokumenPermohonan $record): bool => $record->status === StatusDokumen::Menunggu
                            || auth()->user()->hasRole('admin')),

                    DeleteAction::make()
                        ->visible(fn(DokumenPermohonan $record): bool => $record->status === StatusDokumen::Menunggu
                            || auth()->user()->hasRole('admin'))
                        ->before(function (DokumenPermohonan $record): void {
                            if ($record->lokasi_file && Storage::disk('private')->exists($record->lokasi_file)) {
                                Storage::disk('private')->delete($record->lokasi_file);
                            }
                        }),
                ])
                    ->label('Lainnya')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading('Belum ada dokumen')
            ->emptyStateDescription('Unggah dokumen persyaratan untuk permohonan ini.')
            ->emptyStateIcon('heroicon-o-document')
            ->defaultSort('created_at', 'desc');
    }
}
