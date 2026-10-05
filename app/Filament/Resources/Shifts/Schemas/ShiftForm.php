<?php

namespace App\Filament\Resources\Shifts\Schemas;

use App\Models\Intern;
use App\Models\Shift;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
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
                            // Ganti perusahaan → pilihan intern dikosongkan (intern harus dari perusahaan itu).
                            ->afterStateUpdated(fn ($set) => $set('interns', []))
                            ->required(),
                        Select::make('code')
                            ->label('Jenis shift')
                            ->options(array_combine(Shift::TYPES, Shift::TYPES))
                            ->native(false)
                            ->helperText('Jam masuk & pulang terisi otomatis sesuai jenis (Pagi 08:30–16:30, Siang 12:00–21:00), masih bisa diubah. Satu perusahaan hanya punya satu shift per jenis.')
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
                        Select::make('interns')
                            ->label('Intern yang memakai shift ini')
                            ->helperText('Daftar intern mengikuti perusahaan yang dipilih. Intern yang sudah terdaftar di shift lain tidak ditampilkan — lepaskan dulu dari shift itu bila ingin memindahkannya.')
                            ->relationship(
                                'interns',
                                'nama',
                                modifyQueryUsing: fn (Builder $query, $get, ?Shift $record) => $query
                                    ->with('unit')
                                    ->whereHas('unit', fn (Builder $q) => $q->where('company_id', $get('company_id')))
                                    // Intern yang sudah terdaftar di shift LAIN disembunyikan — tiap intern
                                    // cukup satu shift terdaftar. Intern milik shift ini sendiri tetap tampil.
                                    ->where(fn (Builder $q) => $q
                                        ->whereDoesntHave('shifts', fn (Builder $s) => $s->where('shifts.id', '!=', $record?->id ?? 0))
                                        ->when($record, fn (Builder $q) => $q->orWhereHas('shifts', fn (Builder $s) => $s->whereKey($record->id))))
                                    ->orderBy('nama'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Intern $record) => $record->nama . ($record->unit ? ' — ' . $record->unit->name : ''))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($get) => blank($get('company_id')))
                            ->placeholder(fn ($get) => blank($get('company_id')) ? 'Pilih perusahaan dulu' : 'Pilih intern...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Jam Kerja')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TimePicker::make('start_time')
                            ->label('Jam masuk')
                            ->seconds(false)
                            ->required(),
                        // Shift malam/lintas hari tidak didukung → jam pulang wajib setelah jam masuk.
                        TimePicker::make('end_time')
                            ->label('Jam pulang')
                            ->seconds(false)
                            ->after('start_time')
                            ->validationMessages([
                                'after' => 'Jam pulang harus setelah jam masuk (shift malam tidak didukung).',
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
