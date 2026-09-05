<?php

namespace App\Filament\Resources\Permohonans\RelationManagers;

use App\Enums\JenisKeputusan;
use App\Enums\StatusPermohonan;
use App\Enums\StatusPersetujuan;
use App\Filament\Concerns\HasFileViewAction;
use App\Models\KeputusanIppt;
use App\Services\IpptPdfService;
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
use Filament\Schemas\Components\Grid;
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
        return ! auth()->user()->hasRole('pemohon');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Keputusan')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('nomor_keputusan')
                                ->label('Nomor Keputusan')
                                ->maxLength(50)
                                ->required(),

                            DatePicker::make('tanggal_keputusan')
                                ->label('Tanggal Keputusan')
                                ->required()
                                ->native(false),

                            Select::make('jenis_keputusan')
                                ->label('Jenis Keputusan')
                                ->options(JenisKeputusan::class)
                                ->required()
                                ->native(false)
                                ->live(),

                            Select::make('status')
                                ->label('Status')
                                ->options(StatusPersetujuan::class)
                                ->default(StatusPersetujuan::Draf->value)
                                ->required()
                                ->native(false),
                        ]),

                    Textarea::make('alasan')
                        ->label('Alasan / Pertimbangan Keputusan')
                        ->rows(5)
                        ->columnSpanFull()
                        ->helperText('Wajib diisi terutama apabila keputusan berupa penolakan.'),

                    FileUpload::make('lokasi_file')
                        ->label('File Keputusan')
                        ->disk('private')
                        ->directory('keputusan-ippt')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->downloadable(false)
                        ->openable(false)
                        ->columnSpanFull()
                        ->helperText('Format PDF, maksimal 10 MB.'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
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
            ->headerActions([
                CreateAction::make()
                    ->label('Buat Keputusan')
                    ->icon('heroicon-o-document-check')
                    ->visible(function (): bool {
                        if (! auth()->user()->hasAnyRole(['staff', 'kabid', 'admin'])) {
                            return false;
                        }

                        $permohonan = $this->getOwnerRecord();

                        return $permohonan->rekomendasiTeknis?->status === StatusPersetujuan::Disetujui
                            && $permohonan->risalahPertimbangan()->exists();
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['disusun_oleh'] = auth()->id();

                        return $data;
                    })
                    ->after(function (): void {
                        Notification::make()
                            ->success()
                            ->title('Keputusan berhasil dibuat')
                            ->body('Draft keputusan IPPT berhasil dibuat.')
                            ->send();
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('ajukan')
                        ->label('Ajukan Penetapan')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->visible(fn(KeputusanIppt $record): bool => $record->status === StatusPersetujuan::Draf
                            && auth()->user()->hasAnyRole(['staff', 'kabid', 'admin']))
                        ->requiresConfirmation()
                        ->modalHeading('Ajukan Keputusan')
                        ->modalDescription('Keputusan akan diajukan untuk penetapan.')
                        ->action(function (KeputusanIppt $record): void {
                            $record->update(['status' => StatusPersetujuan::Diajukan]);

                            Notification::make()
                                ->success()
                                ->title('Keputusan diajukan')
                                ->body('Keputusan IPPT berhasil diajukan untuk penetapan.')
                                ->send();
                        }),

                    // Penetapan akhir hanya wewenang kadis.
                    Action::make('tetapkan')
                        ->label('Tetapkan Keputusan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(KeputusanIppt $record): bool => $record->status === StatusPersetujuan::Diajukan
                            && auth()->user()->hasAnyRole(['kadis', 'admin']))
                        ->requiresConfirmation()
                        ->modalHeading('Tetapkan Keputusan IPPT')
                        ->modalDescription(fn(KeputusanIppt $record): string => $record->jenis_keputusan === JenisKeputusan::Terbit
                            ? 'Keputusan ini akan menetapkan IPPT untuk diterbitkan.'
                            : 'Keputusan ini akan menetapkan permohonan sebagai ditolak.')
                        ->action(function (KeputusanIppt $record): void {
                            DB::transaction(function () use ($record): void {
                                $record->update([
                                    'status' => StatusPersetujuan::Disetujui,
                                    'disetujui_oleh' => auth()->id(),
                                ]);

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
                        ->modalDescription('Keputusan akan dikembalikan ke status draf untuk diperbaiki.')
                        ->action(function (KeputusanIppt $record): void {
                            $record->update(['status' => StatusPersetujuan::Ditolak]);

                            Notification::make()
                                ->warning()
                                ->title('Keputusan dikembalikan')
                                ->body('Draft keputusan dapat diperbaiki dan diajukan kembali.')
                                ->send();
                        }),

                    static::fileViewAction(name: 'lihat_file_keputusan'),

                    Action::make('generate_pdf')
                        ->label('Generate PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->visible(fn(KeputusanIppt $record): bool => $record->status === StatusPersetujuan::Disetujui
                            && auth()->user()->hasAnyRole(['staff', 'kabid', 'kadis', 'admin']))
                        ->action(function (KeputusanIppt $record) {
                            $pdf = app(IpptPdfService::class)->generate($record);

                            $filename = 'keputusan-ippt-' . $record->nomor_keputusan . '.pdf';

                            return response()->streamDownload(
                                fn() => print($pdf->output()),
                                $filename,
                            );
                        }),

                    EditAction::make()
                        ->visible(fn(KeputusanIppt $record): bool => in_array($record->status->value, StatusPersetujuan::editableValues(), true)
                            && auth()->user()->hasAnyRole(['staff', 'kabid', 'admin'])),

                    DeleteAction::make()
                        ->visible(fn(KeputusanIppt $record): bool => in_array($record->status->value, StatusPersetujuan::editableValues(), true)
                            && auth()->user()->hasRole('admin')),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading('Belum ada keputusan')
            ->emptyStateDescription('Keputusan dapat dibuat setelah rekomendasi teknis disetujui dan risalah pertimbangan diterima.')
            ->emptyStateIcon('heroicon-o-document-check');
    }
}
