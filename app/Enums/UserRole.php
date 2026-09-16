<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Intern = 'intern';
    case Pembimbing = 'pembimbing';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Intern => 'Intern',
            self::Pembimbing => 'Pembimbing',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Intern => 'info',
            self::Pembimbing => 'success',
        };
    }
}
