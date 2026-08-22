<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\PrinterSetting;
use Illuminate\Database\Seeder;

class PosSeeder extends Seeder
{
    public function run(): void
    {
        $makanan = Category::create(['name' => 'Makanan']);
        $minuman = Category::create(['name' => 'Minuman']);
        $snack = Category::create(['name' => 'Snack']);

        $menus = [
            ['name' => 'Nasi Goreng Spesial', 'price' => 25000, 'category_id' => $makanan->id],
            ['name' => 'Mie Ayam Bakso', 'price' => 20000, 'category_id' => $makanan->id],
            ['name' => 'Ayam Geprek', 'price' => 22000, 'category_id' => $makanan->id],
            ['name' => 'Es Teh Manis', 'price' => 5000, 'category_id' => $minuman->id],
            ['name' => 'Es Jeruk', 'price' => 7000, 'category_id' => $minuman->id],
            ['name' => 'Kopi Susu Gula Aren', 'price' => 18000, 'category_id' => $minuman->id],
            ['name' => 'Kentang Goreng', 'price' => 15000, 'category_id' => $snack->id],
            ['name' => 'Pisang Goreng', 'price' => 12000, 'category_id' => $snack->id],
            ['name' => 'Tahu Crispy', 'price' => 10000, 'category_id' => $snack->id],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }

        PrinterSetting::query()->firstOrCreate(
            ['id' => 1],
            ['printer_name' => null, 'paper_width' => '80mm']
        );
    }
}
