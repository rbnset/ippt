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
                    ->limit(55)
                    ->tooltip(fn (DokumenPermohonan $record): ?string => $record->catatan)
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
                static::fileViewAction(label: 'Lihat'),

                Action::make('lihatCatatan')
                    ->label('Lihat Catatan')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('gray')
                    ->visible(fn (DokumenPermohonan $record): bool => filled($record->catatan))
                    ->modalHeading(fn (DokumenPermohonan $record): string => 'Catatan: ' . $record->jenis_dokumen->getLabel())
                    ->modalDescription('Baca alasan penolakan atau arahan perbaikan dari petugas.')
                    ->form([
                        Textarea::make('catatan')
                            ->label('Catatan / Arahan Petugas')
                            ->default(fn (DokumenPermohonan $record): ?string => $record->catatan)
                            ->disabled()
                            ->rows(7)
                            ->columnSpanFull(),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                Action::make('uploadUlang')
                    ->label('Upload Ulang')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (DokumenPermohonan $record): bool => $record->status === StatusDokumen::Ditolak
                        && app(DokumenPermohonanWorkflowService::class)->isLatestSubmission($record)
                        && auth()->user()->hasAnyRole(['pemohon', 'staff', 'admin']))
                    ->modalHeading(fn (DokumenPermohonan $record): string => 'Upload Ulang: ' . $record->jenis_dokumen->getLabel())
                    ->modalDescription(fn (DokumenPermohonan $record): string => 'Dokumen ini ditolak. Perbaiki sesuai arahan petugas lalu unggah file baru.\n\nAlasan / arahan: ' . ($record->catatan ?: 'Tidak ada catatan. Pastikan dokumen lengkap, jelas, dan sesuai persyaratan.'))
                    ->form([
                        FileUpload::make('lokasi_file')
                            ->label('File Pengganti')
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
                            ->helperText('Pastikan file baru sudah memperbaiki masalah yang disebutkan petugas. PDF/JPG/PNG, maksimal 10 MB.'),

                        Textarea::make('catatan')
                            ->label('Catatan Perbaikan')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Opsional. Jelaskan perbaikan yang Anda lakukan pada dokumen baru.'),
                    ])
                    ->action(function (DokumenPermohonan $record, array $data): void {
                        $newDocument = app(DokumenPermohonanWorkflowService::class)->uploadReplacement(
                            $this->getOwnerRecord(),
                            $record,
                            $data['lokasi_file'],
                            Auth::user(),
                        );

                        $newDocument->update([
                            'catatan' => $data['catatan'] ?? null,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Dokumen berhasil dikirim ulang')
                            ->body('Dokumen baru berstatus Menunggu Verifikasi. Dokumen tidak dapat diganti lagi sampai petugas memberikan hasil verifikasi.')
                            ->send();
                    }),

                Action::make('terima')
                    ->label('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DokumenPermohonan $record): bool => $record->status === StatusDokumen::Menunggu
                        && auth()->user()->hasAnyRole(['staff', 'admin']))
                    ->requiresConfirmation()
                    ->modalHeading('Terima Dokumen')
                    ->modalDescription('Apakah dokumen ini sudah sesuai, jelas, dan dapat diterima? Setelah diterima, pemohon tidak dapat menggantinya melalui alur upload ulang.')
                    ->action(function (DokumenPermohonan $record): void {
                        $record->update([
                            'status' => StatusDokumen::Diterima,
                            'catatan' => null,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Dokumen diterima')
                            ->body("Dokumen \"{$record->nama_file}\" telah diterima dan dikunci.")
                            ->send();
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
                            ->required()
                            ->rows(5)
                            ->maxLength(1000)
                            ->helperText('Jelaskan dengan spesifik apa yang salah dan apa yang harus diperbaiki agar pemohon dapat mengunggah ulang dengan benar.'),
                    ])
                    ->modalHeading('Tolak Dokumen & Beri Arahan')
                    ->modalSubmitActionLabel('Tolak Dokumen')
                    ->action(function (DokumenPermohonan $record, array $data): void {
                        $record->update([
                            'status' => StatusDokumen::Ditolak,
                            'catatan' => $data['catatan'],
                        ]);

                        $permohonan = $record->permohonan;
                        if ($permohonan) {
                            $permohonan->update(['status' => StatusPermohonan::Dikembalikan]);
                        }

                        app(DokumenPermohonanWorkflowService::class)->notifyPemohonRejected($record->fresh(['permohonan.pemohon.user']));

                        Notification::make()
                            ->danger()
                            ->title('Dokumen ditolak')
                            ->body("Dokumen \"{$record->nama_file}\" ditolak dan pemohon sudah menerima notifikasi beserta arahan perbaikan.")
                            ->send();
                    }),

                ActionGroup::make([
                    DeleteAction::make()
                        ->visible(fn (DokumenPermohonan $record): bool => $record->status === StatusDokumen::Menunggu
                            && auth()->user()->hasRole('admin'))
                        ->before(function (DokumenPermohonan $record): void {
                            if ($record->lokasi_file && Storage::disk('private')->exists($record->lokasi_file)) {
                                Storage::disk('private')->delete($record->lokasi_file);
                            }
                        }),
                ])
                    ->label('Lainnya')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->visible(fn (): bool => auth()->user()->hasRole('admin')),
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
