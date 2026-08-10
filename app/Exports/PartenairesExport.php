<?php

namespace App\Exports;

use App\Models\Partenaire;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class PartenairesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Partenaire::query();

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
            'Type',
            'Consultant',
            'Entité Commerciale',
            'Date Création',
            'Date Mise à Jour',
        ];
    }

    public function map($partenaire): array
    {
        return [
            $partenaire->id,
            $partenaire->nom ?? 'N/A',
            $partenaire->email ?? 'N/A',
            $partenaire->telephone ?? 'N/A',
            $partenaire->statut ?? 'N/A',
            $partenaire->type ?? 'N/A',
            $partenaire->consultant?->name ?? 'N/A',
            $partenaire->entiteCommerciale?->nom ?? 'N/A',
            $partenaire->created_at?->format('d/m/Y H:i') ?? 'N/A',
            $partenaire->updated_at?->format('d/m/Y H:i') ?? 'N/A',
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
        return 'Partenaires';
    }
}
