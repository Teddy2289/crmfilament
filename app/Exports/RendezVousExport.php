<?php

namespace App\Exports;

use App\Models\RendezVous;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class RendezVousExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = RendezVous::query();

        if (!empty($this->filters['statut'])) {
            $query->where('statut', $this->filters['statut']);
        }

        if (!empty($this->filters['date_debut'])) {
            $query->where('date_heure', '>=', $this->filters['date_debut']);
        }

        if (!empty($this->filters['date_fin'])) {
            $query->where('date_heure', '<=', $this->filters['date_fin']);
        }

        return $query->with(['consultant', 'prospect'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Titre',
            'Date/Heure',
            'Durée',
            'Statut',
            'Lieu',
            'Consultant',
            'Prospect',
            'Date Création',
            'Date Mise à Jour',
        ];
    }

    public function map($rdv): array
    {
        return [
            $rdv->id,
            $rdv->titre ?? 'N/A',
            $rdv->date_heure?->format('d/m/Y H:i') ?? 'N/A',
            $rdv->duree ?? 0,
            $rdv->statut ?? 'N/A',
            $rdv->lieu ?? 'N/A',
            $rdv->consultant?->name ?? 'N/A',
            $rdv->prospect?->nom ?? 'N/A',
            $rdv->created_at?->format('d/m/Y H:i') ?? 'N/A',
            $rdv->updated_at?->format('d/m/Y H:i') ?? 'N/A',
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
        return 'RendezVous';
    }
}
