<?php

namespace App\Filament\Resources\Permohonans\RelationManagers;

use App\Enums\JenisKeputusan;
use App\Enums\StatusPermohonan;
use App\Enums\StatusPersetujuan;
use App\Filament\Concerns\HasFileViewAction;
use App\Models\KeputusanIppt;
use App\Services\IpptPdfService;
use App\Services\KeputusanIpptNumberService;
use App\Enums\UserRole;
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
use Illuminate\Support\Facades\DB;

class KeputusanIpptRelationManager extends RelationManager
{
    use HasFileViewAction;

    protected static string $relationship = 'keputusanIppt';

    protected static ?string $title = 'Keputusan IPPT';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'pemohon', 'staff', 'tim_teknis', 'kabid', 'kadis']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Keputusan IPPT')
                ->description('Rancangan keputusan disusun setelah rekomendasi teknis disetujui dan Risalah Pertimbangan Teknis diterima.')
                ->columns(1)
                ->schema([
                    TextInput::make('nomor_keputusan')
                        ->label('Nomor Keputusan')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Dibuat otomatis oleh sistem.'),
                    DatePicker::make('tanggal_keputusan')
                        ->label('Tanggal Keputusan')
                        ->required()
                        ->native(false)
                        ->maxDate(now()),
                    Select::make('jenis_keputusan')
                        ->label('Hasil Keputusan')
                        ->options(JenisKeputusan::class)
                        ->required()
                        ->native(false)
                        ->default(JenisKeputusan::Terbit)
                        ->helperText('Pilih “IPPT Diterbitkan” jika permohonan disetujui. Pilih “Permohonan Ditolak” hanya jika keputusan akhirnya menolak permohonan.')
                        ->live(),
                    Textarea::make('alasan')
                        ->label('Alasan / Pertimbangan Keputusan')
                        ->rows(7)
                        ->maxLength(12000)
                        ->required()
                        ->helperText('Jelaskan dasar penetapan. Untuk keputusan penolakan, uraikan alasan secara jelas.')
                        ->columnSpanFull(),
                    Textarea::make('hasil_pertimbangan')
                        ->label('Ringkasan Dasar Teknis')
                        ->rows(8)
                        ->maxLength(12000)
                        ->helperText('Ringkas keterkaitan rekomendasi teknis dan Risalah Pertimbangan Teknis sebagai dasar keputusan.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['disusunOleh', 'disetujuiOleh']))
            ->columns([
                TextColumn::make('nomor_keputusan')
                    ->label('Nomor Keputusan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal_keputusan')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('jenis_keputusan')
                    ->label('Keputusan')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('versi')
                    ->label('Versi')
                    ->badge()
                    ->placeholder('1'),

                TextColumn::make('is_current')
                    ->label('Aktif')
                    ->formatStateUsing(fn ($state): string => $state ? 'Ya' : 'Arsip')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),

                TextColumn::make('disusunOleh.name')
                    ->label('Disusun Oleh')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('disetujuiOleh.name')
                    ->label('Ditetapkan Oleh')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusPersetujuan::class),

                SelectFilter::make('jenis_keputusan')
                    ->label('Jenis Keputusan')
                    ->options(JenisKeputusan::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([
                ActionGroup::make([
                    Action::make('lihat_detail')
                        ->label('Tinjau Detail')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->color('gray')
                        ->modalHeading('Tinjauan Detail Keputusan IPPT')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->infolist(fn (KeputusanIppt $record) => [
                            \Filament\Infolists\Components\TextEntry::make('nomor_keputusan')
                                ->label('Nomor Keputusan')
                                ->placeholder('-'),
                            \Filament\Infolists\Components\TextEntry::make('versi')
                                ->label('Versi')
                                ->badge(),
                            \Filament\Infolists\Components\TextEntry::make('tanggal_keputusan')
                                ->label('Tanggal Keputusan')
                                ->date('d/m/Y')
                                ->placeholder('-'),
                            \Filament\Infolists\Components\TextEntry::make('jenis_keputusan')
                                ->label('Hasil Keputusan')
                                ->badge(),
                            \Filament\Infolists\Components\TextEntry::make('status')
                                ->label('Status')
                                ->badge(),
                            \Filament\Infolists\Components\TextEntry::make('disusunOleh.name')
                                ->label('Disusun Oleh')
                                ->placeholder('-'),
                            \Filament\Infolists\Components\TextEntry::make('disetujuiOleh.name')
                                ->label('Ditetapkan Oleh')
                                ->placeholder('-'),
                            \Filament\Infolists\Components\TextEntry::make('hasil_pertimbangan')
                                ->label('Ringkasan Dasar Teknis')
                                ->prose()
                                ->placeholder('-')
                                ->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('alasan')
                                ->label('Alasan / Pertimbangan Keputusan')
                                ->prose()
                                ->placeholder('-')
                                ->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('alasan_koreksi')
                                ->label('Alasan Koreksi')
                                ->prose()
                                ->placeholder('-')
                                ->columnSpanFull(),
                            \Filament\Infolists\Components\TextEntry::make('is_current')
                                ->label('Status Arsip')
                                ->formatStateUsing(fn ($state): string => $state ? 'Keputusan aktif' : 'Arsip keputusan')
                                ->badge(),
                        ])
                        ->modalWidth('5xl'),

                    Action::make('ajukan')
                        ->label('Ajukan Penetapan')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->visible(fn(KeputusanIppt $record): bool => $record->status === StatusPersetujuan::Draf
                            && auth()->user()->hasRole('staff'))
                        ->requiresConfirmation()
                        ->modalHeading('Ajukan Keputusan')
                        ->modalDescription('Keputusan akan diajukan untuk penetapan.')
                        ->action(function (KeputusanIppt $record): void {
                            $permohonan = $record->permohonan?->loadMissing(['rekomendasiTeknis', 'risalahPertimbangan']);
                            if (! $permohonan
                                || $permohonan->status !== StatusPermohonan::Keputusan
                                || $permohonan->rekomendasiTeknis?->status !== StatusPersetujuan::Disetujui
                                || $permohonan->risalahPertimbangan?->status !== 'diterima') {
                                Notification::make()->danger()->title('Keputusan belum dapat diajukan')->body('Pastikan Rekomendasi Teknis sudah disetujui dan Risalah Pertimbangan Teknis sudah diterima.')->send();
                                return;
                            }

                            $record->update(['status' => StatusPersetujuan::Diajukan]);

                            Notification::make()
                                ->success()
                                ->title('Keputusan diajukan')
                                ->body('Keputusan IPPT berhasil diajukan untuk penetapan.')
                                ->send();
                            $notification = app(\App\Services\IpptWorkflowNotificationService::class);
                            $notification->roles(
                                [\App\Enums\UserRole::KADIS],
                                'Penetapan Keputusan IPPT diperlukan',
                                "Keputusan {$record->nomor_keputusan} pada {$record->permohonan?->nomor_permohonan} menunggu penetapan.",
                                'warning',
                                $notification->applicationAction($record->permohonan, 'Review Keputusan', 'keputusan'),
                            );
                        }),

                    // Penetapan akhir hanya wewenang kadis.
                    Action::make('tetapkan')
                        ->label(fn(KeputusanIppt $record): string => $record->jenis_keputusan === JenisKeputusan::Terbit
                            ? 'Tetapkan & Terbitkan'
                            : 'Tetapkan sebagai Ditolak')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(KeputusanIppt $record): bool => $record->status === StatusPersetujuan::Diajukan
                            && auth()->user()->hasAnyRole(['kadis', 'admin']))
                        ->requiresConfirmation()
                        ->modalHeading(fn(KeputusanIppt $record): string => $record->jenis_keputusan === JenisKeputusan::Terbit
                            ? 'Tetapkan & Terbitkan Keputusan IPPT'
                            : 'Tetapkan Keputusan sebagai Ditolak')
                        ->modalDescription(fn(KeputusanIppt $record): string => $record->jenis_keputusan === JenisKeputusan::Terbit
                            ? 'Anda akan menyetujui dan menetapkan IPPT. Status permohonan akan menjadi Diterbitkan dan PDF keputusan resmi akan dibuat.'
                            : 'Anda akan menetapkan keputusan penolakan. Status permohonan akan menjadi Ditolak dan alasan penolakan akan tercantum dalam keputusan.')
                        ->action(function (KeputusanIppt $record): void {
                            DB::transaction(function () use ($record): void {
                                // Jika ini merupakan koreksi, keputusan sebelumnya tetap disimpan sebagai arsip.
                                // Hanya versi yang baru ditetapkan yang menjadi keputusan aktif/current.
                                $record->permohonan?->keputusanIppt()->update(['is_current' => false]);

                                $record->update([
                                    'status' => StatusPersetujuan::Disetujui,
                                    'is_current' => true,
                                    'disetujui_oleh' => auth()->id(),
                                    'tanggal_keputusan' => $record->tanggal_keputusan ?? now()->toDateString(),
                                ]);
                                $record->refresh();
                                $pdfPath = app(IpptPdfService::class)->store($record);
                                $record->update(['lokasi_file' => $pdfPath]);

                                $permohonan = $record->permohonan;

                                // FIX: sebelumnya set status Permohonan ke string 'diterbitkan'
                                // yang tidak ada di enum StatusPermohonan (nilainya 'selesai').
                                // Sekarang enum sudah punya case Diterbitkan yang sesuai.
                                $permohonan->update([
                                    'status' => $record->jenis_keputusan === JenisKeputusan::Terbit
                                        ? StatusPermohonan::Diterbitkan
                                        : StatusPermohonan::Ditolak,

                                    'disetujui_keputusan_oleh' => auth()->id(),
                                    'disetujui_keputusan_pada' => now(),
                                ]);
                            });

                            $notification = app(\App\Services\IpptWorkflowNotificationService::class);
                            $action = $notification->applicationAction($record->permohonan, 'Lihat Keputusan', 'keputusan');
                            $notification->pemohon(
                                $record->permohonan,
                                'Keputusan IPPT ditetapkan',
                                $record->jenis_keputusan === JenisKeputusan::Terbit
                                    ? "Keputusan {$record->nomor_keputusan} telah ditetapkan dan dokumen resmi tersedia."
                                    : "Keputusan {$record->nomor_keputusan} ditetapkan sebagai penolakan. Alasan: " . ($record->alasan ?: 'Lihat dokumen keputusan untuk alasan lengkap.'),
                                $record->jenis_keputusan === JenisKeputusan::Terbit ? 'success' : 'danger',
                                $action,
                            );
                            $notification->roles(
                                [UserRole::ADMIN, UserRole::STAFF, UserRole::TIM_TEKNIS, UserRole::KADIS],
                                'Keputusan IPPT ditetapkan',
                                "Keputusan {$record->nomor_keputusan} untuk {$record->permohonan?->nomor_permohonan} telah ditetapkan.",
                                'success',
                                $action,
                            );

                            Notification::make()
                                ->success()
                                ->title('Keputusan berhasil ditetapkan')
                                ->body(
                                    $record->jenis_keputusan === JenisKeputusan::Terbit
                                        ? 'IPPT berhasil ditetapkan dan diterbitkan.'
                                        : 'Permohonan IPPT berhasil ditetapkan sebagai ditolak.'
                                )
                                ->send();
                        }),

                    Action::make('kembalikan')
                        ->label('Kembalikan')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->visible(fn(KeputusanIppt $record): bool => $record->status === StatusPersetujuan::Diajukan
                            && auth()->user()->hasAnyRole(['kadis', 'admin']))
                        ->requiresConfirmation()
                        ->modalHeading('Kembalikan Keputusan')
                        ->form([Textarea::make('alasan')->label('Catatan Perbaikan')->required()->rows(5)->maxLength(4000)])
                        ->modalDescription('Keputusan akan dikembalikan ke penyusun untuk diperbaiki.')
                        ->action(function (KeputusanIppt $record, array $data): void {
                            $record->update(['status' => StatusPersetujuan::Ditolak, 'alasan' => $data['alasan']]);

                            Notification::make()
                                ->warning()
                                ->title('Keputusan dikembalikan')
                                ->body('Draft keputusan dapat diperbaiki dan diajukan kembali.')
                                ->send();
                            $notification = app(\App\Services\IpptWorkflowNotificationService::class);
                            $notification->roles(
                                [UserRole::STAFF, UserRole::ADMIN],
                                'Keputusan IPPT perlu diperbaiki',
                                "Keputusan {$record->nomor_keputusan} dikembalikan oleh Kepala Dinas. Catatan: {$data['alasan']}",
                                'danger',
                                $notification->applicationAction($record->permohonan, 'Perbaiki Keputusan', 'keputusan'),
                            );
                        }),

                    static::fileViewAction(name: 'lihat_file_keputusan'),

                    Action::make('koreksi')
                        ->label('Koreksi Keputusan')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(function (KeputusanIppt $record): bool {
                            if ($record->status !== StatusPersetujuan::Disetujui
                                || ! auth()->user()->hasRole('staff')) {
                                return false;
                            }

                            return ! $record->permohonan?->keputusanIppt()
                                ->whereIn('status', [StatusPersetujuan::Draf->value, StatusPersetujuan::Diajukan->value])
                                ->exists();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Koreksi Keputusan IPPT')
                        ->modalDescription('Koreksi tidak mengubah keputusan lama. Sistem membuat keputusan baru dengan nomor resmi baru sebagai draft. Setelah ditetapkan, keputusan baru mencabut dan menggantikan keputusan sebelumnya, sedangkan keputusan lama tetap tersimpan sebagai arsip/audit trail.')
                        ->form([
                            Textarea::make('alasan_koreksi')
                                ->label('Alasan Koreksi')
                                ->required()
                                ->rows(4)
                                ->maxLength(4000)
                                ->helperText('Jelaskan kesalahan yang ditemukan dan apa yang perlu diperbaiki.'),
                            Section::make('Data yang Dikoreksi')
                                ->description('Perbarui langsung data keputusan yang perlu dikoreksi. Setelah draft dibuat, data masih dapat diedit kembali melalui aksi Edit Draft.')
                                ->columns(1)
                                ->schema([
                                    DatePicker::make('tanggal_keputusan')
                                        ->label('Tanggal Keputusan')
                                        ->required()
                                        ->native(false)
                                        ->maxDate(now())
                                        ->default(fn (KeputusanIppt $record) => $record->tanggal_keputusan),
                                    Select::make('jenis_keputusan')
                                        ->label('Hasil Keputusan')
                                        ->options(JenisKeputusan::class)
                                        ->required()
                                        ->native(false)
                                        ->default(fn (KeputusanIppt $record) => $record->jenis_keputusan),
                                    Textarea::make('alasan')
                                        ->label('Alasan / Pertimbangan Keputusan')
                                        ->rows(7)
                                        ->maxLength(12000)
                                        ->required()
                                        ->default(fn (KeputusanIppt $record) => $record->alasan)
                                        ->columnSpanFull(),
                                    Textarea::make('hasil_pertimbangan')
                                        ->label('Ringkasan Dasar Teknis')
                                        ->rows(8)
                                        ->maxLength(12000)
                                        ->default(fn (KeputusanIppt $record) => $record->hasil_pertimbangan)
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->action(function (KeputusanIppt $record, array $data): void {
                            DB::transaction(function () use ($record, $data): void {
                                $new = $record->replicate([
                                    'nomor_keputusan',
                                    'status',
                                    'disusun_oleh',
                                    'disetujui_oleh',
                                    'lokasi_file',
                                    'created_at',
                                    'updated_at',
                                ]);

                                $new->permohonan_id = $record->permohonan_id;
                                $new->disusun_oleh = auth()->id();
                                $new->disetujui_oleh = null;
                                $new->nomor_keputusan = app(KeputusanIpptNumberService::class)->generate(now());
                                $new->status = StatusPersetujuan::Draf;
                                $new->lokasi_file = null;
                                $new->tanggal_keputusan = $data['tanggal_keputusan'];
                                $new->jenis_keputusan = $data['jenis_keputusan'];
                                $new->alasan = $data['alasan'];
                                $new->hasil_pertimbangan = $data['hasil_pertimbangan'] ?? null;
                                $new->versi = ((int) ($record->versi ?? 1)) + 1;
                                $new->revisi_dari_id = $record->id;
                                $new->alasan_koreksi = $data['alasan_koreksi'];
                                $new->dikoreksi_oleh = auth()->id();
                                $new->dikoreksi_pada = now();
                                $new->save();

                                // Koreksi atas keputusan yang sudah ditetapkan membuka kembali
                                // tahap keputusan. Rekomendasi dan Risalah tetap sah; yang diulang
                                // hanya penyusunan/pengajuan keputusan versi koreksi.
                                $record->permohonan?->update([
                                    'status' => StatusPermohonan::Keputusan,
                                ]);
                            });

                            Notification::make()
                                ->success()
                                ->title('Draft koreksi berhasil dibuat')
                                ->body('Keputusan lama tetap tersimpan sebagai arsip. Silakan periksa dan ajukan versi koreksi untuk penetapan ulang.')
                                ->send();

                            $notification = app(\App\Services\IpptWorkflowNotificationService::class);
                            $notification->roles(
                                [\App\Enums\UserRole::KADIS],
                                'Koreksi Keputusan IPPT dibuat',
                                "Keputusan {$record->nomor_keputusan} memiliki draft koreksi yang menunggu pemeriksaan dan penetapan.",
                                'warning',
                                $notification->applicationAction($record->permohonan, 'Review Koreksi', 'keputusan'),
                            );
                        }),

                    Action::make('generate_pdf')
                        ->label('Generate PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->visible(fn(KeputusanIppt $record): bool => $record->status === StatusPersetujuan::Disetujui
                            && auth()->user()->hasAnyRole(['staff', 'kadis', 'pemohon', 'admin']))
                        ->action(function (KeputusanIppt $record) {
                            $pdf = app(IpptPdfService::class)->generate($record);

                            $safeNomor = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $record->nomor_keputusan);
                            $filename = 'keputusan-ippt-' . trim($safeNomor, '-.') . '.pdf';

                            return response()->streamDownload(
                                fn() => print($pdf->output()),
                                $filename,
                            );
                        }),

                    EditAction::make()
                        ->label('Edit Draft Koreksi')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn(KeputusanIppt $record): bool => in_array($record->status->value, StatusPersetujuan::editableValues(), true)
                            && auth()->user()->hasRole('staff')),

                    DeleteAction::make()
                        ->visible(fn(KeputusanIppt $record): bool => in_array($record->status->value, StatusPersetujuan::editableValues(), true)
                            && auth()->user()->hasRole('admin')),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading('Belum ada keputusan')
            ->emptyStateDescription('Setelah Rekomendasi Teknis disetujui dan Risalah Pertimbangan diterima, Staff dapat membuat Draft Keputusan dari tombol aksi pada halaman View Permohonan.')
            ->emptyStateIcon('heroicon-o-document-check');
    }
}
