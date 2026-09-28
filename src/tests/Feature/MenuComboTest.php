<?php

use App\Models\Category;
use App\Models\Menu;

function mixedTemplateMenus(): Category
{
    $category = Category::factory()->create();

    Menu::factory()->create(['name' => 'Foodcourt Item', 'price' => 10000, 'category_id' => $category->id, 'template' => 'FOODCOURT']);
    Menu::factory()->create(['name' => 'Cafe Item', 'price' => 10000, 'category_id' => $category->id, 'template' => 'CAFE_1912']);
    Menu::factory()->create(['name' => 'Pastry Item', 'price' => 10000, 'category_id' => $category->id, 'template' => 'PASTRY_BAKERY']);
    Menu::factory()->create(['name' => 'Tanpa Template', 'price' => 10000, 'category_id' => $category->id, 'template' => null]);

    return $category;
}

it('generates combos only from menus of the selected template', function () {
    mixedTemplateMenus();

    $items = $this->postJson('/menu-combos/generate', [
        'target' => 50000,
        'template' => 'FOODCOURT',
        'max_qty_per_item' => 10,
    ])
        ->assertSuccessful()
        ->json('data.items');

    expect(collect($items)->pluck('name')->unique()->values()->all())->toBe(['Foodcourt Item']);
});

it('lists menus filtered by template', function () {
    mixedTemplateMenus();

    $names = $this->getJson('/menus?template=CAFE_1912')
        ->assertSuccessful()
        ->json('data.*.name');

    expect($names)->toBe(['Cafe Item']);
});
