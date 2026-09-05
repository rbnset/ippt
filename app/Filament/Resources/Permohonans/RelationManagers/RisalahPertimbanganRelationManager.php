<?php

namespace App\Filament\Resources\Permohonans\RelationManagers;

use App\Enums\HasilRisalah;
use App\Filament\Concerns\HasFileViewAction;
use App\Models\RisalahPertimbangan;
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
        return $schema
            ->components([
                Section::make('Data Risalah Pertimbangan')
                    ->description('Data risalah pertimbangan teknis yang diterima dari Kantor Pertanahan.')
                    ->schema([
                        TextInput::make('nomor_risalah')
                            ->label('Nomor Risalah')
                            ->maxLength(50)
                            ->placeholder('Masukkan nomor risalah')
                            ->unique(
                                table: 'risalah_pertimbangan',
                                column: 'nomor_risalah',
                                ignoreRecord: true
                            ),

                        DatePicker::make('tanggal_risalah')
                            ->label('Tanggal Risalah')
                            ->native(false)
                            ->maxDate(now()),

                        Select::make('hasil')
                            ->label('Hasil Pertimbangan')
                            ->options(HasilRisalah::class)
                            ->native(false),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(5)
                            ->maxLength(5000)
                            ->columnSpanFull(),

                        FileUpload::make('lokasi_file')
                            ->label('File Risalah')
                            ->disk('private')
                            ->directory('risalah-pertimbangan')
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
                            ->helperText('Format PDF, JPG, atau PNG. Maksimal 10 MB.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('tanggal_risalah')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('hasil')
                    ->label('Hasil')
                    ->badge(),

                TextColumn::make('diterimaOleh.name')
                    ->label('Diterima Oleh')
                    ->placeholder('-'),

                TextColumn::make('lokasi_file')
                    ->label('File')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => $state ? 'Tersedia' : 'Tidak Ada')
                    ->color(fn(?string $state): string => $state ? 'success' : 'gray'),

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
                CreateAction::make()
                    ->label('Tambah Risalah')
                    ->icon('heroicon-o-document-arrow-up')
                    ->visible(fn(): bool => auth()->user()->hasAnyRole(['staff', 'admin']))
                    ->mutateFormDataUsing(function (array $data): array {
                        // BUG SEBELUMNYA: kolom ini tidak pernah diisi meski
                        // ditampilkan di tabel sebagai "Diterima Oleh".
                        $data['diterima_oleh'] = Auth::id();

                        return $data;
                    })
                    ->after(function (RisalahPertimbangan $record): void {
                        Notification::make()
                            ->success()
                            ->title('Risalah berhasil ditambahkan')
                            ->body('Risalah pertimbangan teknis berhasil disimpan.')
                            ->send();
                    }),
            ])
            ->recordActions([
                static::fileViewAction(),

                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn(): bool => auth()->user()->hasAnyRole(['staff', 'admin']))
                        ->after(function (RisalahPertimbangan $record): void {
                            Notification::make()
                                ->success()
                                ->title('Risalah berhasil diperbarui')
                                ->send();
                        }),

                    DeleteAction::make()
                        ->visible(fn(): bool => auth()->user()->hasRole('admin'))
                        ->before(function (RisalahPertimbangan $record): void {
                            if ($record->lokasi_file && Storage::disk('private')->exists($record->lokasi_file)) {
                                Storage::disk('private')->delete($record->lokasi_file);
                            }
                        }),
                ])
                    ->label('Lainnya')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->emptyStateHeading('Belum ada risalah pertimbangan')
            ->emptyStateDescription('Risalah diterima dari Kantor Pertanahan setelah proses rekomendasi teknis selesai.')
            ->emptyStateIcon('heroicon-o-scale')
            ->defaultSort('tanggal_risalah', 'desc');
    }
}
