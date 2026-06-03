<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Customer;
use App\Models\User;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;


class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('branch_id')
                ->label('Branch')
                ->options(Branch::pluck('name', 'id'))
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('user_id', null)), // Reset staff selection when branch changes

            Select::make('user_id')
                ->label('Staff')
                ->options(function (Get $get) {
                    $branchId = $get('branch_id');

                    if (!$branchId) {
                        return [];
                    }

                    return User::where('branch_id', $branchId)
                        ->where('role', UserRole::Staff)
                        ->pluck('name', 'id');
                })
                ->required()
                ->searchable()
                ->live(),

            Select::make('customer_id')
                ->label('Customer')
                ->options(Customer::pluck('name', 'id'))
                ->required()
                ->searchable()
                ->createOptionForm([
                    TextInput::make('name')->required(),
                    TextInput::make('email')->email(),
                    TextInput::make('phone'),
                ]),

            Select::make('service_id')
                ->label('Service')
                ->options(Service::pluck('name', 'id'))
                ->required()
                ->searchable(),

            DateTimePicker::make('starts_at')
                ->label('Start Date & Time (Branch Local Time)')
                ->required()
                ->seconds(false),

            Select::make('status')
                ->options(AppointmentStatus::class) // AppointmentStatus enum contains HasLabels function for auto label generation
                ->visible(fn ($record) => filled($record)) // Only show status field when editing an existing appointment
                ->required(),


            Textarea::make('cancellation_reason')
                ->visible(fn (Get $get) => $get('status') === AppointmentStatus::Cancelled->value)
                ->required(fn (Get $get) => $get('status') === AppointmentStatus::Cancelled->value),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('customer.name')->searchable(),
                TextColumn::make('staff.name')->label('Staff')->searchable(),
                TextColumn::make('service.name'),
                TextColumn::make('branch.name'),

                TextColumn::make('starts_at')
                    ->label('Start Time')
                    ->dateTime('d M Y, h:i A')
                    ->description(fn ($record) => $record->branch->timezone) // Show branch timezone as description
                    ->sortable(),

                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(AppointmentStatus::class),

                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(Branch::pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);

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
            'index'  => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
            'edit'   => EditAppointment::route('/{record}/edit'),
        ];
    }
}
