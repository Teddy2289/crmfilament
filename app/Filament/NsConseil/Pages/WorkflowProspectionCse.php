<?php

namespace App\Filament\NsConseil\Pages;

use App\Models\WorkflowGroupe;
use App\Models\WorkflowStep;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WorkflowProspectionCse extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Parcours prospection CSE';

    protected static ?string $title = 'Parcours prospection CSE';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.ns-conseil.pages.workflow-prospection-cse';

    protected static ?string $slug = 'workflow-prospection-cse';

    public ?WorkflowGroupe $selectedGroupe = null;

    public Collection $workflowSteps;

    public ?int $selectedWorkflowGroupeId = null;

    public function mount(): void
    {
        $this->loadWorkflow();
    }

    public function updatedSelectedWorkflowGroupeId(): void
    {
        $this->loadWorkflow();
    }

    public function loadWorkflow(): void
    {
        if ($this->selectedWorkflowGroupeId) {
            $this->selectedGroupe = WorkflowGroupe::find($this->selectedWorkflowGroupeId);
        } else {
            $this->selectedGroupe = WorkflowGroupe::where('model_type', 'prospect')
                ->where('actif', true)
                ->orderBy('ordre')
                ->first();

            if ($this->selectedGroupe) {
                $this->selectedWorkflowGroupeId = $this->selectedGroupe->id;
            }
        }

        $this->workflowSteps = $this->selectedGroupe
            ? $this->selectedGroupe->workflowSteps()
                ->whereNull('parent_step_id')
                ->with(['childSteps' => fn ($q) => $q->orderBy('ordre')])
                ->orderBy('ordre')
                ->get()
            : collect();
    }

    public function toggleStepActif(int $stepId): void
    {
        $step = WorkflowStep::find($stepId);
        if ($step) {
            $step->update(['actif' => ! $step->actif]);
            $this->loadWorkflow();
            Notification::make()
                ->title($step->actif ? 'Étape activée' : 'Étape désactivée')
                ->success()
                ->send();
        }
    }

    public function getWorkflowGroupesProperty(): Collection
    {
        return WorkflowGroupe::where('model_type', 'prospect')
            ->where('actif', true)
            ->orderBy('ordre')
            ->get();
    }

    public function getStepTypesProperty(): array
    {
        return WorkflowStep::TYPES;
    }

    // ── Couleurs : on renvoie uniquement des clés natives Filament ────────
    // (success / danger / warning / info / gray / primary), jamais de
    // classes Tailwind écrites à la main. Ces couleurs sont déjà stylées
    // par le CSS compilé de Filament, donc aucun souci de @source/purge.

    public function getStepTypeColor(string $type): string
    {
        return match ($type) {
            'task' => 'info',
            'condition' => 'warning',
            'action' => 'success',
            'notification' => 'primary',
            'approval' => 'danger',
            default => 'gray',
        };
    }

    // Déduit une couleur de branche à partir du condition_label
    // (le modèle ne stocke pas de clé "yes"/"no" explicite)
    public function branchKeyFromCondition(?string $conditionLabel): string
    {
        $label = mb_strtolower((string) $conditionLabel);

        return match (true) {
            str_contains($label, '✓') || str_contains($label, 'oui') || str_contains($label, 'accepté') || str_contains($label, 'joint') => 'success',
            str_contains($label, '✗') || str_contains($label, 'non') || str_contains($label, 'aucun') => 'danger',
            str_contains($label, '⏱') || str_contains($label, 'rappel') || str_contains($label, 'créneau') => 'warning',
            default => 'info',
        };
    }

    public function getTagColor(string $tag): string
    {
        return match ($tag) {
            'STD_NR' => 'warning',
            'CSE_NR' => 'primary',
            'RP' => 'info',
            'RPC' => 'success',
            'KO' => 'danger',
            'QF' => 'info',
            default => 'gray',
        };
    }

    public function getBranchesGridClass(int $count): string
    {
        return match (true) {
            $count === 1 => 'grid-cols-1',
            $count === 2 => 'grid-cols-2',
            $count >= 3 => 'grid-cols-3',
            default => 'grid-cols-1',
        };
    }

    // ─────────────────────────────────────────────────────────────────
    // ACTION : Modifier une étape (case ou branche) via modal
    // ─────────────────────────────────────────────────────────────────
    public function editStepAction(): Action
    {
        return Action::make('editStep')
            ->label('')
            ->icon('heroicon-o-pencil')
            ->iconButton()
            ->color('primary')
            ->tooltip('Modifier cette étape')
            ->modalHeading(fn (array $arguments): string => 'Modifier : '.(WorkflowStep::find($arguments['step'])?->label ?? ''))
            ->modalWidth('2xl')
            ->fillForm(function (array $arguments): array {
                $step = WorkflowStep::with('childSteps')->findOrFail($arguments['step']);

                return [
                    'label' => $step->label,
                    'condition_label' => $step->condition_label,
                    'type' => $step->type,
                    'ordre' => $step->ordre,
                    'actif' => $step->actif,
                    'description' => $step->config['description'] ?? null,
                    'tag' => $step->config['tag'] ?? null,
                    'branches' => $step->childSteps->map(fn (WorkflowStep $child) => [
                        'id' => $child->id,
                        'label' => $child->label,
                        'condition_label' => $child->condition_label,
                        'description' => $child->config['description'] ?? null,
                        'tag' => $child->config['tag'] ?? null,
                        'actif' => $child->actif,
                    ])->toArray(),
                ];
            })
            ->form([
                TextInput::make('label')
                    ->label('Titre de l\'étape')
                    ->required()
                    ->maxLength(255),

                TextInput::make('condition_label')
                    ->label('Label de condition')
                    ->helperText('Ex: "✓ Oui", "⏱ Rappel" — laissez vide si ce n\'est pas une branche'),

                Select::make('type')
                    ->label('Type')
                    ->options(WorkflowStep::TYPES)
                    ->required()
                    ->native(false),

                TextInput::make('ordre')
                    ->label('Ordre')
                    ->numeric()
                    ->required(),

                Toggle::make('actif')
                    ->label('Étape active')
                    ->inline(false),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('tag')
                    ->label('Tag Ringover')
                    ->helperText('Ex: RDV, CSE-NI, BLOC…')
                    ->columnSpanFull(),

                Repeater::make('branches')
                    ->label('Branches / issues possibles')
                    ->schema([
                        TextInput::make('id')->hidden(),
                        TextInput::make('label')
                            ->label('Titre de la branche')
                            ->required(),
                        TextInput::make('condition_label')
                            ->label('Label condition')
                            ->helperText('Ex: "✓ Oui", "✗ Non"'),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('tag')
                            ->label('Tag Ringover'),
                        Toggle::make('actif')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2)
                    ->addActionLabel('Ajouter une branche')
                    ->reorderable(false)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Nouvelle branche')
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, array $arguments): void {
                $step = WorkflowStep::findOrFail($arguments['step']);

                $step->update([
                    'label' => $data['label'],
                    'condition_label' => $data['condition_label'] ?: null,
                    'type' => $data['type'],
                    'ordre' => $data['ordre'],
                    'actif' => $data['actif'],
                    'config' => array_filter([
                        ...($step->config ?? []),
                        'description' => $data['description'] ?: null,
                        'tag' => $data['tag'] ?: null,
                    ], fn ($v) => $v !== null),
                ]);

                $submittedIds = collect($data['branches'] ?? [])
                    ->pluck('id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->toArray();

                WorkflowStep::where('parent_step_id', $step->id)
                    ->whereNotIn('id', $submittedIds ?: [0])
                    ->delete();

                foreach (($data['branches'] ?? []) as $branch) {
                    $isExisting = ! empty($branch['id']);

                    $payload = [
                        'workflow_groupe_id' => $step->workflow_groupe_id,
                        'parent_step_id' => $step->id,
                        'label' => $branch['label'],
                        'condition_label' => $branch['condition_label'] ?: null,
                        'type' => 'action',
                        'actif' => $branch['actif'] ?? true,
                        'config' => array_filter([
                            'description' => $branch['description'] ?: null,
                            'tag' => $branch['tag'] ?: null,
                        ], fn ($v) => $v !== null),
                    ];

                    if ($isExisting) {
                        WorkflowStep::where('id', $branch['id'])->update($payload);
                    } else {
                        $payload['code'] = 'branch_'.Str::slug($branch['label']).'_'.uniqid();
                        $payload['ordre'] = (WorkflowStep::max('ordre') ?? 0) + 1;
                        WorkflowStep::create($payload);
                    }
                }

                $this->loadWorkflow();

                Notification::make()
                    ->title('Étape mise à jour')
                    ->success()
                    ->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('gerer_workflows')
                ->label('Gérer les parcours')
                ->icon('heroicon-o-cog')
                ->color('primary')
                ->url('/super-admin/workflow-groupes')
                ->openUrlInNewTab(),

            \Filament\Actions\Action::make('voir_original')
                ->label('Voir l\'original')
                ->icon('heroicon-o-document')
                ->color('success')
                ->url(asset('docs/aopiacrm/Workflow_prospection_CSE_v2.html'))
                ->openUrlInNewTab(),
        ];
    }
}