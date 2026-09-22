<?php

namespace App\Filament\Resources\Interns\Tables;

use App\Models\Intern;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InternsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama_panggilan')
                    ->label('Nama Panggilan')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'L' => 'info',
                        'P' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('institusi.name')
                    ->label('Institusi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->badge()
                    ->placeholder('Belum ditempatkan')
                    ->sortable(),
                TextColumn::make('pembimbing.name')
                    ->label('Pembimbing')
                    ->placeholder('Belum ditugaskan')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('mentor.name')
                    ->label('Mentor')
                    ->placeholder('Belum ditugaskan')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('tanggal_mulai')
                    ->label('Magang Mulai')
                    ->date()
                    ->placeholder('—')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('tanggal_selesai')
                    ->label('Magang Selesai')
                    ->date()
                    ->placeholder('—')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('nilai_akhir')
                    ->label('Nilai Akhir')
                    ->placeholder('—')
                    ->sortable()
                    ->description(fn ($record): ?string => $record->predikat()),
                ...collect(Intern::CRITERIA)
                    ->map(fn ($c, $field) => TextColumn::make($field)
                        ->label($c['title'])
                        ->placeholder('—')
                        ->toggleable(isToggledHiddenByDefault: true))
                    ->values()
                    ->all(),
                TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('viewCertificate')
                    ->label('Lihat Sertifikat')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn ($record): string => route('interns.certificate.view', $record))
                    ->openUrlInNewTab(),
                Action::make('certificate')
                    ->label('Download')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn ($record): string => route('interns.certificate', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
