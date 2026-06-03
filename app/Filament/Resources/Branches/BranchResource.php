<?php

namespace App\Filament\Resources\Branches;

use App\Filament\Resources\Branches\Pages\CreateBranch;
use App\Filament\Resources\Branches\Pages\EditBranch;
use App\Filament\Resources\Branches\Pages\ListBranches;
use App\Models\Branch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('address')
                ->required()
                ->maxLength(500),

            TextInput::make('phone')
                ->required()
                ->tel()
                ->placeholder('+60123456789'),

            Select::make('timezone')
                ->required()
                ->searchable()
                ->options(array_combine(
                    timezone_identifiers_list(),
                    timezone_identifiers_list()
                ))
                ->default('UTC'),

            TimePicker::make('opening_time')
                ->required()
                ->seconds(false),

            TimePicker::make('closing_time')
                ->required()
                ->seconds(false)
                ->after('opening_time'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('timezone'),
                TextColumn::make('opening_time'),
                TextColumn::make('closing_time'),
                TextColumn::make('phone'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
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
            'index'  => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'edit'   => EditBranch::route('/{record}/edit'),
        ];
    }
}
