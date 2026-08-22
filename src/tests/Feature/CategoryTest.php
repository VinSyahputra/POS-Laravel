<?php

use App\Models\Category;
use App\Models\Menu;

it('lists categories with menu count', function () {
    $category = Category::factory()->create(['name' => 'Minuman']);
    Menu::factory()->count(3)->create(['category_id' => $category->id]);

    $this->getJson('/categories')
        ->assertSuccessful()
        ->assertJsonPath('data.0.name', 'Minuman')
        ->assertJsonPath('data.0.menus_count', 3);
});

it('creates a category', function () {
    $this->postJson('/categories', ['name' => 'Makanan'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Makanan');

    expect(Category::where('name', 'Makanan')->exists())->toBeTrue();
});

it('rejects a duplicate category name', function () {
    Category::factory()->create(['name' => 'Snack']);

    $this->postJson('/categories', ['name' => 'Snack'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('renames a category', function () {
    $category = Category::factory()->create(['name' => 'Snak']);

    $this->putJson("/categories/{$category->id}", ['name' => 'Snack'])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Snack');

    expect($category->refresh()->name)->toBe('Snack');
});

it('refuses to delete a category that still has menus', function () {
    $category = Category::factory()->create();
    Menu::factory()->create(['category_id' => $category->id]);

    $this->deleteJson("/categories/{$category->id}")
        ->assertConflict()
        ->assertJsonPath('message', "Kategori \"{$category->name}\" masih dipakai oleh menu dan tidak bisa dihapus.");
});

it('deletes an empty category', function () {
    $category = Category::factory()->create();

    $this->deleteJson("/categories/{$category->id}")->assertSuccessful();

    expect($category->fresh())->toBeNull();
});
