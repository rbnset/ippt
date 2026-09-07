<?php

namespace App\Filament\Resources\Permohonans\RelationManagers;

use App\Enums\HasilRisalah;
use App\Filament\Concerns\HasFileViewAction;
use App\Models\RisalahPertimbangan;
use App\Enums\StatusPermohonan;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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

class RisalahPertimbanganRelationManager extends RelationManager
{
    use HasFileViewAction;

    protected static string $relationship = 'risalahPertimbangan';

    protected static ?string $title = 'Risalah Pertimbangan Teknis';
    protected static ?string $modelLabel = 'Risalah Pertimbangan';
    protected static ?string $pluralModelLabel = 'Risalah Pertimbangan';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'pemohon', 'staff', 'kabid', 'kadis']);
    }

    public function isReadOnly(): bool
    {
        return ! auth()->user()->hasAnyRole(['staff', 'admin']);
    }

    public static function getBadge(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->risalahPertimbangan()->exists() ? '1' : null;
    }

    public static function getBadgeColor(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): ?string
    {
        return match ($ownerRecord->risalahPertimbangan?->status) {
            'diterima' => 'success',
            'menunggu_dokumen' => 'warning',
            default => null,
        };
    }

    public static function getBadgeTooltip(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): ?string
    {
        return match ($ownerRecord->risalahPertimbangan?->status) {
            'diterima' => 'Risalah Pertimbangan: Diterima',
            'menunggu_dokumen' => 'Risalah Pertimbangan: Menunggu dokumen eksternal',
            default => null,
        };
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Penerimaan Dokumen Eksternal')
                ->description('Risalah Pertimbangan Teknis diterbitkan oleh Kantor Pertanahan Kota Yogyakarta. Staff/Admin hanya mencatat metadata dokumen yang diterima dan menyimpan salinannya; aplikasi tidak menyusun atau menerbitkan Risalah.')
                ->schema([
                    TextInput::make('nomor_risalah')
                        ->label('Nomor Risalah')
                        ->required()
                        ->maxLength(100)
                        ->unique(table: 'risalah_pertimbangan', column: 'nomor_risalah', ignoreRecord: true)
                        ->helperText('Masukkan nomor persis sebagaimana tercantum pada dokumen resmi Kantor Pertanahan.'),

                    DatePicker::make('tanggal_risalah')
                        ->label('Tanggal Risalah')
                        ->required()
                        ->native(false)
                        ->maxDate(now()),

                    Select::make('hasil')
                        ->label('Hasil Pertimbangan')
                        ->options(HasilRisalah::class)
                        ->native(false)
                        ->required()
                        ->helperText('Salin hasil pertimbangan sebagaimana tercantum pada Risalah resmi.'),

                    FileUpload::make('lokasi_file')
                        ->label('Dokumen Risalah Pertimbangan Teknis')
                        ->disk('private')
                        ->directory('risalah-pertimbangan')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->downloadable(false)
                        ->openable(false)
                        ->required(fn (string $operation): bool => $operation === 'edit')
                        ->helperText('PDF Risalah yang diterima dari Kantor Pertanahan. Maksimal 10 MB.')
                        ->columnSpanFull(),

                    FileUpload::make('lokasi_file_peta')
                        ->label('Lampiran Peta Risalah (opsional)')
                        ->disk('private')
                        ->directory('risalah-pertimbangan/peta')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(20480)
                        ->downloadable(false)
                        ->openable(false)
                        ->helperText('Isi hanya jika peta diterima sebagai berkas terpisah. Maksimal 20 MB.')
                        ->columnSpanFull(),

                    Textarea::make('catatan')
                        ->label('Catatan Penerimaan Internal')
                        ->rows(4)
                        ->maxLength(5000)
                        ->helperText('Catatan administrasi penerimaan, bukan pengganti substansi Risalah resmi.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nomor_risalah')
            ->columns([
                TextColumn::make('nomor_risalah')
                    ->label('Nomor Risalah')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal_risalah')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('hasil')
                    ->label('Hasil')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'diterima' => 'Diterima',
                        'menunggu_dokumen' => 'Menunggu Dokumen Eksternal',
                        default => (string) $state,
                    }),

                TextColumn::make('nomor_ba_pembahasan')
                    ->label('BA Pembahasan')
                    ->placeholder('Tidak dicatat pada ringkasan sistem')
                    ->toggleable(),

                TextColumn::make('diterimaOleh.name')
                    ->label('Diterima Oleh')
                    ->placeholder('Tidak dicatat pada ringkasan sistem')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Diterima')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('hasil')
                    ->label('Hasil')
                    ->options(HasilRisalah::class),
            ])
            ->headerActions([
                Action::make('terima_risalah')
                    ->label('Terima Risalah')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->visible(fn(): bool => auth()->user()->hasAnyRole(['staff', 'admin'])
                        && $this->getOwnerRecord()->status === StatusPermohonan::MenungguRisalah
                        && ! $this->getOwnerRecord()->risalahPertimbangan()->where('status', 'diterima')->exists())
                    ->form([
                        TextInput::make('nomor_risalah')->label('Nomor Risalah')->required()->maxLength(100),
                        DatePicker::make('tanggal_risalah')->label('Tanggal Risalah')->required()->native(false)->maxDate(now()),
                        Textarea::make('catatan')->label('Catatan Penerimaan')->rows(4)->maxLength(5000)->columnSpanFull(),
                        FileUpload::make('lokasi_file')
                            ->label('Dokumen Risalah Pertimbangan Teknis')
                            ->disk('private')->directory('risalah-pertimbangan')->visibility('private')
                            ->acceptedFileTypes(['application/pdf'])->maxSize(10240)->required()
                            ->downloadable(false)->openable(false)->columnSpanFull(),
                        FileUpload::make('lokasi_file_peta')
                            ->label('Lampiran Peta Risalah (opsional)')
                            ->disk('private')->directory('risalah-pertimbangan/peta')->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->maxSize(20480)
                            ->downloadable(false)->openable(false)->columnSpanFull(),
                        Select::make('hasil')->label('Hasil Pertimbangan')->options(HasilRisalah::class)->native(false)->required(),
                    ])
                    ->modalHeading('Terima Risalah Pertimbangan Teknis')
                    ->modalDescription('Risalah diterbitkan oleh Kantor Pertanahan Kota Yogyakarta. Sistem mencatat dokumen yang diterima dan menjadikannya dasar untuk tahap keputusan IPPT.')
                    ->modalSubmitActionLabel('Terima & Simpan')
                    ->action(function (array $data): void {
                        $permohonan = $this->getOwnerRecord();

                        if ($permohonan->status !== StatusPermohonan::MenungguRisalah) {
                            Notification::make()
                                ->danger()
                                ->title('Risalah belum masuk tahap penerimaan')
                                ->body('Penerimaan Risalah hanya dapat dilakukan setelah Rekomendasi Teknis disetujui dan permohonan berada pada tahap Menunggu Risalah.')
                                ->send();

                            return;
                        }

                        // Satu permohonan hanya memiliki satu record intake Risalah.
                        // Jika placeholder belum terbentuk karena data lama/recovery, buat otomatis di sini.
                        $risalah = RisalahPertimbangan::firstOrCreate(
                            ['permohonan_id' => $permohonan->id],
                            [
                                'status' => 'menunggu_dokumen',
                                'diminta_oleh' => Auth::id(),
                                'diminta_pada' => now(),
                                'lokasi_file' => null,
                            ],
                        );

                        if ($risalah->status !== 'menunggu_dokumen') {
                            Notification::make()
                                ->danger()
                                ->title('Risalah sudah diproses')
                                ->body('Dokumen Risalah pada permohonan ini sudah tidak berada pada tahap menunggu dokumen eksternal.')
                                ->send();

                            return;
                        }

                        $risalah->update([
                            'diterima_oleh' => Auth::id(),
                            'nomor_risalah' => $data['nomor_risalah'],
                            'tanggal_risalah' => $data['tanggal_risalah'],
                            'hasil' => $data['hasil'],
                            'lokasi_file' => $data['lokasi_file'],
                            'lokasi_file_peta' => $data['lokasi_file_peta'] ?? null,
                            'catatan' => $data['catatan'] ?? null,
                            'status' => 'diterima',
                            'diterima_pada' => now(),
                        ]);

                        $this->getOwnerRecord()->update(['status' => StatusPermohonan::Keputusan]);

                        Notification::make()
                            ->success()
                            ->title('Risalah berhasil diterima')
                            ->body('Risalah telah tercatat. Permohonan siap memasuki tahap penyusunan keputusan IPPT.')
                            ->send();

                        app(\App\Services\IpptWorkflowNotificationService::class)->pemohon($this->getOwnerRecord(), 'Risalah Pertimbangan diterima', 'Risalah Pertimbangan Teknis telah diterima dan proses berlanjut ke tahap keputusan IPPT.', 'success');
                        app(\App\Services\IpptWorkflowNotificationService::class)->roles([\App\Enums\UserRole::STAFF], 'Risalah Pertimbangan diterima', "Risalah {$data['nomor_risalah']} telah diterima. Permohonan siap dibuatkan keputusan IPPT.", 'info');
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    static::fileViewAction(
                        attribute: 'lokasi_file',
                        label: 'Lihat',
                        name: 'lihat_risalah',
                    ),
                    static::fileDownloadAction(
                        attribute: 'lokasi_file',
                        label: 'Unduh',
                        name: 'unduh_risalah',
                    ),
                    Action::make('print_risalah')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->visible(fn(RisalahPertimbangan $record): bool => filled($record->lokasi_file) && Storage::disk('private')->exists($record->lokasi_file))
                        ->url(fn(RisalahPertimbangan $record): string => Storage::disk('private')->temporaryUrl($record->lokasi_file, now()->addMinutes(10)))
                        ->openUrlInNewTab()
                        ->tooltip('Buka dokumen di browser, lalu gunakan dialog cetak.'),
                    Action::make('detail_risalah')
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->modalHeading('Detail Risalah Pertimbangan Teknis')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->infolist(fn(RisalahPertimbangan $record) => [
                            \Filament\Infolists\Components\TextEntry::make('nomor_risalah')->label('Nomor Risalah'),
                            \Filament\Infolists\Components\TextEntry::make('tanggal_risalah')->label('Tanggal')->date('d/m/Y')->placeholder('Belum dicatat'),
                            \Filament\Infolists\Components\TextEntry::make('status')->label('Status')->badge()->formatStateUsing(fn (?string $state): string => match ($state) {
                                'diterima' => 'Diterima',
                                'menunggu_dokumen' => 'Menunggu Dokumen Eksternal',
                                default => (string) $state,
                            }),
                            \Filament\Infolists\Components\TextEntry::make('hasil')->label('Hasil Pertimbangan')->badge()->placeholder('Belum dicatat'),
                            \Filament\Infolists\Components\TextEntry::make('permohonan.nomor_permohonan')->label('Nomor Permohonan')->placeholder('Tidak tersedia'),
                            \Filament\Infolists\Components\TextEntry::make('diterimaOleh.name')->label('Diterima Oleh')->placeholder('Belum diterima'),
                            \Filament\Infolists\Components\TextEntry::make('diterima_pada')->label('Diterima Pada')->dateTime('d/m/Y H:i')->placeholder('Belum diterima'),
                            \Filament\Infolists\Components\TextEntry::make('diminta_pada')->label('Menunggu Sejak')->dateTime('d/m/Y H:i')->placeholder('Tidak tersedia'),
                            \Filament\Infolists\Components\TextEntry::make('catatan')->label('Catatan Penerimaan Internal')->prose()->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('lokasi_file')->label('Dokumen')->placeholder('Belum diunggah'),
                            \Filament\Infolists\Components\TextEntry::make('lokasi_file_peta')->label('Lampiran Peta')->placeholder('Tidak ada'),
                        ])
                        ->modalWidth('4xl'),
                    EditAction::make()
                        ->label('Koreksi Data')
                        ->visible(fn(RisalahPertimbangan $record): bool => $record->status === 'diterima' && auth()->user()->hasAnyRole(['staff', 'admin'])),
                    DeleteAction::make()
                        ->visible(fn(): bool => auth()->user()->hasRole('admin'))
                        ->before(function (RisalahPertimbangan $record): void {
                            foreach (['lokasi_file', 'lokasi_file_peta'] as $attribute) {
                                $path = $record->{$attribute};
                                if ($path && Storage::disk('private')->exists($path)) {
                                    Storage::disk('private')->delete($path);
                                }
                            }
                        }),
                ])
                    ->label('Lainnya')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading('Menunggu Risalah dari Kantor Pertanahan')
            ->emptyStateDescription('Rekomendasi Teknis telah disetujui. Sistem membuat antrean penerimaan secara otomatis. Staff tidak membuat Risalah; Staff hanya menerima dokumen resmi dari Kantor Pertanahan dan mengunggah salinannya.')
            ->emptyStateIcon('heroicon-o-scale')
            ->defaultSort('tanggal_risalah', 'desc');
    }
}
