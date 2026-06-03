<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->required(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required(),
                Select::make('service_id')
                    ->relationship('service', 'name')
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->required(),
                Select::make('status')
                    ->options(AppointmentStatus::class)
                    ->default('pending')
                    ->required(),
                Textarea::make('cancellation_reason')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
