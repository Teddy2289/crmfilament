<?php

namespace App\Http\Controllers;

use App\Services\ImportExportService;
use Illuminate\Http\Request;

class ImportExportController extends Controller
{
    protected $importExportService;

    public function __construct(ImportExportService $importExportService)
    {
        $this->importExportService = $importExportService;
    }

    public function export(Request $request)
    {
        $type = $request->input('type', 'prospects');
        $filters = $request->input('filters', []);

        $data = $this->importExportService->exportData($type, $filters);

        return response()->json([
            'data' => $data,
            'type' => $type,
            'filters' => $filters,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx',
            'type' => 'required|in:prospects,clients,partenaires',
            'field_mapping' => 'nullable|array',
        ]);

        $file = $request->file('file');
        $type = $request->input('type');
        $fieldMapping = $request->input('field_mapping');

        $results = $this->importExportService->importData($type, $file, $fieldMapping);

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    public function getFieldMappings($type)
    {
        $mappings = $this->importExportService->getFieldMappings($type);

        return response()->json([
            'mappings' => $mappings,
        ]);
    }

    public function saveTemplate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:prospects,clients,partenaires',
            'mapping' => 'required|array',
            'name' => 'required|string',
        ]);

        $this->importExportService->saveImportTemplate(
            $request->input('type'),
            $request->input('mapping'),
            $request->input('name')
        );

        return response()->json([
            'success' => true,
        ]);
    }

    public function getTemplates()
    {
        $templates = $this->importExportService->getImportTemplates();

        return response()->json([
            'templates' => $templates,
        ]);
    }

    public function getHistory()
    {
        $history = $this->importExportService->getImportHistory();

        return response()->json([
            'history' => $history,
        ]);
    }
}
