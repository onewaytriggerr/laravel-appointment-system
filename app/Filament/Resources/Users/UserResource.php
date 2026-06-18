<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Models\StaffWorkingHours;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Models\Branch;
use App\Enums\UserRole;
use App\Enums\DayOfWeek;
use Filament\Schemas\Components\Utilities\Get;



class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $label = 'Staff / Admin';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            TextInput::make('password')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create') // Only required when creating a new user
                ->minLength(8)
                ->dehydrated(fn (?string $state): bool => filled($state)), // Only send to the server if the password field is filled

            Select::make('role')
                ->options(UserRole::class)
                ->required()
                ->default(UserRole::Staff->value)
                ->live(), // Ensures instant state broadcast via Livewire

            Select::make('branch_id')
                ->label('Branch')
                ->options(Branch::pluck('name', 'id'))
                ->nullable()
                ->visible(fn (Get $get): bool => in_array($get('role'), [UserRole::Staff, UserRole::Staff->value], true)) // Only show branch selection for staff users
                ->required(fn (Get $get): bool => in_array($get('role'), [UserRole::Staff, UserRole::Staff->value], true)),

            Repeater::make('workingHours')
                ->relationship()
                ->schema([
                    Select::make('day_of_week')
                        ->options(DayOfWeek::class)
                        ->required(),

                    TimePicker::make('start_time')
                        ->required()
                        ->seconds(false),

                    TimePicker::make('end_time')
                        ->required()
                        ->seconds(false),
                ])
                ->visible(fn (Get $get): bool => in_array($get('role'), [UserRole::Staff, UserRole::Staff->value], true)) // Only show when staff role is selected
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')
                    ->badge(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->placeholder('No branch'),
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
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }
}
