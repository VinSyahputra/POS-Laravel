<?php

namespace App\Models;

use App\Enums\Template;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * Peta kategori -> outlet, diturunkan dari file sumber menu:
     * - MENU CAFE 1912.xlsx (kolom "Sumber": Cafe 1912 - ...)
     * - MENU BOGA.xlsx (seluruh isi file)
     * - MENU PASTERY.xlsx (seluruh isi file)
     *
     * Setiap kategori berasal dari satu file sumber saja (tidak ada kategori
     * yang overlap antar file), jadi pemetaannya deterministik. Dipakai bersama
     * oleh seeder dan backfill kolom `menus.template`.
     *
     * @var array<string, Template>
     */
    public const TEMPLATE_MAP = [
        // MENU CAFE 1912.xlsx
        'Capcay' => Template::Cafe1912,
        'Pasta' => Template::Cafe1912,
        'Rice Bowl' => Template::Cafe1912,
        'Steak' => Template::Cafe1912,
        'Main Course' => Template::Cafe1912,
        'Fried Rice' => Template::Cafe1912,
        'Appetizer' => Template::Cafe1912,
        'Dessert' => Template::Cafe1912,
        'Sandwich' => Template::Cafe1912,
        'Other' => Template::Cafe1912,
        'Milk Blend' => Template::Cafe1912,
        'Milk Base' => Template::Cafe1912,
        'Mocktail' => Template::Cafe1912,
        'Signature' => Template::Cafe1912,
        'Espresso Base' => Template::Cafe1912,
        'Manual Brew' => Template::Cafe1912,
        'Tea Base' => Template::Cafe1912,
        'Coffee Milk' => Template::Cafe1912,
        // MENU BOGA.xlsx
        'Bakso Malang' => Template::Foodcourt,
        'Mie Ayam' => Template::Foodcourt,
        'Indomie Menu' => Template::Foodcourt,
        'Snack' => Template::Foodcourt,
        'Minuman' => Template::Foodcourt,
        'Drink Corner' => Template::Foodcourt,
        // MENU PASTERY.xlsx
        'Pastry' => Template::PastryBakery,
    ];

    protected $fillable = ['name'];

    /**
     * Outlet yang dimiliki kategori tersebut, atau null bila tidak diketahui.
     */
    public static function templateFor(string $categoryName): ?Template
    {
        return self::TEMPLATE_MAP[$categoryName] ?? null;
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }
}
