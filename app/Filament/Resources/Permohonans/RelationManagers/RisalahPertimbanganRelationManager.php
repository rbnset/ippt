<?php

namespace App\Filament\Resources\Permohonans\RelationManagers;

use App\Enums\HasilRisalah;
use App\Filament\Concerns\HasFileViewAction;
use App\Models\RisalahPertimbangan;
use App\Enums\StatusPermohonan;
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

class RisalahPertimbanganRelationManager extends RelationManager
{
    use HasFileViewAction;

    protected static string $relationship = 'risalahPertimbangan';

    protected static ?string $title = 'Risalah Pertimbangan Teknis';
    protected static ?string $modelLabel = 'Risalah Pertimbangan';
    protected static ?string $pluralModelLabel = 'Risalah Pertimbangan';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return ! auth()->user()->hasRole('pemohon');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Penerimaan Risalah Pertimbangan Teknis')
                ->description('Risalah ini merupakan dokumen resmi pertimbangan teknis pertanahan dari Kantor Pertanahan. Sistem hanya mencatat dan menyimpan dokumen yang telah diterima, bukan menyusun risalah BPN.')
                ->schema([
                    TextInput::make('status')
                        ->hidden()
                        ->dehydrated(),

                    TextInput::make('nomor_risalah')
                        ->label('Nomor Risalah')
                        ->required()
                        ->maxLength(100)
                        ->unique(table: 'risalah_pertimbangan', column: 'nomor_risalah', ignoreRecord: true)
                        ->helperText('Masukkan nomor persis sebagaimana tercantum pada dokumen resmi BPN.'),

                    DatePicker::make('tanggal_risalah')
                        ->label('Tanggal Risalah')
                        ->required()
                        ->native(false)
                        ->maxDate(now()),

                    Textarea::make('dasar_penerbitan')
                        ->label('Dasar Penerbitan Risalah')
                        ->rows(4)
                        ->maxLength(10000)
                        ->helperText('Ringkasan dasar penerbitan sebagaimana tercantum pada risalah, misalnya permohonan, ketentuan tata ruang, dan berita acara terkait.')
                        ->columnSpanFull(),

                    TextInput::make('nomor_ba_peninjauan')
                        ->label('Nomor Berita Acara Peninjauan Lapangan')
                        ->maxLength(100)
                        ->placeholder('Jika tercantum pada risalah'),

                    DatePicker::make('tanggal_ba_peninjauan')
                        ->label('Tanggal Berita Acara Peninjauan Lapangan')
                        ->native(false)
                        ->maxDate(now()),

                    TextInput::make('nomor_ba_pembahasan')
                        ->label('Nomor Berita Acara Rapat Pembahasan')
                        ->maxLength(100)
                        ->placeholder('Jika tercantum pada risalah'),

                    DatePicker::make('tanggal_ba_pembahasan')
                        ->label('Tanggal Berita Acara Rapat Pembahasan')
                        ->native(false)
                        ->maxDate(now()),

                    Select::make('hasil')
                        ->label('Hasil Pertimbangan')
                        ->options(HasilRisalah::class)
                        ->native(false)
                        ->required(),

                    Textarea::make('pertimbangan_penguasaan_pemilikan')
                        ->label('Pertimbangan Penguasaan, Pemilikan, Penggunaan & Pemanfaatan Tanah')
                        ->rows(5)
                        ->maxLength(10000)
                        ->columnSpanFull(),

                    Textarea::make('ketentuan_syarat')
                        ->label('Ketentuan dan Syarat')
                        ->rows(5)
                        ->maxLength(10000)
                        ->columnSpanFull(),

                    Textarea::make('indikasi_sengketa')
                        ->label('Indikasi Sengketa, Konflik, atau Perkara Pertanahan')
                        ->rows(4)
                        ->maxLength(5000)
                        ->columnSpanFull(),

                    Textarea::make('pengakuan_hak')
                        ->label('Pengakuan Hak Atas Tanah dan Hak Keperdataan Lainnya')
                        ->rows(4)
                        ->maxLength(5000)
                        ->columnSpanFull(),

                    Textarea::make('kemampuan_tanah')
                        ->label('Kemampuan Tanah')
                        ->rows(4)
                        ->maxLength(5000)
                        ->columnSpanFull(),

                    Textarea::make('keterangan_lain')
                        ->label('Keterangan Lain')
                        ->rows(4)
                        ->maxLength(5000)
                        ->columnSpanFull(),

                    FileUpload::make('lokasi_file')
                        ->label('Dokumen Risalah Pertimbangan Teknis')
                        ->disk('private')
                        ->directory('risalah-pertimbangan')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->downloadable(false)
                        ->openable(false)
                        ->required()
                        ->helperText('Dokumen resmi BPN. PDF maksimal 10 MB.')
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
                        ->helperText('Jika peta disimpan sebagai berkas terpisah. Maksimal 20 MB.')
                        ->columnSpanFull(),

                    Textarea::make('catatan')
                        ->label('Catatan Penerimaan Internal')
                        ->rows(4)
                        ->maxLength(5000)
                        ->helperText('Catatan administrasi penerimaan, bukan pengganti isi risalah resmi.')
                        ->columnSpanFull(),
                ])
                ->columns(1),
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
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'diterima' => 'Diterima',
                        'menunggu_dokumen' => 'Menunggu Dokumen',
                        default => (string) $state,
                    }),

                TextColumn::make('nomor_ba_pembahasan')
                    ->label('BA Pembahasan')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('diterimaOleh.name')
                    ->label('Diterima Oleh')
                    ->placeholder('-')
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
                    ->label('Terima & Upload Risalah')
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
                    ->modalSubmitActionLabel('Simpan Risalah')
                    ->action(function (array $data): void {
                        RisalahPertimbangan::create([
                            'permohonan_id' => $this->getOwnerRecord()->id,
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
                            \Filament\Infolists\Components\TextEntry::make('tanggal_risalah')->label('Tanggal')->date('d/m/Y'),
                            \Filament\Infolists\Components\TextEntry::make('hasil')->label('Hasil')->badge(),
                            \Filament\Infolists\Components\TextEntry::make('dasar_penerbitan')->label('Dasar Penerbitan')->prose()->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('nomor_ba_peninjauan')->label('Nomor BA Peninjauan')->placeholder('-'),
                            \Filament\Infolists\Components\TextEntry::make('tanggal_ba_peninjauan')->label('Tanggal BA Peninjauan')->date('d/m/Y')->placeholder('-'),
                            \Filament\Infolists\Components\TextEntry::make('nomor_ba_pembahasan')->label('Nomor BA Pembahasan')->placeholder('-'),
                            \Filament\Infolists\Components\TextEntry::make('tanggal_ba_pembahasan')->label('Tanggal BA')->date('d/m/Y')->placeholder('-'),
                            \Filament\Infolists\Components\TextEntry::make('pertimbangan_penguasaan_pemilikan')->label('Pertimbangan Tanah')->prose()->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('ketentuan_syarat')->label('Ketentuan & Syarat')->prose()->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('indikasi_sengketa')->label('Indikasi Sengketa/Konflik/Perkara')->prose()->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('pengakuan_hak')->label('Pengakuan Hak')->prose()->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('kemampuan_tanah')->label('Kemampuan Tanah')->prose()->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('keterangan_lain')->label('Keterangan Lain')->prose()->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('catatan')->label('Catatan Internal')->prose()->columnSpanFull(),
                        ])
                        ->modalWidth('5xl'),
                    EditAction::make()
                        ->label('Koreksi Data')
                        ->visible(fn(): bool => auth()->user()->hasAnyRole(['staff', 'admin'])),
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
            ->emptyStateHeading('Risalah Pertimbangan Belum Diterima')
            ->emptyStateDescription('Setelah rekomendasi teknis disetujui, sistem menunggu Risalah Pertimbangan Teknis dari Kantor Pertanahan. Risalah ini menjadi salah satu dasar sebelum keputusan IPPT ditetapkan.')
            ->emptyStateIcon('heroicon-o-scale')
            ->defaultSort('tanggal_risalah', 'desc');
    }
}
