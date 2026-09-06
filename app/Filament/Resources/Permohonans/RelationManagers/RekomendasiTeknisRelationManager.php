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
        return auth()->user()->hasAnyRole(['admin', 'pemohon', 'tim_teknis', 'kabid', 'kadis']);
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
                    ->after(fn () => Notification::make()->success()->title('Rekomendasi teknis dibuat')->body('Dokumen masih berupa draf dan dapat diperbaiki sebelum diajukan review.')->send()),
            ])
            ->recordActions([
                Action::make('ajukan')
                    ->label('Ajukan Review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (RekomendasiTeknis $record): bool => $record->status === StatusPersetujuan::Draf && auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Ajukan Rekomendasi untuk Review')
                    ->modalDescription('Setelah diajukan, penyusun tidak dapat mengubah isi sampai reviewer menyetujui atau menolaknya.')
                    ->action(function (RekomendasiTeknis $record): void {
                        $record->update(['status' => StatusPersetujuan::Diajukan, 'diajukan_pada' => now()]);
                        Notification::make()->success()->title('Rekomendasi diajukan')->body('Rekomendasi teknis menunggu review pejabat berwenang.')->send();
                        app(\App\Services\IpptWorkflowNotificationService::class)->roles(
                            [\App\Enums\UserRole::KABID],
                            'Review Rekomendasi Teknis diperlukan',
                            "Rekomendasi {$record->nomor_rekomendasi} diajukan untuk review.",
                            'warning'
                        );
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

                        $staffUsers = \App\Models\User::query()->where('role', 'staff')->get();
                        foreach ($staffUsers as $staff) {
                            Notification::make()
                                ->info()
                                ->title('Menunggu Risalah Pertimbangan Teknis')
                                ->body("Rekomendasi {$record->nomor_rekomendasi} telah disetujui. Risalah dari Kantor Pertanahan perlu diterima sebelum keputusan IPPT.")
                                ->sendToDatabase($staff, isEventDispatched: true);
                        }

                        Notification::make()->success()->title('Rekomendasi disetujui')->body('Rekomendasi terkunci, PDF resmi dibuat, dan permohonan berpindah ke tahap menunggu Risalah Pertimbangan Teknis.')->send();
                        app(\App\Services\IpptWorkflowNotificationService::class)->pemohon($record->permohonan, 'Rekomendasi Teknis disetujui', "Rekomendasi {$record->nomor_rekomendasi} telah disetujui.", 'success');
                        app(\App\Services\IpptWorkflowNotificationService::class)->roles([\App\Enums\UserRole::STAFF], 'Risalah Pertimbangan diperlukan', "Rekomendasi {$record->nomor_rekomendasi} telah disetujui. Silakan proses penerimaan Risalah Pertimbangan Teknis.", 'warning');
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
                            'ditolak_oleh' => Auth::id(),
                            'ditolak_pada' => now(),
                        ]);
                        Notification::make()->danger()->title('Rekomendasi dikembalikan')->body('Penyusun dapat memperbaiki draf berdasarkan catatan review.')->send();
                        app(\App\Services\IpptWorkflowNotificationService::class)->roles([\App\Enums\UserRole::TIM_TEKNIS], 'Rekomendasi Teknis perlu diperbaiki', "Rekomendasi {$record->nomor_rekomendasi} dikembalikan dengan catatan review.", 'danger');
                        app(\App\Services\IpptWorkflowNotificationService::class)->pemohon($record->permohonan, 'Rekomendasi Teknis dikembalikan', "Rekomendasi {$record->nomor_rekomendasi} memerlukan perbaikan internal.", 'warning');
                    }),

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

                static::fileViewAction(attribute: 'lokasi_file', label: 'Buka Lampiran', name: 'buka_lampiran'),
                static::fileDownloadAction(attribute: 'lokasi_file', label: 'Unduh Lampiran', name: 'unduh_lampiran'),

                ActionGroup::make([
                    EditAction::make()
                        ->label('Edit Draf')
                        ->visible(fn (RekomendasiTeknis $record): bool => in_array($record->status?->value, StatusPersetujuan::editableValues(), true) && auth()->user()->hasAnyRole(['tim_teknis', 'admin']))
                        ->after(fn () => Notification::make()->success()->title('Rekomendasi diperbarui')->send()),
                    DeleteAction::make()
                        ->label('Hapus Draf')
                        ->visible(fn (RekomendasiTeknis $record): bool => in_array($record->status?->value, StatusPersetujuan::editableValues(), true) && auth()->user()->hasRole('admin'))
                        ->before(function (RekomendasiTeknis $record): void {
                            foreach ([$record->lokasi_file, $record->generated_pdf_path] as $path) {
                                if ($path && Storage::disk('private')->exists($path)) Storage::disk('private')->delete($path);
                            }
                        }),
                ])->label('Lainnya')->icon('heroicon-m-ellipsis-vertical')->color('gray'),
            ])
            ->emptyStateHeading('Belum ada rekomendasi teknis')
            ->emptyStateDescription('Rekomendasi dibuat setelah BAP pemeriksaan lapangan tersedia dan data teknis telah siap direview.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->defaultSort('created_at', 'desc');
    }
}
