<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->withCount('menus')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return response()->json(['data' => $category], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return response()->json(['data' => $category->refresh()]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->menus()->exists()) {
            return response()->json([
                'message' => "Kategori \"{$category->name}\" masih dipakai oleh menu dan tidak bisa dihapus.",
            ], 409);
        }

        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }
}
