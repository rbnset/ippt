<?php

namespace App\Filament\Resources\Pemohons;

use App\Filament\Resources\Pemohons\Pages\CreatePemohon;
use App\Filament\Resources\Pemohons\Pages\EditPemohon;
use App\Filament\Resources\Pemohons\Pages\ListPemohons;
use App\Filament\Resources\Pemohons\Pages\ViewPemohon;
use App\Filament\Resources\Pemohons\Schemas\PemohonForm;
use App\Filament\Resources\Pemohons\Schemas\PemohonInfolist;
use App\Filament\Resources\Pemohons\Tables\PemohonsTable;
use App\Models\Pemohon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\UserRole;

class PemohonResource extends Resource
{
    protected static ?string $model = Pemohon::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Pemohon';

    protected static ?string $modelLabel = 'Pemohon';

    protected static ?string $pluralModelLabel = 'Pemohon';

    protected static string|\UnitEnum|null $navigationGroup = 'Permohonan IPPT';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole(UserRole::PEMOHON)) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return PemohonForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PemohonInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PemohonsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPemohons::route('/'),
            'create' => CreatePemohon::route('/create'),
            'view' => ViewPemohon::route('/{record}'),
            'edit' => EditPemohon::route('/{record}/edit'),
        ];
    }
}
