<?php

namespace App\Http\Controllers;

use App\Enums\Template;
use App\Http\Requests\GenerateMenuComboRequest;
use App\Services\MenuBudgetComposerService;
use Illuminate\Http\JsonResponse;

class MenuComboController extends Controller
{
    public function generate(GenerateMenuComboRequest $request, MenuBudgetComposerService $composer): JsonResponse
    {
        $result = $composer->compose(
            $request->integer('target'),
            $request->enum('template', Template::class),
            $request->maxQtyPerItem(),
        );

        if (! $result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json(['data' => $result]);
    }
}
