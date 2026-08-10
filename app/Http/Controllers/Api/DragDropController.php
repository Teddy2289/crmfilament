<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DragDropService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DragDropController extends Controller
{
    protected DragDropService $dragDropService;

    public function __construct(DragDropService $dragDropService)
    {
        $this->dragDropService = $dragDropService;
    }

    public function reorder(Request $request, string $resource): JsonResponse
    {
        $request->validate([
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'integer',
        ]);

        $success = $this->dragDropService->handleReorder(
            $resource,
            $request->ordered_ids
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Ordre mis à jour avec succès' : 'Échec de la mise à jour',
        ]);
    }

    public function moveToGroup(Request $request, string $resource): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'group_field' => 'required|string',
            'group_value' => 'required|string',
        ]);

        $success = $this->dragDropService->handleMoveToGroup(
            $resource,
            $request->id,
            $request->group_field,
            $request->group_value
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Déplacement réussi' : 'Échec du déplacement',
        ]);
    }

    public function getConfig(string $resource): JsonResponse
    {
        $config = $this->dragDropService->getDragDropConfig($resource);

        return response()->json([
            'config' => $config,
        ]);
    }
}
