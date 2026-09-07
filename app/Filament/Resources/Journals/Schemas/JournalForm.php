<?php

namespace App\Filament\Resources\Journals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class JournalForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        $isAdmin = $user?->isAdmin() ?? false;

        return $schema
            ->components([
                // Hanya admin yang boleh memilih intern.
                // Untuk peserta, intern_id diisi otomatis di CreateJournal / EditJournal.
                Select::make('intern_id')
                    ->label('Intern')
                    ->relationship('intern', 'nama')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible($isAdmin)
                    ->dehydrated($isAdmin),
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->required()
                    ->default(now())
                    ->native(false),
                Textarea::make('activity')
                    ->label('Kegiatan')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),

                // Lampiran kegiatan: bisa banyak, tiap baris pilih jenis (foto / PDF / link).
                Repeater::make('attachments')
                    ->label('Lampiran Kegiatan')
                    ->relationship()
                    ->columnSpanFull()
                    ->addActionLabel('Tambah lampiran')
                    ->defaultItems(0)
                    ->collapsible()
                    ->cloneable()
                    ->itemLabel(fn (array $state): ?string => match ($state['type'] ?? null) {
                        'photo' => 'Foto — ' . ($state['label'] ?: basename((string) ($state['path'] ?? ''))),
                        'document' => 'Dokumen — ' . ($state['label'] ?: basename((string) ($state['path'] ?? ''))),
                        'link' => 'Link — ' . ($state['label'] ?: ($state['url'] ?? '')),
                        default => 'Lampiran baru',
                    })
                    ->schema([
                        Select::make('type')
                            ->label('Jenis')
                            ->options([
                                'photo' => 'Foto',
                                'document' => 'Dokumen (PDF)',
                                'link' => 'Link',
                            ])
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('label')
                            ->label('Keterangan')
                            ->maxLength(255),
                        FileUpload::make('path')
                            ->label(fn (Get $get): string => $get('type') === 'document' ? 'Berkas PDF' : 'Berkas Foto')
                            ->directory('journal-attachments')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->maxSize(5120) // 5 MB
                            ->acceptedFileTypes(fn (Get $get): array => $get('type') === 'document'
                                ? ['application/pdf']
                                : ['image/jpeg', 'image/png', 'image/webp'])
                            ->image(fn (Get $get): bool => $get('type') === 'photo')
                            ->visible(fn (Get $get): bool => in_array($get('type'), ['photo', 'document'], true))
                            ->required(fn (Get $get): bool => in_array($get('type'), ['photo', 'document'], true))
                            ->columnSpanFull(),
                        TextInput::make('url')
                            ->label('URL / Tautan')
                            ->url()
                            ->maxLength(2048)
                            ->prefixIcon('heroicon-o-link')
                            ->visible(fn (Get $get): bool => $get('type') === 'link')
                            ->required(fn (Get $get): bool => $get('type') === 'link')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
