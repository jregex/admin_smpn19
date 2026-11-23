<?php

namespace App\Enum;

enum Hari: string
{
    case SENIN = 'Senin';
    case SELASA = 'Selasa';
    case RABU = 'Rabu';
    case KAMIS = 'Kamis';
    case JUMAT = 'Jumat';
    case SABTU = 'Sabtu';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
