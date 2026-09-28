<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menu yang sudah ada sebelum kolom `menus.template` ada menyimpan outlet
     * null, sehingga sistem tidak tahu menu tersebut milik outlet mana: menu
     * tidak muncul di kasir/generate outlet yang benar dan bisa lolos saat
     * transaksi dibuat. Turunkan outlet dari kategori menu (setiap kategori
     * berasal dari satu file sumber, lihat Category::TEMPLATE_MAP).
     *
     * Hanya baris yang masih null yang diisi; outlet yang sudah diisi (misalnya
     * sudah diperbaui lewat menu manager) tidak disentuh.
     */
    public function up(): void
    {
        $categories = DB::table('categories')->get(['id', 'name']);

        foreach ($categories as $category) {
            $template = Category::templateFor($category->name);

            if ($template === null) {
                continue;
            }

            DB::table('menus')
                ->whereNull('template')
                ->where('category_id', $category->id)
                ->update(['template' => $template->value]);
        }
    }

    public function down(): void
    {
        // Migrasi data: tidak ada struktur skema yang berubah.
    }
};
