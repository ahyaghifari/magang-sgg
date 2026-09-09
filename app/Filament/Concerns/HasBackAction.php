<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;

/**
 * Menyediakan tombol "Kembali" ke halaman daftar (index) resource.
 * Dipakai di halaman Create & Edit setiap Filament Resource.
 */
trait HasBackAction
{
    protected function backAction(): Action
    {
        return Action::make('kembali')
            ->label('Kembali')
            ->hiddenLabel()
            ->tooltip('Kembali')
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url($this->getResource()::getUrl('index'));
    }
}
