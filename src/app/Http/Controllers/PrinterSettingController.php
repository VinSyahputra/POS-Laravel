<?php

namespace App\Http\Controllers;

use App\Models\PrinterSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrinterSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $setting = PrinterSetting::query()->first();

        if (! $setting) {
            $setting = PrinterSetting::create([
                'printer_name' => null,
                'paper_width' => '80mm',
            ]);
        }

        return response()->json(['data' => $setting]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'printer_name' => ['nullable', 'string', 'max:100'],
            'paper_width' => ['required', 'in:58mm,80mm'],
        ]);

        $setting = PrinterSetting::query()->first();

        if (! $setting) {
            $setting = PrinterSetting::create($validated);
        } else {
            $setting->update($validated);
        }

        return response()->json(['data' => $setting->refresh()]);
    }
}
