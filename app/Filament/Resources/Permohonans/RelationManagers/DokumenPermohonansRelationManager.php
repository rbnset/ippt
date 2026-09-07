<?php

namespace App\Filament\Resources\Permohonans\RelationManagers;

use App\Enums\JenisDokumen;
use App\Enums\StatusDokumen;
use App\Enums\StatusPermohonan;
use App\Filament\Concerns\HasFileViewAction;
use App\Models\DokumenPermohonan;
use App\Models\Permohonan;
use App\Services\DokumenPermohonanWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DokumenPermohonansRelationManager extends RelationManager
{
    use HasFileViewAction;

    protected static string $relationship = 'dokumenPermohonan';

    protected static ?string $title = 'Dokumen Persyaratan';

    protected static ?string $modelLabel = 'Dokumen';

    protected static ?string $pluralModelLabel = 'Dokumen Persyaratan';

    /**
     * Visual status tab for the document table. Uploading is intentionally
     * handled only by the dedicated workflow actions, not by the table header.
     */
    public string $statusTab = 'semua';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'pemohon', 'staff', 'tim_teknis', 'kabid', 'kadis']);
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
                    ->tooltip(fn (DokumenPermohonan $record): ?string => $record->nama_file),

                TextColumn::make('tipe_file')
                    ->label('Tipe')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => strtoupper($state ?? '-')),

                TextColumn::make('ukuran_file')
                    ->label('Ukuran')
                    ->formatStateUsing(fn (?int $state): string => $state
                        ? number_format($state / 1024, 2) . ' KB'
                        : '-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('catatan')
                    ->label('Catatan / Arahan')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? Str::words($state, 8, '...') : '-')
                    ->placeholder('-')
                    ->wrap(),

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
            ])
            ->header(
                view('filament.resources.permohonans.relation-managers.dokumen-status-tabs', [
                    'relationManager' => $this,
                ])
            )
            ->modifyQueryUsing(function ($query) {
                return match ($this->statusTab) {
                    'menunggu' => $query->where('status', StatusDokumen::Menunggu->value),
                    'diterima' => $query->where('status', StatusDokumen::Diterima->value),
                    'ditolak' => $query->where('status', StatusDokumen::Ditolak->value),
                    default => $query,
                };
            })
            ->recordActions([
                ActionGroup::make([
                Action::make('terima')
                    ->label('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DokumenPermohonan $record): bool => $record->status === StatusDokumen::Menunggu
                        && auth()->user()->hasAnyRole(['staff', 'admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Terima Dokumen')
                    ->modalDescription('Apakah dokumen ini sudah sesuai, jelas, dan dapat diterima? Setelah diterima, dokumen akan terkunci.')
                    ->action(function (DokumenPermohonan $record): void {
                        $record->update(['status' => StatusDokumen::Diterima, 'catatan' => null]);
                        Notification::make()->success()->title('Dokumen diterima')->body("Dokumen \"{$record->nama_file}\" telah diterima dan dikunci.")->send();
                        app(\App\Services\IpptWorkflowNotificationService::class)->pemohon($record->permohonan, 'Dokumen persyaratan diterima', "Dokumen {$record->jenis_dokumen->getLabel()} telah diterima dan dikunci.", 'success');
                    }),

                Action::make('tolak')
                    ->label('Tolak & Beri Arahan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (DokumenPermohonan $record): bool => $record->status === StatusDokumen::Menunggu
                        && auth()->user()->hasAnyRole(['staff', 'admin']))
                    ->form([
                        Textarea::make('catatan')
                            ->label('Alasan Penolakan & Arahan Perbaikan')
                            ->required()->rows(5)->maxLength(1000)
                            ->helperText('Jelaskan dengan spesifik apa yang salah dan bagaimana pemohon harus memperbaikinya.'),
                    ])
                    ->modalHeading('Tolak Dokumen & Beri Arahan')
                    ->modalSubmitActionLabel('Tolak Dokumen')
                    ->action(function (DokumenPermohonan $record, array $data): void {
                        $record->update(['status' => StatusDokumen::Ditolak, 'catatan' => $data['catatan']]);
                        $permohonan = $record->permohonan;
                        if ($permohonan) $permohonan->update(['status' => StatusPermohonan::Dikembalikan]);
                        app(\App\Services\IpptWorkflowNotificationService::class)->pemohonDocumentRejected($record->fresh(['permohonan.pemohon.user']));
                        Notification::make()->danger()->title('Dokumen ditolak')->body('Pemohon sudah menerima notifikasi beserta arahan perbaikan.')->send();
                    }),

                static::fileViewAction(label: 'Lihat', name: 'lihat_file'),
                    static::fileDownloadAction(label: 'Unduh', name: 'unduh_file'),
                    Action::make('cetak')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->visible(fn (DokumenPermohonan $record): bool => filled($record->lokasi_file))
                        ->disabled(fn (DokumenPermohonan $record): bool => ! Storage::disk('private')->exists($record->lokasi_file))
                        ->url(fn (DokumenPermohonan $record): ?string => Storage::disk('private')->exists($record->lokasi_file)
                            ? Storage::disk('private')->temporaryUrl($record->lokasi_file, now()->addMinutes(10)) : null)
                        ->openUrlInNewTab()
                        ->tooltip('Buka file di browser, lalu gunakan dialog cetak.'),
                    Action::make('lihatCatatan')
                        ->label('Lihat Catatan')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('gray')
                        ->visible(fn (DokumenPermohonan $record): bool => filled($record->catatan))
                        ->modalHeading(fn (DokumenPermohonan $record): string => 'Catatan: ' . $record->jenis_dokumen->getLabel())
                        ->modalDescription('Alasan penolakan atau arahan perbaikan dari petugas.')
                        ->form([
                            Textarea::make('catatan')->label('Catatan / Arahan Petugas')
                                ->default(fn (DokumenPermohonan $record): ?string => $record->catatan)
                                ->disabled()->rows(10)->columnSpanFull(),
                        ])->modalSubmitAction(false)->modalCancelActionLabel('Tutup'),
                    Action::make('uploadUlang')
                        ->label('Upload Ulang')
                        ->icon('heroicon-o-arrow-path')->color('warning')
                        ->visible(fn (DokumenPermohonan $record): bool => $record->status === StatusDokumen::Ditolak
                            && app(DokumenPermohonanWorkflowService::class)->isLatestSubmission($record)
                            && auth()->user()->hasAnyRole(['pemohon', 'staff', 'admin']))
                        ->modalHeading(fn (DokumenPermohonan $record): string => 'Upload Ulang: ' . $record->jenis_dokumen->getLabel())
                        ->modalDescription(fn (DokumenPermohonan $record): string => 'Perbaiki dokumen sesuai arahan petugas sebelum mengunggah file baru.

Alasan / arahan: ' . ($record->catatan ?: 'Pastikan dokumen lengkap, jelas, dan sesuai persyaratan.'))
                        ->form([
                            FileUpload::make('lokasi_file')->label('File Pengganti')->disk('private')->directory(fn (): string => app(\App\Services\PermohonanStoragePathService::class)->directory($this->getOwnerRecord(), 'dokumen-persyaratan/tmp'))->visibility('private')
                                ->acceptedFileTypes(['application/pdf','image/jpeg','image/png'])->maxSize(10240)->downloadable(false)->openable(false)->required()
                                ->helperText('PDF/JPG/PNG, maksimal 10 MB.'),
                            Textarea::make('catatan')->label('Catatan Perbaikan')->rows(3)->maxLength(1000)->helperText('Opsional. Jelaskan perbaikan yang Anda lakukan.'),
                        ])
                        ->action(function (DokumenPermohonan $record, array $data): void {
                            $newDocument = app(DokumenPermohonanWorkflowService::class)->uploadReplacement($this->getOwnerRecord(), $record, $data['lokasi_file'], Auth::user());
                            $newDocument->update(['catatan' => $data['catatan'] ?? null]);

                            app(\App\Services\IpptWorkflowNotificationService::class)->rolesDocumentReuploaded($newDocument->fresh(['permohonan.pemohon']));
                            app(\App\Services\IpptWorkflowNotificationService::class)->pemohonDocumentReuploaded($newDocument->fresh(['permohonan.pemohon.user']));

                            Notification::make()->success()->title('Dokumen berhasil dikirim ulang')->body('Dokumen baru menunggu verifikasi dan notifikasi sudah dikirim kepada Admin/Staff.')->send();
                        }),
                    DeleteAction::make()
                        ->label('Hapus')
                        ->visible(fn (DokumenPermohonan $record): bool => $record->status === StatusDokumen::Menunggu && auth()->user()->hasRole('admin'))
                        ->before(function (DokumenPermohonan $record): void {
                            if ($record->lokasi_file && Storage::disk('private')->exists($record->lokasi_file)) Storage::disk('private')->delete($record->lokasi_file);
                        }),
            ])
                ->label('Lainnya')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray'),
            ])
            ->emptyStateHeading('Belum ada dokumen')
            ->emptyStateDescription('Dokumen persyaratan akan tampil di sini setelah permohonan dibuat.')
            ->emptyStateIcon('heroicon-o-document')
            ->defaultSort('created_at', 'desc');
    }

    public function setStatusTab(string $tab): void
    {
        if (! in_array($tab, ['semua', 'menunggu', 'diterima', 'ditolak'], true)) {
            return;
        }

        $this->statusTab = $tab;
    }

    /**
     * Lightweight counts keep the tabs informative without adding another
     * filter control to the interface.
     *
     * @return array<string, int>
     */
    public function getStatusTabCounts(): array
    {
        return $this->getOwnerRecord()
            ->dokumenPermohonan()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

}
