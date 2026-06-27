<?php

namespace App\Services;

use App\Models\Appel;
use App\Models\StatutPhoning;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RingoverTagService
{
    /**
     * @return array{
     *     tags: list<string>,
     *     department_tag: ?string,
     *     status_tag: ?string,
     *     status_code: ?string,
     *     status_label: ?string,
     *     complete: bool,
     *     missing: list<string>
     * }
     */
    public function analyze(array $call): array
    {
        $tags = $this->extractTags($call);
        $departmentTag = $this->detectDepartmentTag($tags);
        $status = $this->detectStatusTag($tags);
        $missing = [];

        if (! $departmentTag) {
            $missing[] = 'department';
        }

        if (! $status) {
            $missing[] = 'status';
        }

        return [
            'tags' => $tags,
            'department_tag' => $departmentTag,
            'status_tag' => $status['tag'] ?? null,
            'status_code' => $status['code'] ?? null,
            'status_label' => $status['label'] ?? null,
            'complete' => $missing === [],
            'missing' => $missing,
        ];
    }

    /**
     * @return list<string>
     */
    public function extractTags(array $call): array
    {
        $rawTags = [];

        foreach ([
            'tags',
            'tag',
            'tag_list',
            'call_tags',
            'data.tags',
            'data.tag_list',
            'call.tags',
            'resource.tags',
        ] as $path) {
            $this->appendRawTags(data_get($call, $path), $rawTags);
        }

        return collect($rawTags)
            ->map(fn ($tag) => $this->normalizeTagLabel($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function normalizeStatusCode(string $tag): string
    {
        return Str::of($tag)
            ->upper()
            ->replace('+', '_PLUS')
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^A-Z0-9_]+/', '_')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->lower()
            ->toString();
    }

    /**
     * @return array<string, mixed>
     */
    public function diagnostic(): array
    {
        if (! Schema::hasTable('appels') || ! Schema::hasColumn('appels', 'ringover_tag_is_complete')) {
            return [
                'schema_ready' => false,
                'total_calls' => 0,
                'complete_tags' => 0,
                'missing_department' => 0,
                'missing_status' => 0,
                'unmapped_users' => 0,
                'mapped_users' => 0,
                'webhook_calls' => 0,
            ];
        }

        $ringoverCalls = Appel::query()->whereNotNull('ringover_call_id');

        return [
            'schema_ready' => true,
            'total_calls' => (clone $ringoverCalls)->count(),
            'complete_tags' => (clone $ringoverCalls)->where('ringover_tag_is_complete', true)->count(),
            'missing_department' => (clone $ringoverCalls)->whereNull('ringover_department_tag')->count(),
            'missing_status' => (clone $ringoverCalls)->whereNull('ringover_status_tag')->count(),
            'unmapped_users' => (clone $ringoverCalls)
                ->whereNotNull('ringover_user_id')
                ->whereNull('user_id')
                ->distinct('ringover_user_id')
                ->count('ringover_user_id'),
            'mapped_users' => User::query()->whereNotNull('ringover_user_id')->count(),
            'webhook_calls' => (clone $ringoverCalls)->whereNotNull('ringover_webhook_received_at')->count(),
        ];
    }

    /**
     * @param mixed $value
     * @param list<string> $rawTags
     */
    private function appendRawTags(mixed $value, array &$rawTags): void
    {
        if (blank($value)) {
            return;
        }

        if (is_string($value) || is_numeric($value)) {
            $rawTags[] = (string) $value;

            return;
        }

        if (! is_array($value)) {
            return;
        }

        foreach ($value as $item) {
            if (is_string($item) || is_numeric($item)) {
                $rawTags[] = (string) $item;

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            foreach (['name', 'label', 'text', 'tag', 'code', 'value'] as $key) {
                if (filled($item[$key] ?? null)) {
                    $rawTags[] = (string) $item[$key];
                    break;
                }
            }
        }
    }

    private function normalizeTagLabel(string $tag): ?string
    {
        $tag = trim($tag);

        if ($tag === '') {
            return null;
        }

        return Str::of($tag)
            ->upper()
            ->replaceMatches('/\s+/', ' ')
            ->toString();
    }

    private function detectDepartmentTag(array $tags): ?string
    {
        foreach ($tags as $tag) {
            if (preg_match('/^DEP[_\-\s]?([0-9]{2,3}|2A|2B)$/i', $tag, $matches)) {
                return 'DEP_'.strtoupper($matches[1]);
            }
        }

        return null;
    }

    /**
     * @return array{tag: string, code: string, label: string}|null
     */
    private function detectStatusTag(array $tags): ?array
    {
        $lookup = $this->statusLookup();

        foreach ($tags as $tag) {
            $code = $this->normalizeStatusCode($tag);

            if (isset($lookup[$code])) {
                return [
                    'tag' => $tag,
                    'code' => $code,
                    'label' => $lookup[$code],
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function statusLookup(): array
    {
        $lookup = config('ringover.status_tags', []);

        if (! Schema::hasTable('statut_phonings')) {
            return $lookup;
        }

        StatutPhoning::query()
            ->where('model_type', 'prospect')
            ->where('actif', true)
            ->get(['code', 'label'])
            ->each(function (StatutPhoning $statut) use (&$lookup): void {
                $lookup[$statut->code] = $statut->label;
            });

        return $lookup;
    }
}
