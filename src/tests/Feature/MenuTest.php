<?php

use App\Models\Category;
use App\Models\Menu;

it('lists menus with their category', function () {
    $category = Category::factory()->create(['name' => 'Minuman']);
    Menu::factory()->create(['name' => 'Es Jeruk', 'category_id' => $category->id]);

    $this->getJson('/menus')
        ->assertSuccessful()
        ->assertJsonPath('data.0.name', 'Es Jeruk')
        ->assertJsonPath('data.0.category.name', 'Minuman');
});

it('filters menus by category', function () {
    $makanan = Category::factory()->create();
    $minuman = Category::factory()->create();
    Menu::factory()->count(2)->create(['category_id' => $makanan->id]);
    Menu::factory()->create(['category_id' => $minuman->id]);

    $response = $this->getJson("/menus?category_id={$minuman->id}")
        ->assertSuccessful()
        ->json('data');

    expect($response)->toHaveCount(1)
        ->and($response[0]['category_id'])->toBe($minuman->id);
});

it('creates a menu with integer price', function () {
    $category = Category::factory()->create();

    $this->postJson('/menus', [
        'name' => 'Nasi Goreng',
        'price' => 25000,
        'category_id' => $category->id,
    ])->assertCreated();

    $menu = Menu::query()->first();
    expect($menu->name)->toBe('Nasi Goreng')
        ->and($menu->price)->toBeInt()->toBe(25000);
});

it('rejects invalid menu payloads', function (array $payload, array $errors) {
    Category::factory()->create();

    $this->postJson('/menus', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'missing name' => [['name' => null, 'price' => 1000, 'category_id' => 1], ['name']],
    'negative price' => [['name' => 'X', 'price' => -5, 'category_id' => 1], ['price']],
    'non-integer price' => [['name' => 'X', 'price' => 'abc', 'category_id' => 1], ['price']],
    'invalid category' => [['name' => 'X', 'price' => 1000, 'category_id' => 999], ['category_id']],
]);

it('updates a menu', function () {
    $menu = Menu::factory()->create(['price' => 10000]);
    $otherCategory = Category::factory()->create();

    $this->putJson("/menus/{$menu->id}", [
        'name' => $menu->name,
        'price' => 15000,
        'category_id' => $otherCategory->id,
    ])->assertSuccessful();

    expect($menu->refresh()->price)->toBe(15000)
        ->and($menu->category_id)->toBe($otherCategory->id);
});

it('deletes a menu without breaking history', function () {
    $menu = Menu::factory()->create();

    $this->deleteJson("/menus/{$menu->id}")->assertSuccessful();

    expect($menu->fresh())->toBeNull();
});
