<?php

namespace App\Filament\Resources\Permohonans\RelationManagers;

use App\Enums\HasilPemeriksaan;
use App\Enums\StatusPemeriksaan;
use App\Filament\Concerns\HasFileViewAction;
use App\Models\PemeriksaanLapangan;
use App\Services\PemeriksaanLapanganPdfService;
use App\Services\PemeriksaanLapanganNumberService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PemeriksaanLapanganRelationManager extends RelationManager
{
    use HasFileViewAction;

    protected static string $relationship = 'pemeriksaanLapangan';
    protected static ?string $title = 'Pemeriksaan Lapangan';
    protected static ?string $modelLabel = 'Pemeriksaan Lapangan';
    protected static ?string $pluralModelLabel = 'Pemeriksaan Lapangan';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return ! auth()->user()->hasRole('pemohon');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Waktu dan Tim Pemeriksa')
                ->description('Isi berdasarkan pemeriksaan yang benar-benar dilakukan di lokasi.')
                ->columns(1)
                ->schema([
                    DatePicker::make('tanggal_pemeriksaan')
                        ->label('Tanggal Pemeriksaan')
                        ->required()
                        ->native(false)
                        ->maxDate(now()),
                    TimePicker::make('waktu_mulai')->label('Waktu Mulai')->required()->seconds(false),
                    TimePicker::make('waktu_selesai')->label('Waktu Selesai')->required()->seconds(false),
                    TextInput::make('cuaca')
                        ->label('Kondisi Cuaca')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('Cerah / Mendung / Hujan'),
                    TextInput::make('nama_tim')
                        ->label('Petugas / Anggota Tim Pemeriksa')
                        ->required()
                        ->maxLength(500)
                        ->placeholder('Contoh: Budi Santoso, S.T.; Siti Rahmawati, S.Si.'),
                ]),

            Section::make('Lokasi Hasil Peninjauan')
                ->description('Koordinat sebaiknya diambil dari perangkat petugas saat berada di lokasi.')
                ->columns(1)
                ->schema([
                    TextInput::make('latitude')->label('Latitude')->numeric()->required()->minValue(-90)->maxValue(90),
                    TextInput::make('longitude')->label('Longitude')->numeric()->required()->minValue(-180)->maxValue(180),
                    Textarea::make('alamat_lokasi')
                        ->label('Alamat / Patokan Lokasi')
                        ->required()
                        ->rows(3)
                        ->maxLength(1000),
                ]),

            Section::make('Checklist Pemeriksaan Lapangan')
                ->description('Seluruh unsur harus ditandai sebelum BAP dapat difinalisasi.')
                ->columns(1)
                ->schema([
                    CheckboxList::make('checklist')
                        ->label('Unsur yang telah diverifikasi di lapangan')
                        ->options([
                            'identitas_lokasi' => 'Identitas dan lokasi tanah sesuai dengan dokumen permohonan',
                            'batas_bidang' => 'Batas-batas bidang tanah dapat diidentifikasi',
                            'akses_jalan' => 'Akses/jaringan jalan dapat diidentifikasi',
                            'penggunaan_eksisting' => 'Penggunaan tanah eksisting telah diverifikasi',
                            'kondisi_fisik' => 'Kondisi fisik lokasi telah didokumentasikan',
                            'denah_lokasi' => 'Denah/site plan sesuai dengan kondisi lapangan',
                            'koordinat' => 'Titik koordinat lokasi telah diverifikasi',
                            'lingkungan' => 'Kondisi lingkungan sekitar telah diperiksa',
                        ])
                        ->columns(1)
                        ->required()
                        ->minItems(8),
                ]),

            Section::make('Hasil Pemeriksaan')
                ->columns(1)
                ->schema([
                    Select::make('hasil')
                        ->label('Kesimpulan Pemeriksaan')
                        ->options(HasilPemeriksaan::class)
                        ->required()
                        ->native(false),
                    Textarea::make('kondisi_eksisting')
                        ->label('Kondisi Eksisting')
                        ->required()
                        ->rows(7)
                        ->maxLength(10000),
                    Textarea::make('temuan')
                        ->label('Temuan di Lapangan')
                        ->required()
                        ->rows(7)
                        ->maxLength(10000),
                    Textarea::make('kesimpulan')
                        ->label('Kesimpulan')
                        ->required()
                        ->rows(6)
                        ->maxLength(10000),
                    Textarea::make('rekomendasi')
                        ->label('Rekomendasi / Tindak Lanjut')
                        ->required()
                        ->rows(6)
                        ->maxLength(10000),
                ]),

            Section::make('Dokumentasi Lapangan')
                ->description('Minimal 2 foto dari sudut berbeda. Setiap foto wajib diberi jenis tampilan dan caption agar bukti lapangan mudah dipahami dalam BAP.')
                ->columns(1)
                ->schema([
                    Repeater::make('foto_lapangan')
                        ->label('Foto Kondisi Lapangan')
                        ->schema([
                            Select::make('jenis')
                                ->label('Jenis / Arah Tampilan')
                                ->options([
                                    'tampak_utara' => 'Tampak Utara',
                                    'tampak_selatan' => 'Tampak Selatan',
                                    'tampak_timur' => 'Tampak Timur',
                                    'tampak_barat' => 'Tampak Barat',
                                    'objek_utama' => 'Objek / Kondisi Utama',
                                    'akses_jalan' => 'Akses / Jalan',
                                    'batas_bidang' => 'Batas Bidang / Patok',
                                    'pendukung' => 'Foto Pendukung',
                                ])
                                ->required()
                                ->native(false),
                            FileUpload::make('path')
                                ->label('Foto')
                                ->disk('private')
                                ->directory('pemeriksaan-lapangan/foto')
                                ->visibility('private')
                                ->image()
                                ->required()
                                ->maxSize(5120)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->downloadable(false)
                                ->openable(false),
                            Textarea::make('caption')
                                ->label('Caption / Keterangan Foto')
                                ->required()
                                ->rows(3)
                                ->maxLength(500)
                                ->placeholder('Contoh: Tampak kondisi bidang tanah dari arah selatan, terlihat akses jalan lingkungan.'),
                        ])
                        ->itemLabel(fn (array $state): string => filled($state['caption'] ?? null)
                            ? Str::limit($state['caption'], 55)
                            : 'Dokumentasi Lapangan')
                        ->collapsible()
                        ->cloneable(false)
                        ->reorderable()
                        ->addActionLabel('Tambah Foto Lapangan')
                        ->minItems(2)
                        ->maxItems(12)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tanggal_pemeriksaan')
            ->columns([
                TextColumn::make('nomor_bap')->label('Nomor BAP')->searchable()->placeholder('Belum difinalisasi'),
                TextColumn::make('versi')->label('Versi')->formatStateUsing(fn ($state): string => 'BAP ' . ($state ?: 1)),
                TextColumn::make('tanggal_pemeriksaan')->label('Tanggal')->date('d/m/Y')->sortable(),
                TextColumn::make('hasil')->label('Hasil')->badge(),
                TextColumn::make('status')->label('Status BAP')->badge(),
                TextColumn::make('alasan_pembaruan')
                    ->label('Alasan Pembaruan')
                    ->limit(45)
                    ->tooltip(fn (PemeriksaanLapangan $record): ?string => $record->alasan_pembaruan ?: null)
                    ->toggleable(),
                TextColumn::make('dibuatOleh.name')->label('Petugas')->placeholder('-'),
                TextColumn::make('difinalisasi_pada')->label('Final')->dateTime('d/m/Y H:i')->placeholder('-')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status BAP')->options(StatusPemeriksaan::class),
                SelectFilter::make('hasil')->label('Hasil')->options(HasilPemeriksaan::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Isi Pemeriksaan Lapangan')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->visible(fn (): bool => auth()->user()->hasAnyRole(['tim_teknis', 'admin'])
                        && ! $this->getOwnerRecord()->pemeriksaanLapangan()->exists())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['dibuat_oleh'] = Auth::id();
                        $data['status'] = StatusPemeriksaan::Draf;
                        $data['versi'] = 1;
                        return $data;
                    })
                    ->after(fn () => Notification::make()->success()->title('Pemeriksaan disimpan sebagai draf')->body('Periksa kembali seluruh data dan finalisasi jika sudah benar.')->send()),

                Action::make('perbarui_bap_final')
                    ->label('Perbarui BAP Final')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(function (): bool {
                        if (! auth()->user()->hasAnyRole(['tim_teknis', 'admin'])) return false;
                        $owner = $this->getOwnerRecord();
                        $hasDraft = $owner->pemeriksaanLapangan()->where('status', StatusPemeriksaan::Draf)->exists();
                        return ! $hasDraft && $owner->pemeriksaanLapangan()->where('status', StatusPemeriksaan::Final)->exists();
                    })
                    ->form([
                        Textarea::make('alasan_pembaruan')
                            ->label('Alasan Pembaruan BAP Final')
                            ->required()->rows(5)->maxLength(2000)
                            ->helperText('Wajib dijelaskan, misalnya koreksi data, perubahan kondisi lapangan, atau instruksi pejabat berwenang.'),
                    ])
                    ->modalHeading('Perbarui BAP Final')
                    ->modalDescription('BAP final sebelumnya tetap menjadi arsip. Sistem akan membuat versi baru sebagai draf.')
                    ->modalSubmitActionLabel('Buat Draf Pembaruan')
                    ->action(function (array $data): void {
                        $owner = $this->getOwnerRecord();
                        $record = $owner->pemeriksaanLapangan()->where('status', StatusPemeriksaan::Final)->orderByDesc('versi')->firstOrFail();
                        $nextVersion = ((int) $owner->pemeriksaanLapangan()->max('versi')) + 1;
                        $payload = $record->only(['permohonan_id','tanggal_pemeriksaan','waktu_mulai','waktu_selesai','nama_tim','cuaca','latitude','longitude','alamat_lokasi','checklist','kondisi_eksisting','temuan','kesimpulan','rekomendasi','hasil','foto_lapangan']);
                        $payload['dibuat_oleh'] = Auth::id();
                        $payload['status'] = StatusPemeriksaan::Draf;
                        $payload['versi'] = $nextVersion;
                        $payload['revisi_dari_id'] = $record->id;
                        $payload['alasan_pembaruan'] = $data['alasan_pembaruan'];
                        $payload['nomor_bap'] = null; $payload['generated_bap_path'] = null;
                        $payload['difinalisasi_oleh'] = null; $payload['difinalisasi_pada'] = null;
                        $payload['foto_lapangan'] = collect($payload['foto_lapangan'] ?? [])->map(function ($photo) {
                            if (! is_array($photo) || empty($photo['path'])) return $photo;
                            $source=$photo['path'];
                            if (! Storage::disk('private')->exists($source)) return $photo;
                            $target='pemeriksaan-lapangan/foto/revisi/'.Str::uuid().'-'.basename($source);
                            Storage::disk('private')->copy($source,$target); $photo['path']=$target; return $photo;
                        })->all();
                        $owner->pemeriksaanLapangan()->create($payload);
                        Notification::make()->success()->title('Draf pembaruan BAP dibuat')->body('BAP final lama tetap menjadi arsip. Silakan edit dan finalisasi versi baru.')->send();
                    }),
            ])
            ->recordActions([
                static::fileViewAction(attribute: 'generated_bap_path', label: 'Buka BAP', name: 'buka_bap'),
                static::fileDownloadAction(attribute: 'generated_bap_path', label: 'Unduh BAP', name: 'unduh_bap'),
                Action::make('cetak_bap')
                    ->label('Cetak BAP')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->visible(fn (PemeriksaanLapangan $record): bool => filled($record->generated_bap_path))
                    ->disabled(fn (PemeriksaanLapangan $record): bool => ! Storage::disk('private')->exists($record->generated_bap_path))
                    ->url(fn (PemeriksaanLapangan $record): ?string => Storage::disk('private')->exists($record->generated_bap_path)
                        ? Storage::disk('private')->temporaryUrl($record->generated_bap_path, now()->addMinutes(10))
                        : null)
                    ->openUrlInNewTab()
                    ->tooltip('Buka PDF di browser, lalu gunakan dialog Cetak browser.'),

                Action::make('finalisasi')
                    ->label('Finalisasi & Generate BAP')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->visible(fn (PemeriksaanLapangan $record): bool => ! $record->isFinal() && auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Finalisasi Pemeriksaan Lapangan')
                    ->modalDescription('Setelah finalisasi, formulir pemeriksaan tidak dapat diedit. Pastikan checklist, koordinat, temuan, dan dokumentasi sudah benar.')
                    ->action(function (PemeriksaanLapangan $record, PemeriksaanLapanganPdfService $pdfService): void {
                        $record->load('permohonan');
                        $record->update([
                            'status' => StatusPemeriksaan::Final,
                            'nomor_bap' => app(PemeriksaanLapanganNumberService::class)->generate(now()),
                            'difinalisasi_oleh' => Auth::id(),
                            'difinalisasi_pada' => now(),
                        ]);
                        $record->refresh();
                        $path = $pdfService->store($record);
                        $record->update(['generated_bap_path' => $path]);

                        Notification::make()->success()->title('BAP berhasil difinalisasi')->body('Form pemeriksaan kini terkunci. BAP final tersedia untuk dibuka, diunduh, dan dicetak.')->send();
                    }),

                Action::make('hapus_draf')
                    ->label('Hapus Draf')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (PemeriksaanLapangan $record): bool => ! $record->isFinal() && auth()->user()->hasRole('admin'))
                    ->requiresConfirmation()
                    ->action(function (PemeriksaanLapangan $record): void {
                        self::deleteDraftFiles($record);
                        $record->delete();
                    }),

                ActionGroup::make([
                    EditAction::make()
                        ->label('Edit Draf')
                        ->visible(fn (PemeriksaanLapangan $record): bool => ! $record->isFinal() && auth()->user()->hasAnyRole(['tim_teknis', 'admin'])),
                    DeleteAction::make()
                        ->label('Hapus Draf')
                        ->visible(fn (PemeriksaanLapangan $record): bool => ! $record->isFinal() && auth()->user()->hasRole('admin'))
                        ->before(function (PemeriksaanLapangan $record): void {
                            self::deleteDraftFiles($record);
                        }),
                ])
                    ->label('Lainnya')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading('Belum ada pemeriksaan lapangan')
            ->emptyStateDescription('Petugas dapat membuat pemeriksaan baru setelah peninjauan lokasi. BAP yang telah final hanya dapat diperbarui melalui versi baru dengan alasan yang tercatat.')
            ->emptyStateIcon('heroicon-o-map')
            ->defaultSort('versi', 'desc');
    }

    private static function deleteDraftFiles(PemeriksaanLapangan $record): void
    {
        foreach ($record->foto_lapangan ?? [] as $photo) {
            $path = is_array($photo) ? ($photo['path'] ?? null) : $photo;
            if ($path) {
                Storage::disk('private')->delete($path);
            }
        }

        if ($record->generated_bap_path) {
            Storage::disk('private')->delete($record->generated_bap_path);
        }
    }
}
