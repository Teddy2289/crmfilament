<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Partenaire;
use App\Models\Prospect;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CrmMapController extends Controller
{
    public function getData(): JsonResponse
    {
        try {
            $departments = [];
            $cities = [];

            $sources = [
                'prospects' => Prospect::query()->whereNull('deleted_at')->get(['ville', 'departement', 'code_postal']),
                'clients' => Client::query()->whereNull('deleted_at')->get(['ville', 'departement', 'code_postal']),
                'partenaires' => Partenaire::query()->whereNull('deleted_at')->get(['ville', 'departement', 'code_postal']),
            ];

            foreach ($sources as $type => $records) {
                foreach ($records as $record) {
                    $city = trim((string) ($record->ville ?? ''));
                    $department = $this->departmentFor($record->departement, $record->code_postal);
                    if ($city === '' && $department === '') {
                        continue;
                    }

                    $departmentKey = $department !== '' ? $department : 'NC';
                    $cityKey = mb_strtolower($city).'|'.$departmentKey;
                    $this->increment($departments, $departmentKey, $departmentKey, $type);
                    if ($city !== '') {
                        $this->increment($cities, $cityKey, $city, $type, $departmentKey);
                    }
                }
            }

            return response()->json([
                'departments' => $this->sortAggregates($departments),
                'cities' => $this->sortAggregates($cities),
            ]);
        } catch (\Throwable $exception) {
            Log::error('CrmMapController getData failed', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return response()->json([
                'departments' => [],
                'cities' => [],
            ], 500);
        }
    }

    private function departmentFor(?string $department, ?string $postalCode): string
    {
        $department = trim((string) $department);
        if ($department !== '' && preg_match('/^(?:\d{2,3}|2A|2B)$/i', $department)) {
            return strtoupper($department);
        }

        $postalCode = preg_replace('/\D+/', '', (string) $postalCode) ?: '';
        if (strlen($postalCode) >= 5) {
            if (str_starts_with($postalCode, '20')) {
                return ((int) substr($postalCode, 0, 3) < 202 ? '2A' : '2B');
            }
            return substr($postalCode, 0, 2);
        }

        return $department;
    }

    private function increment(array &$collection, string $key, string $label, string $type, ?string $department = null): void
    {
        if (! isset($collection[$key])) {
            $collection[$key] = [
                'label' => $label,
                'department' => $department,
                'total' => 0,
                'prospects' => 0,
                'clients' => 0,
                'partenaires' => 0,
            ];
        }
        $collection[$key]['total']++;
        $collection[$key][$type]++;
    }

    private function sortAggregates(array $aggregates): array
    {
        uasort($aggregates, fn (array $a, array $b) => $b['total'] <=> $a['total'] ?: strcasecmp($a['label'], $b['label']));
        return array_values($aggregates);
    }
}
