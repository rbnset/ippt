<?php

namespace App\Filament\Resources\Permohonans\Schemas;

use App\Enums\JenisDokumen;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PermohonanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Wizard::make([
                    Step::make('Data Permohonan')
                        ->icon(Heroicon::DocumentText)
                        ->description('Isi identitas pemohon dan data tanah yang dimohonkan.')
                        ->schema([
                            Section::make('Identitas Permohonan')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('nomor_permohonan')
                                            ->label('Nomor Permohonan')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->placeholder('Otomatis saat disimpan')
                                            ->helperText('Nomor dibuat otomatis oleh sistem sesuai format IPPT/YYYY/MM/####.'),

                                        TextInput::make('tanggal_permohonan')
                                            ->label('Tanggal Permohonan')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->placeholder('Otomatis mengikuti tanggal pengajuan')
                                            ->helperText('Tanggal ditetapkan otomatis saat permohonan dibuat.'),

                                        Select::make('pemohon_id')
                                            ->label('Pemohon')
                                            ->relationship(name: 'pemohon', titleAttribute: 'nama')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->native(false)
                                            ->columnSpanFull()
                                            ->visible(fn (): bool => ! auth()->user()?->hasRole(\App\Enums\UserRole::PEMOHON)),

                                        Hidden::make('pemohon_id')
                                            ->default(fn (): ?int => auth()->user()?->pemohon?->id)
                                            ->required()
                                            ->dehydrated()
                                            ->visible(fn (): bool => auth()->user()?->hasRole(\App\Enums\UserRole::PEMOHON)),

                                        Section::make('Data Pemohon Terdaftar')
                                            ->description('Identitas diambil otomatis dari akun yang sedang login. Data ini tidak dapat diganti saat mengajukan permohonan secara online.')
                                            ->icon(Heroicon::User)
                                            ->visible(fn (): bool => auth()->user()?->hasRole(\App\Enums\UserRole::PEMOHON))
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('pemohon_nama_display')
                                                        ->label('Nama Pemohon')
                                                        ->default(fn (): ?string => auth()->user()?->pemohon?->nama)
                                                        ->disabled()
                                                        ->dehydrated(false),
                                                    TextInput::make('pemohon_nik_display')
                                                        ->label('NIK')
                                                        ->default(fn (): ?string => auth()->user()?->pemohon?->nik)
                                                        ->disabled()
                                                        ->dehydrated(false),
                                                    TextInput::make('pemohon_telepon_display')
                                                        ->label('Nomor Telepon / WhatsApp')
                                                        ->default(fn (): ?string => auth()->user()?->pemohon?->nomor_telepon)
                                                        ->disabled()
                                                        ->dehydrated(false),
                                                    TextInput::make('pemohon_email_display')
                                                        ->label('Email Akun')
                                                        ->default(fn (): ?string => auth()->user()?->email)
                                                        ->disabled()
                                                        ->dehydrated(false),
                                                ]),
                                            ])
                                            ->columnSpanFull(),

                                        Toggle::make('diwakilkan')
                                            ->label('Pengurusan dikuasakan kepada pihak lain')
                                            ->helperText('Aktifkan jika permohonan diurus oleh pemegang kuasa.')
                                            ->live()
                                            ->default(false),

                                        TextInput::make('nama_pemegang_kuasa')
                                            ->label('Nama Pemegang Kuasa')
                                            ->maxLength(150)
                                            ->visible(fn (Get $get): bool => (bool) $get('diwakilkan'))
                                            ->required(fn (Get $get): bool => (bool) $get('diwakilkan')),

                                        TextInput::make('nik_pemegang_kuasa')
                                            ->label('NIK Pemegang Kuasa')
                                            ->length(16)
                                            ->regex('/^[0-9]{16}$/')
                                            ->visible(fn (Get $get): bool => (bool) $get('diwakilkan'))
                                            ->required(fn (Get $get): bool => (bool) $get('diwakilkan')),
                                    ]),
                                ]),

                            Section::make('Data Tanah')
                                ->schema([
                                    TextInput::make('lokasi_tanah')
                                        ->label('Lokasi Tanah')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    Grid::make(2)->schema([
                                        TextInput::make('luas_tanah')
                                            ->label('Luas Tanah')
                                            ->numeric()
                                            ->suffix('m²')
                                            ->required()
                                            ->minValue(0)
                                            ->maxValue(99999999.99),

                                        TextInput::make('nomor_hak')
                                            ->label('Nomor Hak / Sertipikat')
                                            ->maxLength(50),
                                    ]),

                                    Grid::make(2)->schema([
                                        TextInput::make('penggunaan_sekarang')
                                            ->label('Penggunaan Tanah Saat Ini')
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder('Contoh: Sawah / Pekarangan'),

                                        TextInput::make('penggunaan_dimohonkan')
                                            ->label('Penggunaan Tanah yang Dimohonkan')
                                            ->required()
                                            ->maxLength(100)
                                            ->placeholder('Contoh: Rumah Tinggal'),
                                    ]),

                                    Textarea::make('keterangan')
                                        ->label('Keterangan Tambahan')
                                        ->rows(4)
                                        ->maxLength(5000)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Step::make('Dokumen Persyaratan')
                        ->visibleOn('create')
                        ->icon(Heroicon::PaperClip)
                        ->description('Semua dokumen wajib harus tersedia sebelum permohonan diajukan.')
                        ->schema([
                            Section::make('Persyaratan Wajib')
                                ->description('Daftar mengikuti persyaratan layanan IPPT. Dokumen tidak boleh dilewati. Surat kuasa dan KTP pemegang kuasa hanya wajib jika permohonan dikuasakan.')
                                ->schema([
                                    FileUpload::make('dokumen_ktp_pemohon')
                                        ->label('1. KTP Pemohon')
                                        ->required()
                                        ->disk('private')
                                        ->directory('dokumen-ippt-upload')
                                        ->visibility('private')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->downloadable(false)
                                        ->openable(false)
                                        ->helperText('Wajib. PDF/JPG/PNG, maksimal 10 MB.'),

                                    FileUpload::make('dokumen_ktp_pemegang_kuasa')
                                        ->label('2. KTP Pemegang Kuasa')
                                        ->visible(fn (Get $get): bool => (bool) $get('diwakilkan'))
                                        ->required(fn (Get $get): bool => (bool) $get('diwakilkan'))
                                        ->disk('private')
                                        ->directory('dokumen-ippt-upload')
                                        ->visibility('private')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->downloadable(false)
                                        ->openable(false)
                                        ->helperText('Wajib jika menggunakan kuasa.'),

                                    FileUpload::make('dokumen_bukti_hak')
                                        ->label('3. Bukti Hak Atas Tanah')
                                        ->required()
                                        ->disk('private')
                                        ->directory('dokumen-ippt-upload')
                                        ->visibility('private')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->downloadable(false)
                                        ->openable(false),

                                    FileUpload::make('dokumen_surat_kuasa')
                                        ->label('4. Surat Kuasa Bermeterai')
                                        ->visible(fn (Get $get): bool => (bool) $get('diwakilkan'))
                                        ->required(fn (Get $get): bool => (bool) $get('diwakilkan'))
                                        ->disk('private')
                                        ->directory('dokumen-ippt-upload')
                                        ->visibility('private')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->downloadable(false)
                                        ->openable(false)
                                        ->helperText('Wajib jika permohonan dikuasakan.'),

                                    FileUpload::make('dokumen_tidak_sengketa')
                                        ->label('5. Surat Pernyataan Tanah Tidak Dalam Sengketa')
                                        ->required()
                                        ->disk('private')
                                        ->directory('dokumen-ippt-upload')
                                        ->visibility('private')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->downloadable(false)
                                        ->openable(false),

                                    FileUpload::make('dokumen_pbb')
                                        ->label('6. Bukti Pelunasan PBB Tahun Terakhir')
                                        ->required()
                                        ->disk('private')
                                        ->directory('dokumen-ippt-upload')
                                        ->visibility('private')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->downloadable(false)
                                        ->openable(false),

                                    FileUpload::make('dokumen_denah_lokasi')
                                        ->label('7. Denah dan Koordinat Lokasi Tanah')
                                        ->required()
                                        ->disk('private')
                                        ->directory('dokumen-ippt-upload')
                                        ->visibility('private')
                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                        ->maxSize(10240)
                                        ->downloadable(false)
                                        ->openable(false)
                                        ->helperText('Wajib. Denah harus jelas dan mencantumkan informasi koordinat lokasi.'),
                                ])
                                ->columns(2),

                            Section::make('Pemeriksaan Kelengkapan')
                                ->description('Sistem hanya dapat membuat permohonan setelah seluruh persyaratan wajib tervalidasi.')
                                ->icon(Heroicon::ShieldCheck)
                                ->schema([
                                    TextInput::make('dokumen_check_info')
                                        ->label('Status Kelengkapan')
                                        ->default('Lengkap — seluruh dokumen wajib akan diperiksa sebelum pengajuan.')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                ->persistStepInQueryString()
                ->columnSpanFull(),
            ]);
    }
}
