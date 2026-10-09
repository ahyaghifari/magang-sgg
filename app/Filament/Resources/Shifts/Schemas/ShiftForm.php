<?php

namespace App\Filament\Resources\Shifts\Schemas;

use App\Models\Shift;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class ShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Shift')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('company_id')
                            ->label('Perusahaan')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Select::make('code')
                            ->label('Jenis shift')
                            ->options(array_combine(Shift::TYPES, Shift::TYPES))
                            ->native(false)
                            ->helperText('Jam masuk & pulang terisi otomatis sesuai jenis (Pagi 08:00–14:00, Siang 14:00–20:00, Malam 20:00–08:00), masih bisa diubah. Satu perusahaan hanya punya satu shift per jenis.')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($times = Shift::DEFAULT_TIMES[$state] ?? null) {
                                    $set('start_time', $times['start']);
                                    $set('end_time', $times['end']);
                                }
                            })
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, $get) => $rule->where('company_id', $get('company_id')),
                            )
                            ->validationMessages([
                                'unique' => 'Perusahaan ini sudah punya shift dengan jenis yang sama. Silakan ubah shift yang sudah ada.',
                            ]),
                    ]),

                Section::make('Jam Kerja')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TimePicker::make('start_time')
                            ->label('Jam masuk')
                            ->seconds(false)
                            ->required(),
                        // Jam pulang lebih awal dari jam masuk = pulang keesokan hari (shift malam).
                        TimePicker::make('end_time')
                            ->label('Jam pulang')
                            ->seconds(false)
                            ->different('start_time')
                            ->helperText('Bila lebih awal dari jam masuk (mis. Malam 20:00–08:00), dianggap pulang keesokan hari.')
                            ->validationMessages([
                                'different' => 'Jam pulang tidak boleh sama dengan jam masuk.',
                            ])
                            ->required(),
                        TextInput::make('break_minutes')
                            ->label('Istirahat (menit)')
                            ->helperText('Dipotong dari durasi kerja.')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(600)
                            ->required(),
                    ]),

                Section::make('Toleransi & Jendela Absen')
                    ->description('Toleransi menentukan kapan dihitung telat / pulang cepat. Jendela absen menentukan rentang tap sidik jari yang dicocokkan ke shift ini.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('late_tolerance_minutes')
                            ->label('Toleransi telat (menit)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(600)
                            ->required(),
                        TextInput::make('early_leave_tolerance_minutes')
                            ->label('Toleransi pulang cepat (menit)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(600)
                            ->required(),
                        TextInput::make('checkin_buffer_minutes')
                            ->label('Jendela tap masuk sebelum jam masuk (menit)')
                            ->helperText('Mis. 120 = tap mulai 2 jam sebelum jam masuk dianggap tap masuk shift ini.')
                            ->numeric()
                            ->default(120)
                            ->minValue(0)
                            ->maxValue(720)
                            ->required(),
                        TextInput::make('checkout_buffer_minutes')
                            ->label('Jendela tap pulang setelah jam pulang (menit)')
                            ->helperText('Mis. 240 = tap sampai 4 jam setelah jam pulang masih dianggap tap pulang shift ini.')
                            ->numeric()
                            ->default(240)
                            ->minValue(0)
                            ->maxValue(720)
                            ->required(),
                    ]),
            ]);
    }
}
