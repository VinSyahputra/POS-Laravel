<?php

namespace App\Services;

use App\Enums\Template;
use App\Models\Menu;

class MenuBudgetComposerService
{
    private const UNIT = 500;

    private const TOLERANCE_RUPIAH = 10000;

    public function compose(int $targetRupiah, Template $template, int $maxQtyPerItem = 10): array
    {
        $menus = Menu::query()
            ->where('template', $template->value)
            ->get(['id', 'name', 'price'])
            ->values();

        if ($menus->isEmpty()) {
            return $this->failure();
        }

        $targetUnit = intdiv($targetRupiah, self::UNIT);
        $toleranceUnit = intdiv(self::TOLERANCE_RUPIAH, self::UNIT);
        $minUnit = max(0, $targetUnit - $toleranceUnit);

        if ($targetUnit <= 0) {
            return $this->failure();
        }

        $reachable = array_fill(0, $targetUnit + 1, false);
        $reachable[0] = true;
        $parentItem = array_fill(0, $targetUnit + 1, null);
        $parentUnit = array_fill(0, $targetUnit + 1, null);

        foreach ($menus as $menu) {
            $weight = intdiv($menu->price, self::UNIT);

            if ($weight <= 0 || $weight > $targetUnit) {
                continue;
            }

            for ($rep = 0; $rep < $maxQtyPerItem; $rep++) {
                for ($u = $targetUnit; $u >= $weight; $u--) {
                    if ($reachable[$u] || ! $reachable[$u - $weight]) {
                        continue;
                    }

                    $reachable[$u] = true;
                    $parentItem[$u] = $menu->id;
                    $parentUnit[$u] = $u - $weight;
                }
            }
        }

        $bestUnit = null;
        $searchFloor = max(1, $minUnit);

        for ($u = $targetUnit; $u >= $searchFloor; $u--) {
            if ($reachable[$u]) {
                $bestUnit = $u;
                break;
            }
        }

        if ($bestUnit === null) {
            return $this->failure();
        }

        $qtyByMenuId = [];
        $cursor = $bestUnit;

        while ($cursor > 0) {
            $menuId = $parentItem[$cursor];
            $qtyByMenuId[$menuId] = ($qtyByMenuId[$menuId] ?? 0) + 1;
            $cursor = $parentUnit[$cursor];
        }

        $menusById = $menus->keyBy('id');
        $items = [];
        $subtotal = 0;

        foreach ($qtyByMenuId as $menuId => $qty) {
            $menu = $menusById[$menuId];
            $itemSubtotal = $menu->price * $qty;
            $subtotal += $itemSubtotal;

            $items[] = [
                'menu_id' => $menu->id,
                'name' => $menu->name,
                'price' => $menu->price,
                'qty' => $qty,
                'subtotal' => $itemSubtotal,
            ];
        }

        return [
            'success' => true,
            'items' => $items,
            'subtotal' => $subtotal,
            'target' => $targetRupiah,
            'difference' => $targetRupiah - $subtotal,
        ];
    }

    private function failure(): array
    {
        return [
            'success' => false,
            'message' => 'Tidak ditemukan kombinasi menu yang mendekati budget dengan batas qty saat ini. Coba naikkan batas qty per item lalu Generate ulang.',
        ];
    }
}
