<?php

namespace App\Filament\Resources\Interns\Schemas;

use App\Enums\UserRole;
use App\Models\Intern;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InternForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Pengguna')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('institusi_id')
                    ->label('Institusi (asal sekolah/kampus)')
                    ->relationship('institusi', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('unit_id')
                    ->label('Unit penempatan')
                    ->relationship('unit', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->company->name} — {$record->name}")
                    ->searchable()
                    ->preload()
                    ->placeholder('Belum ditempatkan'),
                Select::make('pembimbing_id')
                    ->label('Pembimbing')
                    ->relationship(
                        'pembimbing',
                        'name',
                        fn ($query) => $query->where('role', UserRole::Pembimbing),
                    )
                    ->searchable()
                    ->preload()
                    ->placeholder('Belum ditugaskan')
                    ->helperText('Pembimbing yang mendampingi intern ini secara khusus — satu pembimbing bisa memegang lebih dari satu intern.'),
                Select::make('mentor_id')
                    ->label('Mentor')
                    ->relationship(
                        'mentor',
                        'name',
                        fn ($query) => $query->where('role', UserRole::Mentor),
                    )
                    ->searchable()
                    ->preload()
                    ->placeholder('Belum ditugaskan')
                    ->helperText('Mentor yang mendampingi intern ini secara khusus — satu mentor bisa memegang lebih dari satu intern.'),
                TextInput::make('nama')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nama_panggilan')
                    ->label('Nama Panggilan')
                    ->maxLength(255),
                TextInput::make('nip')
                    ->label('NIP')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Nomor Induk Pegawai — opsional, diisi manual oleh admin.'),
                Select::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ])
                    ->native(false)
                    ->required(),
                DatePicker::make('tanggal_mulai')
                    ->label('Magang Mulai')
                    ->native(false),
                DatePicker::make('tanggal_selesai')
                    ->label('Magang Selesai')
                    ->native(false)
                    ->afterOrEqual('tanggal_mulai'),
                Section::make('Penilaian — ATTITUDE')
                    ->columns(2)
                    ->schema(
                        collect(Intern::CRITERIA)
                            ->filter(fn ($c) => $c['category'] === 'ATTITUDE')
                            ->map(fn ($c, $field) => TextInput::make($field)
                                ->label($c['title'])
                                ->helperText($c['description'])
                                ->numeric()->integer()->minValue(Intern::SCALE_MIN)->maxValue(Intern::SCALE_MAX)->step(1)->placeholder('1-5'))
                            ->values()
                            ->all(),
                    ),
                Section::make('Penilaian — KNOWLEDGE & SKILL')
                    ->columns(2)
                    ->schema(
                        collect(Intern::CRITERIA)
                            ->filter(fn ($c) => $c['category'] === 'KNOWLEDGE & SKILL')
                            ->map(fn ($c, $field) => TextInput::make($field)
                                ->label($c['title'])
                                ->helperText($c['description'])
                                ->numeric()->integer()->minValue(Intern::SCALE_MIN)->maxValue(Intern::SCALE_MAX)->step(1)->placeholder('1-5'))
                            ->values()
                            ->all(),
                    ),
                Textarea::make('catatan_penilaian')
                    ->label('Catatan Penilaian')
                    ->rows(3)
                    ->maxLength(1000)
                    ->columnSpanFull()
                    ->helperText('Nilai Akhir & Rating dihitung otomatis dari rata-rata 10 kriteria di atas (≥3.50 Excellent, ≥3.00 Good, ≥2.50 Fair, ≥1.50 Below Average, di bawahnya Poor). Ditampilkan di form penilaian PKL.'),
            ]);
    }
}
