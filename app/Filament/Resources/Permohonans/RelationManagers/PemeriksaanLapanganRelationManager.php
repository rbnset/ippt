<?php

namespace App\Filament\Resources\Permohonans\RelationManagers;

use App\Enums\HasilPemeriksaan;
use App\Enums\StatusPemeriksaan;
use App\Filament\Concerns\HasFileViewAction;
use App\Models\PemeriksaanLapangan;
use App\Services\PemeriksaanLapanganPdfService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
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

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return ! auth()->user()->hasRole('pemohon');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Waktu dan Tim Pemeriksa')
                ->description('Isi data faktual saat petugas benar-benar melakukan peninjauan lokasi.')
                ->schema([
                    Grid::make(4)->schema([
                        DatePicker::make('tanggal_pemeriksaan')
                            ->label('Tanggal Pemeriksaan')
                            ->required()
                            ->native(false)
                            ->maxDate(now()),
                        TimePicker::make('waktu_mulai')->label('Mulai')->required()->seconds(false),
                        TimePicker::make('waktu_selesai')->label('Selesai')->required()->seconds(false),
                        TextInput::make('cuaca')->label('Cuaca')->required()->maxLength(100)->placeholder('Cerah / Mendung / Hujan'),
                    ]),
                    TextInput::make('nama_tim')
                        ->label('Petugas / Anggota Tim Pemeriksa')
                        ->required()
                        ->maxLength(500)
                        ->placeholder('Contoh: Budi Santoso, S.T.; Siti Rahmawati, S.Si.'),
                ]),

            Section::make('Lokasi Hasil Peninjauan')
                ->description('Koordinat sebaiknya diambil dari perangkat petugas saat berada di lokasi.')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('latitude')->label('Latitude')->numeric()->required()->minValue(-90)->maxValue(90),
                        TextInput::make('longitude')->label('Longitude')->numeric()->required()->minValue(-180)->maxValue(180),
                        TextInput::make('alamat_lokasi')->label('Alamat / Patokan Lokasi')->required()->maxLength(1000)->columnSpan(1),
                    ]),
                ]),

            Section::make('Checklist Pemeriksaan Lapangan')
                ->description('Semua unsur wajib diperiksa sebelum BAP dapat difinalisasi.')
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
                        ->columns(2)
                        ->required()
                        ->minItems(8),
                ]),

            Section::make('Hasil Pemeriksaan')
                ->schema([
                    Select::make('hasil')
                        ->label('Kesimpulan Pemeriksaan')
                        ->options(HasilPemeriksaan::class)
                        ->required()
                        ->native(false),
                    Textarea::make('kondisi_eksisting')
                        ->label('Kondisi Eksisting')
                        ->required()
                        ->rows(5)
                        ->maxLength(10000)
                        ->columnSpanFull(),
                    Textarea::make('temuan')
                        ->label('Temuan di Lapangan')
                        ->required()
                        ->rows(5)
                        ->maxLength(10000)
                        ->columnSpanFull(),
                    Textarea::make('kesimpulan')
                        ->label('Kesimpulan')
                        ->required()
                        ->rows(4)
                        ->maxLength(10000)
                        ->columnSpanFull(),
                    Textarea::make('rekomendasi')
                        ->label('Rekomendasi / Tindak Lanjut')
                        ->required()
                        ->rows(4)
                        ->maxLength(10000)
                        ->columnSpanFull(),
                ]),

            Section::make('Dokumentasi Lapangan')
                ->description('Yang diunggah adalah foto bukti lapangan. Sistem akan menyusun BAP PDF secara otomatis; PDF BAP manual tidak lagi diunggah.')
                ->schema([
                    FileUpload::make('foto_lapangan')
                        ->label('Foto Kondisi Lapangan')
                        ->disk('private')
                        ->directory('pemeriksaan-lapangan/foto')
                        ->visibility('private')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->maxFiles(12)
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->downloadable(false)
                        ->openable(false)
                        ->required()
                        ->minFiles(2)
                        ->helperText('Minimal 2 foto, maksimal 12 foto. Maksimal 5 MB per foto.')
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
                TextColumn::make('tanggal_pemeriksaan')->label('Tanggal')->date('d/m/Y')->sortable(),
                TextColumn::make('hasil')->label('Hasil')->badge(),
                TextColumn::make('status')->label('Status BAP')->badge(),
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
                    ->visible(fn (): bool => auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['dibuat_oleh'] = Auth::id();
                        $data['status'] = StatusPemeriksaan::Draf;
                        return $data;
                    })
                    ->after(fn () => Notification::make()->success()->title('Pemeriksaan disimpan sebagai draf')->body('Periksa kembali seluruh data dan finalisasi jika sudah benar.')->send()),
            ])
            ->recordActions([
                static::fileViewAction(attribute: 'generated_bap_path', label: 'Lihat BAP', name: 'lihat_bap'),
                static::fileDownloadAction(attribute: 'generated_bap_path', label: 'Unduh BAP', name: 'unduh_bap'),

                Action::make('finalisasi')
                    ->label('Finalisasi & Generate BAP')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->visible(fn (PemeriksaanLapangan $record): bool => ! $record->isFinal() && auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Finalisasi Pemeriksaan Lapangan')
                    ->modalDescription('Setelah finalisasi, data pemeriksaan dan BAP dianggap final. Pastikan checklist, temuan, koordinat, dan foto sudah benar.')
                    ->action(function (PemeriksaanLapangan $record, PemeriksaanLapanganPdfService $pdfService): void {
                        $record->load('permohonan');
                        $record->update([
                            'status' => StatusPemeriksaan::Final,
                            'nomor_bap' => 'BAP/IPPT/' . now()->format('Y') . '/' . str_pad((string) $record->id, 5, '0', STR_PAD_LEFT),
                            'difinalisasi_oleh' => Auth::id(),
                            'difinalisasi_pada' => now(),
                        ]);
                        $record->refresh();
                        $path = $pdfService->store($record);
                        $record->update(['generated_bap_path' => $path]);

                        Notification::make()->success()->title('BAP berhasil dibuat')->body('Pemeriksaan telah difinalisasi dan BAP PDF tersedia untuk dilihat/diunduh.')->send();
                    }),

                ActionGroup::make([
                    EditAction::make()
                        ->label('Edit Draf')
                        ->visible(fn (PemeriksaanLapangan $record): bool => ! $record->isFinal() && auth()->user()->hasAnyRole(['tim_teknis', 'admin'])),
                    DeleteAction::make()
                        ->visible(fn (PemeriksaanLapangan $record): bool => ! $record->isFinal() && auth()->user()->hasRole('admin'))
                        ->before(function (PemeriksaanLapangan $record): void {
                            foreach ($record->foto_lapangan ?? [] as $photo) {
                                Storage::disk('private')->delete($photo);
                            }
                            if ($record->generated_bap_path) {
                                Storage::disk('private')->delete($record->generated_bap_path);
                            }
                        }),
                ])
                    ->label('Lainnya')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading('Belum ada pemeriksaan lapangan')
            ->emptyStateDescription('Petugas dapat mengisi formulir pemeriksaan langsung setelah peninjauan lokasi.')
            ->emptyStateIcon('heroicon-o-map')
            ->defaultSort('tanggal_pemeriksaan', 'desc');
    }
}
