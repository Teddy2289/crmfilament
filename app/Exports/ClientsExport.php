<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Client::query();

        if (!empty($this->filters['statut'])) {
            $query->where('statut', $this->filters['statut']);
        }

        if (!empty($this->filters['date_debut'])) {
            $query->where('created_at', '>=', $this->filters['date_debut']);
        }

        if (!empty($this->filters['date_fin'])) {
            $query->where('created_at', '<=', $this->filters['date_fin']);
        }

        return $query->with(['consultant', 'entiteCommerciale'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nom',
            'Email',
            'Téléphone',
            'Statut',
            'Consultant',
            'Entité Commerciale',
            'Date Création',
            'Date Mise à Jour',
        ];
    }

    public function map($client): array
    {
        return [
            $client->id,
            $client->nom ?? 'N/A',
            $client->email ?? 'N/A',
            $client->telephone ?? 'N/A',
            $client->statut ?? 'N/A',
            $client->consultant?->name ?? 'N/A',
            $client->entiteCommerciale?->nom ?? 'N/A',
            $client->created_at?->format('d/m/Y H:i') ?? 'N/A',
            $client->updated_at?->format('d/m/Y H:i') ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'E0E0E0']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Clients';
    }
}
