<?php

namespace App\Http\Controllers;

use App\Models\UserView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    private const RESOURCES = [
        'campagne-phonings',
        'prospects',
    ];

    private const VIEW_NAME = '__ui_preferences__';

    public function show(Request $request): JsonResponse
    {
        $resource = $this->resource($request);
        $view = UserView::query()
            ->where('user_id', $request->user()->id)
            ->where('resource', $resource)
            ->where('name', self::VIEW_NAME)
            ->first();

        return response()->json([
            'resource' => $resource,
            'preferences' => $view?->config['preferences'] ?? [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $resource = $this->resource($request);
        $validated = $request->validate([
            'preferences' => ['required', 'array', 'max:50'],
        ]);

        $preferences = collect($validated['preferences'])
            ->filter(fn ($value) => is_scalar($value) || is_null($value))
            ->map(fn ($value) => is_null($value) ? null : (string) $value)
            ->all();

        $view = UserView::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'resource' => $resource,
                'name' => self::VIEW_NAME,
            ],
            [
                'type' => 'preferences',
                'config' => ['preferences' => $preferences],
                'is_default' => false,
            ],
        );

        return response()->json([
            'resource' => $resource,
            'preferences' => $view->config['preferences'] ?? [],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $resource = $this->resource($request);

        UserView::query()
            ->where('user_id', $request->user()->id)
            ->where('resource', $resource)
            ->where('name', self::VIEW_NAME)
            ->delete();

        return response()->json([
            'resource' => $resource,
            'preferences' => [],
        ]);
    }

    private function resource(Request $request): string
    {
        $resource = (string) $request->route('resource');

        abort_unless(in_array($resource, self::RESOURCES, true), 404);

        return $resource;
    }
}
