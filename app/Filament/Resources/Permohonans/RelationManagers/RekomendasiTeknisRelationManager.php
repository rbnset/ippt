<?php

namespace App\Filament\Resources\Permohonans\RelationManagers;

use App\Enums\HasilRekomendasi;
use App\Enums\StatusPersetujuan;
use App\Filament\Concerns\HasFileViewAction;
use App\Models\RekomendasiTeknis;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RekomendasiTeknisRelationManager extends RelationManager
{
    use HasFileViewAction;

    protected static string $relationship = 'rekomendasiTeknis';

    protected static ?string $title = 'Rekomendasi Teknis';

    protected static ?string $modelLabel = 'Rekomendasi Teknis';

    protected static ?string $pluralModelLabel = 'Rekomendasi Teknis';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return ! auth()->user()->hasRole('pemohon');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Rekomendasi')
                    ->schema([
                        TextInput::make('nomor_rekomendasi')
                            ->label('Nomor Rekomendasi')
                            ->maxLength(50)
                            ->placeholder('Contoh: 500.16/123')
                            ->unique(
                                table: 'rekomendasi_teknis',
                                column: 'nomor_rekomendasi',
                                ignoreRecord: true
                            ),

                        DatePicker::make('tanggal_rekomendasi')
                            ->label('Tanggal Rekomendasi')
                            ->native(false),

                        Select::make('hasil')
                            ->label('Hasil Rekomendasi')
                            ->options(HasilRekomendasi::class)
                            ->native(false),

                        Textarea::make('pertimbangan')
                            ->label('Pertimbangan Teknis')
                            ->rows(6)
                            ->maxLength(10000)
                            ->columnSpanFull(),

                        Textarea::make('ketentuan')
                            ->label('Ketentuan / Catatan Teknis')
                            ->rows(5)
                            ->maxLength(10000)
                            ->columnSpanFull(),

                        FileUpload::make('lokasi_file')
                            ->label('File Rekomendasi Teknis')
                            ->disk('private')
                            ->directory('rekomendasi-teknis')
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
            ->recordTitleAttribute('nomor_rekomendasi')
            ->columns([
                TextColumn::make('nomor_rekomendasi')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('tanggal_rekomendasi')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('hasil')
                    ->label('Hasil')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('disusunOleh.name')
                    ->label('Disusun Oleh')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('direviewOleh.name')
                    ->label('Direview Oleh')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('disetujuiOleh.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('lokasi_file')
                    ->label('File')
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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusPersetujuan::class),

                SelectFilter::make('hasil')
                    ->label('Hasil')
                    ->options(HasilRekomendasi::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Buat Rekomendasi')
                    ->icon('heroicon-o-document-plus')
                    ->visible(fn(): bool => auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['disusun_oleh'] = Auth::id();
                        $data['status'] = StatusPersetujuan::Draf->value;

                        return $data;
                    })
                    ->after(function (RekomendasiTeknis $record): void {
                        Notification::make()
                            ->success()
                            ->title('Rekomendasi teknis berhasil dibuat')
                            ->body('Rekomendasi teknis disimpan sebagai draf.')
                            ->send();
                    }),
            ])
            ->recordActions([
                // Diajukan oleh tim_teknis yang menyusun.
                Action::make('ajukan')
                    ->label('Ajukan Review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn(RekomendasiTeknis $record): bool => $record->status === StatusPersetujuan::Draf
                        && auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan Rekomendasi untuk Review')
                    ->modalDescription('Pastikan data rekomendasi teknis sudah benar sebelum diajukan.')
                    ->action(function (RekomendasiTeknis $record): void {
                        $record->update(['status' => StatusPersetujuan::Diajukan]);

                        Notification::make()
                            ->success()
                            ->title('Rekomendasi diajukan')
                            ->body('Rekomendasi teknis telah diajukan untuk review.')
                            ->send();
                    }),

                // Persetujuan/penolakan hanya wewenang kabid, bukan tim_teknis yang menyusun sendiri.
                Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(RekomendasiTeknis $record): bool => $record->status === StatusPersetujuan::Diajukan
                        && auth()->user()->hasAnyRole(['kabid', 'admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Rekomendasi Teknis')
                    ->modalDescription('Apakah Anda yakin rekomendasi teknis ini sudah dapat disetujui?')
                    ->action(function (RekomendasiTeknis $record): void {
                        $record->update([
                            'status' => StatusPersetujuan::Disetujui,
                            'disetujui_oleh' => Auth::id(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Rekomendasi disetujui')
                            ->body('Rekomendasi teknis telah disetujui.')
                            ->send();
                    }),

                Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(RekomendasiTeknis $record): bool => $record->status === StatusPersetujuan::Diajukan
                        && auth()->user()->hasAnyRole(['kabid', 'admin']))
                    ->form([
                        Textarea::make('catatan')
                            ->label('Catatan Penolakan')
                            ->required()
                            ->rows(4)
                            ->maxLength(2000),
                    ])
                    ->modalHeading('Tolak Rekomendasi Teknis')
                    ->modalSubmitActionLabel('Tolak Rekomendasi')
                    ->action(function (RekomendasiTeknis $record, array $data): void {
                        $record->update([
                            'status' => StatusPersetujuan::Ditolak,
                            'ketentuan' => $data['catatan'],
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Rekomendasi ditolak')
                            ->body('Rekomendasi teknis dikembalikan untuk diperbaiki.')
                            ->send();
                    }),

                static::fileViewAction(),

                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn(RekomendasiTeknis $record): bool => in_array($record->status->value, StatusPersetujuan::editableValues(), true)
                            && auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                        ->after(function (RekomendasiTeknis $record): void {
                            Notification::make()
                                ->success()
                                ->title('Rekomendasi berhasil diperbarui')
                                ->send();
                        }),

                    DeleteAction::make()
                        ->visible(fn(RekomendasiTeknis $record): bool => in_array($record->status->value, StatusPersetujuan::editableValues(), true)
                            && auth()->user()->hasRole('admin'))
                        ->before(function (RekomendasiTeknis $record): void {
                            if ($record->lokasi_file && Storage::disk('private')->exists($record->lokasi_file)) {
                                Storage::disk('private')->delete($record->lokasi_file);
                            }
                        }),
                ])
                    ->label('Lainnya')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading('Belum ada rekomendasi teknis')
            ->emptyStateDescription('Rekomendasi dibuat setelah pemeriksaan lapangan selesai.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->defaultSort('created_at', 'desc');
    }
}
