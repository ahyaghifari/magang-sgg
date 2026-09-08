<?php

namespace App\Filament\Resources\Journals\Schemas;

use App\Models\JournalAttachment;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JournalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rincian Jurnal')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('intern.nama')
                            ->label('Intern'),
                        TextEntry::make('date')
                            ->label('Tanggal')
                            ->date('l, d F Y'),
                        TextEntry::make('activity')
                            ->label('Kegiatan')
                            ->columnSpanFull()
                            ->prose(),
                    ]),

                Section::make('Lampiran Kegiatan')
                    ->schema([
                        TextEntry::make('no_attachments')
                            ->hiddenLabel()
                            ->state('Jurnal ini tidak memiliki lampiran.')
                            ->color('gray')
                            ->visible(fn ($record): bool => $record->attachments->isEmpty()),
                        RepeatableEntry::make('attachments')
                            ->hiddenLabel()
                            ->columns(2)
                            ->visible(fn ($record): bool => $record->attachments->isNotEmpty())
                            ->schema([
                                TextEntry::make('type')
                                    ->label('Jenis')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'photo' => 'Foto',
                                        'document' => 'Dokumen (PDF)',
                                        'link' => 'Link',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'photo' => 'info',
                                        'document' => 'danger',
                                        'link' => 'success',
                                        default => 'gray',
                                    }),
                                TextEntry::make('label')
                                    ->label('Keterangan')
                                    ->placeholder('—'),
                                ImageEntry::make('path')
                                    ->label('Berkas foto')
                                    ->state(fn (JournalAttachment $record): ?string => $record->path ? url('storage/' . $record->path) : null)
                                    ->height(180)
                                    ->columnSpanFull()
                                    ->visible(fn (JournalAttachment $record): bool => $record->type === 'photo'),
                                TextEntry::make('document_link')
                                    ->label('Dokumen')
                                    ->state('Buka PDF di tab baru')
                                    ->icon('heroicon-o-document-arrow-down')
                                    ->url(fn (JournalAttachment $record): ?string => $record->path ? url('storage/' . $record->path) : null)
                                    ->openUrlInNewTab()
                                    ->columnSpanFull()
                                    ->visible(fn (JournalAttachment $record): bool => $record->type === 'document'),
                                TextEntry::make('url')
                                    ->label('Tautan')
                                    ->icon('heroicon-o-link')
                                    ->url(fn (JournalAttachment $record): ?string => $record->url)
                                    ->openUrlInNewTab()
                                    ->columnSpanFull()
                                    ->visible(fn (JournalAttachment $record): bool => $record->type === 'link'),
                            ]),
                    ]),
            ]);
    }
}
