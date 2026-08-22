<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $menus = Menu::query()
            ->with('category:id,name')
            ->when(
                $request->filled('category_id'),
                fn ($query) => $query->where('category_id', $request->integer('category_id'))
            )
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $menus]);
    }

    public function store(StoreMenuRequest $request): JsonResponse
    {
        $menu = Menu::create($request->validated());

        return response()->json(['data' => $menu->load('category:id,name')], 201);
    }

    public function update(UpdateMenuRequest $request, Menu $menu): JsonResponse
    {
        $menu->update($request->validated());

        return response()->json(['data' => $menu->refresh()->load('category:id,name')]);
    }

    public function destroy(Menu $menu): JsonResponse
    {
        $menu->delete();

        return response()->json(['message' => 'Menu berhasil dihapus.']);
    }
}
