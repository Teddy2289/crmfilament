<?php

namespace App\Services;

class DragDropService
{
    protected array $configurableResources = [];

    public function __construct()
    {
        $this->registerDefaultResources();
    }

    protected function registerDefaultResources(): void
    {
        $this->configurableResources = [
            'prospects' => [
                'label' => 'Prospects',
                'model' => \App\Models\Prospect::class,
                'sortable' => true,
                'groupable' => ['statut', 'secteur', 'difficile'],
            ],
            'clients' => [
                'label' => 'Clients',
                'model' => \App\Models\Client::class,
                'sortable' => true,
                'groupable' => ['etat', 'type'],
            ],
            'partenaires' => [
                'label' => 'Partenaires',
                'model' => \App\Models\Partenaire::class,
                'sortable' => true,
                'groupable' => ['type', 'statut'],
            ],
            'tasks' => [
                'label' => 'Tâches',
                'model' => \App\Models\Task::class,
                'sortable' => true,
                'groupable' => ['statut', 'type'],
            ],
            'gantt_tasks' => [
                'label' => 'Tâches Gantt',
                'model' => \App\Models\GanttTask::class,
                'sortable' => true,
                'groupable' => ['status', 'milestone'],
            ],
        ];
    }

    public function getConfigurableResources(): array
    {
        return $this->configurableResources;
    }

    public function isResourceConfigurable(string $resource): bool
    {
        return isset($this->configurableResources[$resource]);
    }

    public function getResourceConfig(string $resource): ?array
    {
        return $this->configurableResources[$resource] ?? null;
    }

    public function registerResource(string $key, array $config): void
    {
        $this->configurableResources[$key] = $config;
    }

    public function getDragDropConfig(string $resource): array
    {
        $config = $this->getResourceConfig($resource);
        
        if (!$config) {
            return [];
        }

        return [
            'resource' => $resource,
            'label' => $config['label'],
            'sortable' => $config['sortable'] ?? false,
            'groupable' => $config['groupable'] ?? [],
            'groups' => $this->getGroupOptions($resource),
        ];
    }

    protected function getGroupOptions(string $resource): array
    {
        $config = $this->getResourceConfig($resource);
        
        if (!$config || empty($config['groupable'])) {
            return [];
        }

        $groups = [];
        foreach ($config['groupable'] as $field) {
            $groups[$field] = $this->getFieldOptions($config['model'], $field);
        }

        return $groups;
    }

    protected function getFieldOptions(string $model, string $field): array
    {
        // This would typically query the database to get unique values
        // For now, return empty array - can be customized per resource
        return [];
    }

    public function handleReorder(string $resource, array $orderedIds): bool
    {
        $config = $this->getResourceConfig($resource);
        
        if (!$config || !$config['sortable']) {
            return false;
        }

        $model = $config['model'];
        
        foreach ($orderedIds as $index => $id) {
            $model::where('id', $id)->update(['order' => $index]);
        }

        return true;
    }

    public function handleMoveToGroup(string $resource, int $id, string $groupField, string $groupValue): bool
    {
        $config = $this->getResourceConfig($resource);
        
        if (!$config || !in_array($groupField, $config['groupable'] ?? [])) {
            return false;
        }

        $model = $config['model'];
        
        return $model::where('id', $id)->update([$groupField => $groupValue]);
    }
}
