<?php

namespace App\Filament\Resources\Permohonans;

use App\Filament\Resources\Permohonans\Pages\CreatePermohonan;
use App\Filament\Resources\Permohonans\Pages\EditPermohonan;
use App\Filament\Resources\Permohonans\Pages\ListPermohonans;
use App\Filament\Resources\Permohonans\Pages\ViewPermohonan;
use App\Filament\Resources\Permohonans\RelationManagers\DokumenPermohonansRelationManager;
use App\Filament\Resources\Permohonans\RelationManagers\KeputusanIpptRelationManager;
use App\Filament\Resources\Permohonans\RelationManagers\PemeriksaanLapanganRelationManager;
use App\Filament\Resources\Permohonans\RelationManagers\RekomendasiTeknisRelationManager;
use App\Filament\Resources\Permohonans\RelationManagers\RisalahPertimbanganRelationManager;
use App\Filament\Resources\Permohonans\Schemas\PermohonanForm;
use App\Filament\Resources\Permohonans\Schemas\PermohonanInfolist;
use App\Filament\Resources\Permohonans\Tables\PermohonansTable;
use App\Models\Permohonan;
use App\Models\RekomendasiTeknis;
use App\Models\RisalahPertimbangan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;


class PermohonanResource extends Resource
{
    protected static ?string $model = Permohonan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Permohonan IPPT';

    protected static ?string $modelLabel = 'Permohonan IPPT';

    protected static ?string $pluralModelLabel = 'Permohonan IPPT';

    // protected static string|\UnitEnum|null $navigationGroup = 'Permohonan IPPT';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'nomor_permohonan';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return PermohonanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PermohonanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermohonansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            'dokumen' => DokumenPermohonansRelationManager::class,
            'pemeriksaan' => PemeriksaanLapanganRelationManager::class,
            'rekomendasi' => RekomendasiTeknisRelationManager::class,
            'risalah' => RisalahPertimbanganRelationManager::class,
            'keputusan' => KeputusanIpptRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermohonans::route('/'),
            'create' => CreatePermohonan::route('/create'),
            'view' => ViewPermohonan::route('/{record}'),
            'edit' => EditPermohonan::route('/{record}/edit'),
        ];
    }
}
