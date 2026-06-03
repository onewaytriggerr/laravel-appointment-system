<?php

namespace App\Filament\Staff\Resources\Appointments;

use Illuminate\Support\Facades\Auth;
use App\Filament\Staff\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Staff\Resources\Appointments\Pages\ListAppointments;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Appointment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\EditAction;
use App\Enums\AppointmentStatus;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function canViewAny(): bool
    {
        return true;
    }

    // Only show appointments for the currently authenticated staff member
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    public static function form(Schema $form): Schema
    {
        return $form->components([
        Select::make('status')
            ->options(fn (?Appointment $record) => $record?->status?->allowedTransitionsOptions() ?? []) // If editing, show allowed transitions based on current status. If creating, no options until status is set
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
                TextColumn::make('customer.name'),
                TextColumn::make('service.name'),
                TextColumn::make('starts_at')
                    ->formatStateUsing(function ($record) {
                        $tz = $record->branch->timezone;
                        return $record->starts_at->setTimezone($tz)->format('d M Y, h:i A');
                    }),
                TextColumn::make('status')
                    ->formatStateUsing(fn ($state) => $state->label()),
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
            'index' => ListAppointments::route('/'),
            'edit'  => EditAppointment::route('/{record}/edit'),
        ];
    }
}
