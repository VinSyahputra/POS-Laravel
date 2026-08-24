<?php

namespace App\Enums;

enum Template: string
{
    case PastryBakery = 'PASTRY_BAKERY';
    case Foodcourt = 'FOODCOURT';
    case Cafe1912 = 'CAFE_1912';

    public function label(): string
    {
        return match ($this) {
            self::PastryBakery => 'Pastry & Bakery',
            self::Foodcourt => 'Foodcourt',
            self::Cafe1912 => 'Cafe 1912',
        };
    }

    public function code(): string
    {
        return match ($this) {
            self::Foodcourt => 'UMB0101',
            self::PastryBakery => 'UMB0201',
            self::Cafe1912 => 'UMB0301',
        };
    }
}
