<?php

namespace App\Filament\Resources\Permohonans\RelationManagers;

use App\Enums\HasilPemeriksaan;
use App\Filament\Concerns\HasFileViewAction;
use App\Models\PemeriksaanLapangan;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

class PemeriksaanLapanganRelationManager extends RelationManager
{
    use HasFileViewAction;

    protected static string $relationship = 'pemeriksaanLapangan';

    protected static ?string $title = 'Pemeriksaan Lapangan';

    protected static ?string $modelLabel = 'Pemeriksaan Lapangan';

    protected static ?string $pluralModelLabel = 'Pemeriksaan Lapangan';

    /**
     * Dokumen pemeriksaan internal - tidak untuk role pemohon.
     */
    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return ! auth()->user()->hasRole('pemohon');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Pemeriksaan')
                    ->schema([
                        DatePicker::make('tanggal_pemeriksaan')
                            ->label('Tanggal Pemeriksaan')
                            ->required()
                            ->native(false)
                            ->maxDate(now()),

                        Select::make('hasil')
                            ->label('Hasil Pemeriksaan')
                            ->options(HasilPemeriksaan::class)
                            ->required()
                            ->native(false),

                        Textarea::make('hasil_pemeriksaan')
                            ->label('Hasil / Catatan Pemeriksaan')
                            ->required()
                            ->rows(5)
                            ->maxLength(5000)
                            ->columnSpanFull(),

                        FileUpload::make('lokasi_file_bap')
                            ->label('BAP / Dokumen Pendukung')
                            ->disk('private')
                            ->directory('pemeriksaan-lapangan')
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                            ])
                            ->maxSize(10240)
                            ->downloadable(false)
                            ->openable(false)
                            ->nullable()
                            ->helperText('Opsional. PDF, JPG, atau PNG. Maksimal 10 MB.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tanggal_pemeriksaan')
            ->columns([
                TextColumn::make('tanggal_pemeriksaan')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('hasil')
                    ->label('Hasil')
                    ->badge(),

                TextColumn::make('hasil_pemeriksaan')
                    ->label('Catatan Pemeriksaan')
                    ->limit(60)
                    ->wrap()
                    ->tooltip(fn(PemeriksaanLapangan $record): ?string => $record->hasil_pemeriksaan),

                TextColumn::make('dibuatOleh.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('-'),

                TextColumn::make('lokasi_file_bap')
                    ->label('Dokumen')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => $state ? 'Tersedia' : 'Tidak Ada')
                    ->color(fn(?string $state): string => $state ? 'success' : 'gray'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('hasil')
                    ->label('Hasil')
                    ->options(HasilPemeriksaan::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Pemeriksaan')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->visible(fn(): bool => auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['dibuat_oleh'] = Auth::id();

                        return $data;
                    })
                    ->after(function (PemeriksaanLapangan $record): void {
                        Notification::make()
                            ->success()
                            ->title('Pemeriksaan berhasil ditambahkan')
                            ->body('Data pemeriksaan lapangan berhasil disimpan.')
                            ->send();
                    }),
            ])
            ->recordActions([
                // Sebelumnya tidak ada aksi lihat file sama sekali di sini - ditambahkan.
                static::fileViewAction(attribute: 'lokasi_file_bap', label: 'Lihat BAP'),

                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn(): bool => auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                        ->after(function (PemeriksaanLapangan $record): void {
                            Notification::make()
                                ->success()
                                ->title('Pemeriksaan berhasil diperbarui')
                                ->body('Data pemeriksaan lapangan berhasil diperbarui.')
                                ->send();
                        }),

                    DeleteAction::make()
                        ->visible(fn(): bool => auth()->user()->hasRole('admin'))
                        ->before(function (PemeriksaanLapangan $record): void {
                            if ($record->lokasi_file_bap && Storage::disk('private')->exists($record->lokasi_file_bap)) {
                                Storage::disk('private')->delete($record->lokasi_file_bap);
                            }
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Pemeriksaan berhasil dihapus')
                        ),
                ])
                    ->label('Lainnya')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading('Belum ada pemeriksaan lapangan')
            ->emptyStateDescription('Tambahkan hasil pemeriksaan lapangan setelah kunjungan dilakukan.')
            ->emptyStateIcon('heroicon-o-map')
            ->defaultSort('tanggal_pemeriksaan', 'desc');
    }
}
