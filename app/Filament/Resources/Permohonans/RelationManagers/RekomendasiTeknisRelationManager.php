<?php

namespace App\Filament\Resources\Permohonans\RelationManagers;

use App\Enums\HasilRekomendasi;
use App\Enums\StatusPersetujuan;
use App\Filament\Concerns\HasFileViewAction;
use App\Models\RekomendasiTeknis;
use App\Services\RekomendasiTeknisPdfService;
use App\Services\RekomendasiTeknisNumberService;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RekomendasiTeknisRelationManager extends RelationManager
{
    use HasFileViewAction;

    protected static string $relationship = 'rekomendasiTeknis';
    protected static ?string $title = 'Rekomendasi Teknis';
    protected static ?string $modelLabel = 'Rekomendasi Teknis';
    protected static ?string $pluralModelLabel = 'Rekomendasi Teknis';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'pemohon', 'staff', 'tim_teknis', 'kabid', 'kadis']);
    }

    /**
     * Rekomendasi teknis dapat dibuat dan dikerjakan langsung dari halaman View Permohonan.
     * Hak akses tetap dibatasi oleh visibility/policy pada masing-masing action.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        // Badge menunjukkan satu proses rekomendasi, bukan jumlah revisi/dokumen.
        return $ownerRecord->rekomendasiTeknis()->exists() ? '1' : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        $record = $ownerRecord->rekomendasiTeknis;

        return match ($record?->status) {
            StatusPersetujuan::Disetujui => 'success',
            StatusPersetujuan::Diajukan => 'warning',
            StatusPersetujuan::Ditolak => 'danger',
            StatusPersetujuan::Draf => 'gray',
            default => null,
        };
    }

    public static function getBadgeTooltip(Model $ownerRecord, string $pageClass): ?string
    {
        $record = $ownerRecord->rekomendasiTeknis;

        return $record
            ? 'Rekomendasi teknis: ' . ($record->status?->getLabel() ?? 'tersedia')
            : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Rekomendasi')
                ->description('Nomor dan tanggal dokumen resmi yang akan menjadi bagian dari berkas pelayanan.')
                ->columns(1)
                ->schema([
                    TextInput::make('nomor_rekomendasi')
                        ->label('Nomor Rekomendasi Teknis')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Dibuat otomatis oleh sistem berdasarkan tahun dan nomor urut.'),
                    DatePicker::make('tanggal_rekomendasi')
                        ->label('Tanggal Rekomendasi')
                        ->required()
                        ->native(false),
                    TextInput::make('nomor_bap_referensi')
                        ->label('Nomor BAP Pemeriksaan Lapangan')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Terisi otomatis dari BAP lapangan final terbaru.'),
                ]),

            Section::make('Dasar dan Pertimbangan')
                ->columns(1)
                ->schema([
                    Textarea::make('dasar_hukum')
                        ->label('Dasar Hukum / Rujukan')
                        ->required()
                        ->rows(6)
                        ->maxLength(12000)
                        ->placeholder('Cantumkan peraturan, ketentuan tata ruang, surat/dokumen rujukan yang digunakan.'),
                    Textarea::make('pertimbangan')
                        ->label('Pertimbangan Teknis')
                        ->required()
                        ->rows(8)
                        ->maxLength(12000),
                    Textarea::make('kesesuaian_tata_ruang')
                        ->label('Kesesuaian Tata Ruang')
                        ->required()
                        ->rows(7)
                        ->maxLength(12000)
                        ->placeholder('Jelaskan hasil pengecekan terhadap rencana tata ruang/ketentuan pemanfaatan ruang yang berlaku.'),
                    Textarea::make('arahan_teknis')
                        ->label('Arahan Teknis / Persyaratan')
                        ->required()
                        ->rows(8)
                        ->maxLength(12000)
                        ->placeholder('Tuliskan arahan, batasan, persyaratan, atau tindak lanjut teknis yang harus diperhatikan.'),
                ]),

            Section::make('Kesimpulan Rekomendasi')
                ->columns(1)
                ->schema([
                    Select::make('hasil')
                        ->label('Hasil Rekomendasi')
                        ->options(HasilRekomendasi::class)
                        ->required()
                        ->native(false),
                    Textarea::make('ketentuan')
                        ->label('Ketentuan / Catatan Teknis')
                        ->required()
                        ->rows(7)
                        ->maxLength(12000),
                ]),

            Section::make('Lampiran Dokumen Teknis')
                ->description('Opsional. Lampiran pendukung dapat berupa PDF, JPG, atau PNG. Dokumen resmi utama akan dibuat oleh sistem dalam bentuk PDF setelah rekomendasi disetujui.')
                ->columns(1)
                ->schema([
                    FileUpload::make('lokasi_file')
                        ->label('Lampiran Pendukung')
                        ->disk('private')
                        ->directory('rekomendasi-teknis/lampiran')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(10240)
                        ->downloadable(false)
                        ->openable(false)
                        ->nullable(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['disusunOleh', 'direviewOleh', 'disetujuiOleh', 'ditolakOleh']))
            ->recordTitleAttribute('nomor_rekomendasi')
            ->columns([
                TextColumn::make('nomor_rekomendasi')->label('Nomor')->searchable()->sortable()->placeholder('-'),
                TextColumn::make('tanggal_rekomendasi')->label('Tanggal')->date('d/m/Y')->sortable()->placeholder('-'),
                TextColumn::make('hasil')->label('Hasil')->badge(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('nomor_bap_referensi')->label('BAP Referensi')->placeholder('-')->toggleable(),
                TextColumn::make('disusunOleh.name')->label('Disusun Oleh')->placeholder('-')->toggleable(),
                TextColumn::make('disetujuiOleh.name')->label('Disetujui Oleh')->placeholder('-')->toggleable(),
                TextColumn::make('generated_pdf_path')
                    ->label('PDF Resmi')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Tersedia' : 'Belum dibuat')
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(StatusPersetujuan::class),
                SelectFilter::make('hasil')->label('Hasil')->options(HasilRekomendasi::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Buat Rekomendasi')
                    ->icon('heroicon-o-document-plus')
                    ->visible(fn (): bool => auth()->user()->hasAnyRole(['tim_teknis', 'admin'])
                        && $this->getOwnerRecord()->pemeriksaanLapangan()->where('status', \App\Enums\StatusPemeriksaan::Final)->exists())
                    ->mutateFormDataUsing(function (array $data): array {
                        $now = now();
                        $bap = $this->getOwnerRecord()->pemeriksaanLapangan()->where('status', \App\Enums\StatusPemeriksaan::Final)->orderByDesc('versi')->first();
                        $data['nomor_rekomendasi'] = app(RekomendasiTeknisNumberService::class)->generate($now);
                        $data['tanggal_rekomendasi'] = $data['tanggal_rekomendasi'] ?? $now->toDateString();
                        $data['nomor_bap_referensi'] = $bap?->nomor_bap;
                        $data['disusun_oleh'] = Auth::id();
                        $data['status'] = StatusPersetujuan::Draf->value;
                        return $data;
                    })
                    ->after(function (): void {
                        $this->getOwnerRecord()->update(['status' => \App\Enums\StatusPermohonan::ProsesTeknis]);
                        Notification::make()
                            ->success()
                            ->title('Rekomendasi teknis dibuat')
                            ->body('Dokumen tersimpan sebagai draf dan dapat diperbaiki sebelum diajukan review.')
                            ->send();
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                Action::make('ajukan')
                    ->label('Ajukan Review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (RekomendasiTeknis $record): bool => $record->status === StatusPersetujuan::Draf && auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan Rekomendasi untuk Review')
                    ->modalDescription('Setelah diajukan, penyusun tidak dapat mengubah isi sampai reviewer menyetujui atau menolaknya.')
                    ->action(function (RekomendasiTeknis $record): void {
                        $record->update([
                            'status' => StatusPersetujuan::Diajukan,
                            'diajukan_pada' => now(),
                        ]);
                        $record->permohonan?->update(['status' => \App\Enums\StatusPermohonan::Rekomendasi]);

                        $record->loadMissing('permohonan.pemohon');
                        $notifications = app(\App\Services\IpptWorkflowNotificationService::class);
                        $action = $notifications->applicationAction($record->permohonan, 'Review Rekomendasi', 'rekomendasi');

                        $notifications->roles(
                            [\App\Enums\UserRole::KABID],
                            'Review Rekomendasi Teknis diperlukan',
                            sprintf(
                                '%s mengajukan %s untuk permohonan %s. Rekomendasi siap diperiksa dan diputuskan.',
                                $record->disusunOleh?->name ?? 'Tim Teknis',
                                $record->nomor_rekomendasi,
                                $record->permohonan?->nomor_permohonan ?? '-',
                            ),
                            'warning',
                            $action,
                        );

                        $notifications->roles(
                            [\App\Enums\UserRole::ADMIN],
                            'Rekomendasi Teknis diajukan',
                            sprintf(
                                '%s untuk permohonan %s telah diajukan kepada Kabid untuk review.',
                                $record->nomor_rekomendasi,
                                $record->permohonan?->nomor_permohonan ?? '-',
                            ),
                            'info',
                            $notifications->applicationAction($record->permohonan, 'Buka Permohonan', 'rekomendasi'),
                        );

                        Notification::make()
                            ->success()
                            ->title('Rekomendasi diajukan')
                            ->body('Rekomendasi teknis masuk tahap review Kabid.')
                            ->send();
                    }),

                Action::make('kirim_ulang_review')
                    ->label('Kirim Ulang untuk Review')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (RekomendasiTeknis $record): bool => $record->status === StatusPersetujuan::Ditolak && auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Ulang Rekomendasi untuk Review')
                    ->modalDescription('Pastikan seluruh perbaikan sudah disimpan. Data rekomendasi yang tersimpan akan dikirim kembali kepada Kabid tanpa membuat dokumen baru.')
                    ->action(function (RekomendasiTeknis $record): void {
                        $record->refresh();

                        try {
                            Validator::make($record->toArray(), [
                                'tanggal_rekomendasi' => ['required', 'date'],
                                'dasar_hukum' => ['required', 'string'],
                                'nomor_bap_referensi' => ['required', 'string'],
                                'pertimbangan' => ['required', 'string'],
                                'kesesuaian_tata_ruang' => ['required', 'string'],
                                'arahan_teknis' => ['required', 'string'],
                                'hasil' => ['required'],
                                'ketentuan' => ['required', 'string'],
                            ])->validate();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Rekomendasi belum lengkap')
                                ->body('Lengkapi dan simpan seluruh bagian rekomendasi sebelum mengirim ulang untuk review.')
                                ->send();
                            throw $e;
                        }

                        $record->update([
                            'status' => StatusPersetujuan::Diajukan,
                            'diajukan_pada' => now(),
                            'generated_pdf_path' => null,
                        ]);
                        $record->permohonan?->update(['status' => \App\Enums\StatusPermohonan::Rekomendasi]);

                        $record->loadMissing('permohonan.pemohon', 'disusunOleh');
                        $notifications = app(\App\Services\IpptWorkflowNotificationService::class);

                        $notifications->roles(
                            [\App\Enums\UserRole::KABID],
                            'Rekomendasi Teknis diajukan ulang',
                            sprintf(
                                '%s telah memperbaiki dan mengirim ulang %s untuk permohonan %s. Silakan review kembali. Catatan review sebelumnya: %s',
                                $record->disusunOleh?->name ?? 'Tim Teknis',
                                $record->nomor_rekomendasi,
                                $record->permohonan?->nomor_permohonan ?? '-',
                                $record->catatan_review ?: 'Tidak ada catatan tambahan.',
                            ),
                            'warning',
                            $notifications->applicationAction($record->permohonan, 'Review Ulang Rekomendasi', 'rekomendasi'),
                        );

                        $notifications->roles(
                            [\App\Enums\UserRole::ADMIN],
                            'Rekomendasi Teknis diperbaiki',
                            sprintf(
                                '%s telah diperbaiki dan dikirim ulang untuk review Kabid pada permohonan %s.',
                                $record->nomor_rekomendasi,
                                $record->permohonan?->nomor_permohonan ?? '-',
                            ),
                            'info',
                            $notifications->applicationAction($record->permohonan, 'Buka Rekomendasi', 'rekomendasi'),
                        );

                        Notification::make()
                            ->success()
                            ->title('Rekomendasi dikirim ulang')
                            ->body('Data perbaikan tersimpan dan rekomendasi kembali ke antrean review Kabid.')
                            ->send();
                    }),

                Action::make('setujui')
                    ->label('Setujui & Generate PDF')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (RekomendasiTeknis $record): bool => $record->status === StatusPersetujuan::Diajukan && auth()->user()->hasAnyRole(['kabid', 'admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Rekomendasi Teknis')
                    ->modalDescription('Persetujuan akan mengunci rekomendasi dan membuat PDF resmi dari data yang disetujui.')
                    ->action(function (RekomendasiTeknis $record, RekomendasiTeknisPdfService $pdfService): void {
                        $record->update([
                            'status' => StatusPersetujuan::Disetujui,
                            'direview_oleh' => Auth::id(),
                            'disetujui_oleh' => Auth::id(),
                            'disetujui_pada' => now(),
                        ]);
                        $record->refresh();
                        $path = $pdfService->store($record);
                        $record->update(['generated_pdf_path' => $path]);

                        // According to the Yogyakarta IPPT flow, the land office
                        // risalah is obtained after the technical recommendation
                        // and before the final decision.
                        $permohonan = $record->permohonan;
                        $permohonan->update(['status' => \App\Enums\StatusPermohonan::MenungguRisalah]);

                        // Open the next workflow stage explicitly so the backoffice
                        // sees a pending Risalah record rather than an empty tab.
                        \App\Models\RisalahPertimbangan::updateOrCreate(
                            ['permohonan_id' => $permohonan->id],
                            [
                                'status' => 'menunggu_dokumen',
                                'diminta_oleh' => Auth::id(),
                                'diminta_pada' => now(),
                                // Risalah is an external document. The intake row is
                                // intentionally created without a file until Kantor
                                // Pertanahan sends the official document.
                                'lokasi_file' => null,
                            ],
                        );

                        $record->loadMissing('permohonan.pemohon');
                        $notifications = app(\App\Services\IpptWorkflowNotificationService::class);

                        Notification::make()
                            ->success()
                            ->title('Rekomendasi disetujui')
                            ->body('Rekomendasi terkunci, PDF resmi dibuat, dan permohonan berpindah ke tahap menunggu Risalah Pertimbangan Teknis.')
                            ->send();

                        $notifications->pemohon(
                            $record->permohonan,
                            'Rekomendasi Teknis disetujui',
                            sprintf(
                                'Rekomendasi %s untuk permohonan %s telah disetujui. Tahap berikutnya adalah Risalah Pertimbangan Teknis.',
                                $record->nomor_rekomendasi,
                                $record->permohonan?->nomor_permohonan ?? '-',
                            ),
                            'success',
                            $notifications->applicationAction($record->permohonan, 'Lihat Permohonan'),
                        );

                        $notifications->roles(
                            [\App\Enums\UserRole::ADMIN, \App\Enums\UserRole::STAFF],
                            'Risalah Pertimbangan diperlukan',
                            sprintf(
                                'Rekomendasi %s telah disetujui untuk permohonan %s. Silakan proses penerimaan Risalah Pertimbangan Teknis.',
                                $record->nomor_rekomendasi,
                                $record->permohonan?->nomor_permohonan ?? '-',
                            ),
                            'warning',
                            $notifications->applicationAction($record->permohonan, 'Proses Risalah'),
                        );

                        $notifications->roles(
                            [\App\Enums\UserRole::TIM_TEKNIS],
                            'Rekomendasi Teknis disetujui',
                            sprintf(
                                'Rekomendasi %s untuk permohonan %s telah disetujui Kabid.',
                                $record->nomor_rekomendasi,
                                $record->permohonan?->nomor_permohonan ?? '-',
                            ),
                            'success',
                            $notifications->applicationAction($record->permohonan, 'Buka Permohonan'),
                        );
                    }),

                Action::make('tolak')
                    ->label('Tolak & Beri Catatan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (RekomendasiTeknis $record): bool => $record->status === StatusPersetujuan::Diajukan && auth()->user()->hasAnyRole(['kabid', 'admin']))
                    ->form([
                        Textarea::make('catatan_review')
                            ->label('Catatan Perbaikan')
                            ->required()
                            ->rows(6)
                            ->maxLength(4000)
                            ->placeholder('Jelaskan bagian yang perlu diperbaiki agar penyusun dapat menindaklanjuti dengan jelas.'),
                    ])
                    ->modalHeading('Tolak Rekomendasi Teknis')
                    ->modalSubmitActionLabel('Tolak & Kembalikan')
                    ->action(function (RekomendasiTeknis $record, array $data): void {
                        $record->update([
                            'status' => StatusPersetujuan::Ditolak,
                            'catatan_review' => $data['catatan_review'],
                            'direview_oleh' => Auth::id(),
                            'ditolak_oleh' => Auth::id(),
                            'ditolak_pada' => now(),
                        ]);

                        // A rejected recommendation returns the technical work to the
                        // Tim Teknis; the application remains in the technical stage.
                        $record->permohonan?->update(['status' => \App\Enums\StatusPermohonan::ProsesTeknis]);

                        $record->loadMissing('permohonan.pemohon');
                        $notifications = app(\App\Services\IpptWorkflowNotificationService::class);

                        Notification::make()
                            ->danger()
                            ->title('Rekomendasi dikembalikan')
                            ->body('Penyusun dapat memperbaiki draf berdasarkan catatan review.')
                            ->send();

                        $notifications->roles(
                            [\App\Enums\UserRole::TIM_TEKNIS],
                            'Rekomendasi Teknis perlu diperbaiki',
                            sprintf(
                                'Rekomendasi %s untuk permohonan %s dikembalikan oleh Kabid. Catatan: %s',
                                $record->nomor_rekomendasi,
                                $record->permohonan?->nomor_permohonan ?? '-',
                                $record->catatan_review ?: 'Silakan periksa kembali rekomendasi.',
                            ),
                            'danger',
                            $notifications->applicationAction($record->permohonan, 'Perbaiki Rekomendasi', 'rekomendasi'),
                        );

                        $notifications->roles(
                            [\App\Enums\UserRole::ADMIN],
                            'Rekomendasi Teknis dikembalikan',
                            sprintf(
                                'Rekomendasi %s dikembalikan oleh Kabid dan perlu diperbaiki oleh Tim Teknis.',
                                $record->nomor_rekomendasi,
                            ),
                            'warning',
                            $notifications->applicationAction($record->permohonan, 'Buka Permohonan'),
                        );
                    }),

                Action::make('lihat_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalHeading('Detail Rekomendasi Teknis')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->infolist(fn (RekomendasiTeknis $record) => [
                        \Filament\Infolists\Components\TextEntry::make('nomor_rekomendasi')
                            ->label('Nomor Rekomendasi'),
                        \Filament\Infolists\Components\TextEntry::make('tanggal_rekomendasi')
                            ->label('Tanggal')
                            ->date('d/m/Y'),
                        \Filament\Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        \Filament\Infolists\Components\TextEntry::make('hasil')
                            ->label('Hasil Rekomendasi')
                            ->badge(),
                        \Filament\Infolists\Components\TextEntry::make('nomor_bap_referensi')
                            ->label('BAP Pemeriksaan Lapangan')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('disusunOleh.name')
                            ->label('Disusun Oleh')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('direviewOleh.name')
                            ->label('Direview Oleh')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('disetujuiOleh.name')
                            ->label('Disetujui Oleh')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('dasar_hukum')
                            ->label('Dasar Hukum / Rujukan')
                            ->prose()
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('pertimbangan')
                            ->label('Pertimbangan Teknis')
                            ->prose()
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('kesesuaian_tata_ruang')
                            ->label('Kesesuaian Tata Ruang')
                            ->prose()
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('arahan_teknis')
                            ->label('Arahan Teknis / Persyaratan')
                            ->prose()
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('ketentuan')
                            ->label('Ketentuan / Catatan Teknis')
                            ->prose()
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('lokasi_file')
                            ->label('Lampiran Pendukung')
                            ->formatStateUsing(fn (?string $state): string => $state && Storage::disk('private')->exists($state) ? 'Buka lampiran pendukung' : 'Lampiran tidak tersedia')
                            ->url(fn (RekomendasiTeknis $record): ?string => $record->lokasi_file && Storage::disk('private')->exists($record->lokasi_file)
                                ? URL::temporarySignedRoute('private-files.show', now()->addMinutes(10), ['path' => $record->lokasi_file])
                                : null)
                            ->openUrlInNewTab()
                            ->color('primary')
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('catatan_review')
                            ->label('Catatan Review')
                            ->prose()
                            ->placeholder('Belum ada catatan review.')
                            ->columnSpanFull(),
                    ])
                    ->modalWidth('5xl'),

                Action::make('lihat_catatan')
                    ->label('Lihat Catatan')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('gray')
                    ->visible(fn (RekomendasiTeknis $record): bool => filled($record->catatan_review))
                    ->form([
                        Textarea::make('catatan_review')
                            ->label('Catatan Review')
                            ->default(fn (RekomendasiTeknis $record): ?string => $record->catatan_review)
                            ->disabled()
                            ->rows(10),
                    ])
                    ->modalHeading('Catatan Review Rekomendasi Teknis')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                static::fileViewAction(attribute: 'lokasi_file', label: 'Buka Lampiran', name: 'buka_lampiran'),
                static::fileDownloadAction(attribute: 'lokasi_file', label: 'Unduh Lampiran', name: 'unduh_lampiran'),

                static::fileViewAction(attribute: 'generated_pdf_path', label: 'Buka PDF', name: 'buka_pdf'),
                static::fileDownloadAction(attribute: 'generated_pdf_path', label: 'Unduh PDF', name: 'unduh_pdf'),
                Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->visible(fn (RekomendasiTeknis $record): bool => filled($record->generated_pdf_path))
                    ->disabled(fn (RekomendasiTeknis $record): bool => ! Storage::disk('private')->exists($record->generated_pdf_path))
                    ->url(fn (RekomendasiTeknis $record): ?string => Storage::disk('private')->exists($record->generated_pdf_path)
                        ? Storage::disk('private')->temporaryUrl($record->generated_pdf_path, now()->addMinutes(10))
                        : null)
                    ->openUrlInNewTab()
                    ->tooltip('Buka PDF di browser, lalu gunakan dialog Cetak browser.'),

                
                    EditAction::make()
                        ->label(fn (RekomendasiTeknis $record): string => $record->status === StatusPersetujuan::Ditolak ? 'Perbaiki Rekomendasi' : 'Edit Draf')
                        ->visible(fn (RekomendasiTeknis $record): bool => in_array($record->status?->value, StatusPersetujuan::editableValues(), true) && auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                        ->mutateFormDataUsing(function (array $data, RekomendasiTeknis $record): array {
                            // Never erase an already stored value simply because a disabled/upload field
                            // was not present in the Livewire payload during an edit.
                            foreach (['nomor_rekomendasi', 'nomor_bap_referensi', 'lokasi_file'] as $field) {
                                if (! array_key_exists($field, $data) || blank($data[$field])) {
                                    $data[$field] = $record->{$field};
                                }
                            }

                            return $data;
                        })
                        ->after(function (RekomendasiTeknis $record): void {
                            $record->refresh();
                            Notification::make()
                                ->success()
                                ->title('Perbaikan tersimpan')
                                ->body('Data rekomendasi tersimpan. Gunakan Kirim Ulang untuk Review agar Kabid menerima versi perbaikan.')
                                ->send();
                        }),
                    DeleteAction::make()
                        ->label('Hapus Draf')
                        ->visible(fn (RekomendasiTeknis $record): bool => in_array($record->status?->value, StatusPersetujuan::editableValues(), true) && auth()->user()->hasRole('admin'))
                        ->before(function (RekomendasiTeknis $record): void {
                            foreach ([$record->lokasi_file, $record->generated_pdf_path] as $path) {
                                if ($path && Storage::disk('private')->exists($path)) Storage::disk('private')->delete($path);
                            }
                        }),
                
                ])
                    ->label('Lainnya')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading('Belum ada rekomendasi teknis')
            ->emptyStateDescription('Rekomendasi dibuat setelah BAP pemeriksaan lapangan tersedia dan data teknis telah siap direview.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->defaultSort('created_at', 'desc');
    }
}
