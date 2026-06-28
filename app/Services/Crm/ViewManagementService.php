<?php

namespace App\Services\Crm;

use App\Models\UserView;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ViewManagementService
{
    protected string $resource = '';

    public function setResource(string $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function getCurrentView(): string
    {
        return Session::get("view_{$this->resource}", 'list');
    }

    public function setCurrentView(string $view): void
    {
        Session::put("view_{$this->resource}", $view);
    }

    public function getAvailableViews(): array
    {
        $user = Auth::user();
        if (! $user) {
            return [
                'list' => 'Liste',
                'kanban' => 'Kanban',
            ];
        }

        $customViews = UserView::where('user_id', $user->id)
            ->where('resource', $this->resource)
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        return array_merge(
            [
                'list' => 'Liste',
                'kanban' => 'Kanban',
            ],
            $customViews
        );
    }

    public function saveView(array $data): UserView
    {
        $user = Auth::user();
        if (! $user) {
            throw new \Exception('User not authenticated');
        }

        return UserView::updateOrCreate(
            [
                'user_id' => $user->id,
                'resource' => $this->resource,
                'name' => $data['name'],
            ],
            [
                'type' => $data['type'],
                'config' => $data['config'] ?? [],
                'is_default' => $data['is_default'] ?? false,
            ]
        );
    }

    public function deleteView(int $viewId): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return UserView::where('id', $viewId)
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    public function getDefaultView(): ?UserView
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        return UserView::where('user_id', $user->id)
            ->where('resource', $this->resource)
            ->where('is_default', true)
            ->first();
    }

    public function applyDefaultView(): void
    {
        $defaultView = $this->getDefaultView();
        if ($defaultView) {
            $this->setCurrentView($defaultView->type);
        }
    }
}
