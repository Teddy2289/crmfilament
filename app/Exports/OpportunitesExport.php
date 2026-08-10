<?php

namespace App\Exports;

use App\Models\Opportunite;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class OpportunitesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Opportunite::query();

        if (!empty($this->filters['statut'])) {
            $query->where('statut', $this->filters['statut']);
        }

        if (!empty($this->filters['date_debut'])) {
            $query->where('created_at', '>=', $this->filters['date_debut']);
        }

        if (!empty($this->filters['date_fin'])) {
            $query->where('created_at', '<=', $this->filters['date_fin']);
        }

        return $query->with(['consultant', 'prospect'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Titre',
            'Montant',
            'Statut',
            'Probabilité',
            'Date Clôture',
            'Consultant',
            'Prospect',
            'Date Création',
            'Date Mise à Jour',
        ];
    }

    public function map($opportunite): array
    {
        return [
            $opportunite->id,
            $opportunite->titre ?? 'N/A',
            $opportunite->montant ?? 0,
            $opportunite->statut ?? 'N/A',
            $opportunite->probabilite ?? 0,
            $opportunite->date_cloture?->format('d/m/Y') ?? 'N/A',
            $opportunite->consultant?->name ?? 'N/A',
            $opportunite->prospect?->nom ?? 'N/A',
            $opportunite->created_at?->format('d/m/Y H:i') ?? 'N/A',
            $opportunite->updated_at?->format('d/m/Y H:i') ?? 'N/A',
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
        return 'Opportunites';
    }
}
