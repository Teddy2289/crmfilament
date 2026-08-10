<?php

namespace App\Exports;

use App\Models\Appel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class AppelsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Appel::query();

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
            'Numéro',
            'Date/Heure',
            'Durée',
            'Statut',
            'Résultat',
            'Consultant',
            'Prospect',
            'Date Création',
            'Date Mise à Jour',
        ];
    }

    public function map($appel): array
    {
        return [
            $appel->id,
            $appel->numero ?? 'N/A',
            $appel->date_heure?->format('d/m/Y H:i') ?? 'N/A',
            $appel->duree ?? 0,
            $appel->statut ?? 'N/A',
            $appel->resultat ?? 'N/A',
            $appel->consultant?->name ?? 'N/A',
            $appel->prospect?->nom ?? 'N/A',
            $appel->created_at?->format('d/m/Y H:i') ?? 'N/A',
            $appel->updated_at?->format('d/m/Y H:i') ?? 'N/A',
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
        return 'Appels';
    }
}
