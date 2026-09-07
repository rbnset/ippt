<?php

namespace App\Filament\Resources\Permohonans\Pages;

use App\Enums\JenisKeputusan;
use App\Enums\StatusPersetujuan;
use App\Enums\StatusPermohonan;
use App\Enums\UserRole;
use App\Filament\Resources\Permohonans\PermohonanResource;
use App\Services\KeputusanIpptNumberService;
use App\Models\KeputusanIppt;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\DB;

class ViewPermohonan extends ViewRecord
{
    protected static string $resource = PermohonanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buat_keputusan')
                ->label('Buat Draft Keputusan')
                ->icon('heroicon-o-document-check')
                ->color('primary')
                ->visible(function (): bool {
                    if (! auth()->user()->hasRole(UserRole::STAFF)) {
                        return false;
                    }

                    $permohonan = $this->record->loadMissing(['rekomendasiTeknis', 'risalahPertimbangan']);

                    return $permohonan->status === StatusPermohonan::Keputusan
                        && $permohonan->rekomendasiTeknis?->status === StatusPersetujuan::Disetujui
                        && $permohonan->risalahPertimbangan?->status === 'diterima'
                        && ! $permohonan->keputusanIppt()->exists();
                })
                ->modalHeading('Buat Draft Keputusan IPPT')
                ->modalDescription('Draft keputusan hanya dapat disusun oleh Staff setelah Rekomendasi Teknis disetujui dan Risalah Pertimbangan Teknis diterima.')
                ->form([
                    Section::make('Informasi Keputusan')
                        ->columns(1)
                        ->schema([
                            TextInput::make('nomor_keputusan')
                                ->label('Nomor Keputusan')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Dibuat otomatis setelah disimpan.'),
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
                                ->default(JenisKeputusan::Terbit),
                            Textarea::make('alasan')
                                ->label('Alasan / Pertimbangan Keputusan')
                                ->rows(7)
                                ->maxLength(12000)
                                ->required()
                                ->columnSpanFull(),
                            Textarea::make('hasil_pertimbangan')
                                ->label('Ringkasan Dasar Teknis')
                                ->rows(8)
                                ->maxLength(12000)
                                ->columnSpanFull()
                                ->helperText('Ringkas keterkaitan Rekomendasi Teknis dan Risalah Pertimbangan Teknis sebagai dasar keputusan.'),
                        ]),
                ])
                ->action(function (array $data): void {
                    $permohonan = $this->record->loadMissing(['rekomendasiTeknis', 'risalahPertimbangan']);

                    if (! auth()->user()->hasRole(UserRole::STAFF)
                        || $permohonan->status !== StatusPermohonan::Keputusan
                        || $permohonan->rekomendasiTeknis?->status !== StatusPersetujuan::Disetujui
                        || $permohonan->risalahPertimbangan?->status !== 'diterima'
                        || $permohonan->keputusanIppt()->exists()) {
                        Notification::make()
                            ->danger()
                            ->title('Draft keputusan belum dapat dibuat')
                            ->body('Pastikan tahap Keputusan sudah aktif, Rekomendasi Teknis sudah disetujui, Risalah Pertimbangan sudah diterima, dan belum ada draft keputusan.')
                            ->send();
                        return;
                    }

                    DB::transaction(function () use ($permohonan, $data): void {
                        $rekomendasi = $permohonan->rekomendasiTeknis;
                        $risalah = $permohonan->risalahPertimbangan;

                        KeputusanIppt::create([
                            'permohonan_id' => $permohonan->id,
                            'disusun_oleh' => auth()->id(),
                            'nomor_keputusan' => app(KeputusanIpptNumberService::class)->generate(now()),
                            'tanggal_keputusan' => $data['tanggal_keputusan'],
                            'jenis_keputusan' => $data['jenis_keputusan'],
                            'status' => StatusPersetujuan::Draf,
                            'alasan' => $data['alasan'],
                            'hasil_pertimbangan' => filled($data['hasil_pertimbangan'] ?? null)
                                ? $data['hasil_pertimbangan']
                                : sprintf(
                                    'Rekomendasi Teknis %s tanggal %s dengan hasil %s. Risalah Pertimbangan Teknis %s tanggal %s dengan hasil %s.',
                                    $rekomendasi?->nomor_rekomendasi ?? '-',
                                    $rekomendasi?->tanggal_rekomendasi?->format('d/m/Y') ?? '-',
                                    $rekomendasi?->hasil?->getLabel() ?? '-',
                                    $risalah?->nomor_risalah ?? '-',
                                    $risalah?->tanggal_risalah?->format('d/m/Y') ?? '-',
                                    $risalah?->hasil?->getLabel() ?? '-',
                                ),
                            'versi' => 1,
                            'is_current' => true,
                        ]);
                    });

                    Notification::make()
                        ->success()
                        ->title('Draft Keputusan berhasil dibuat')
                        ->body('Draft Keputusan IPPT tersimpan. Staff dapat meninjaunya dan mengajukannya untuk penetapan melalui menu Aksi pada tab Keputusan.')
                        ->send();
                }),

            EditAction::make()
                ->visible(fn (): bool => ! auth()->user()->hasRole(UserRole::KABID)),
        ];
    }
}
