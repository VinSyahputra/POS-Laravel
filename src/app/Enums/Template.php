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
}
