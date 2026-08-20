<div class="flex flex-wrap items-center justify-end gap-2 rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-gray-900">
    <span class="mr-auto text-sm text-gray-600 dark:text-gray-300">Exporter l’évolution journalière</span>
    <button type="button" class="fi-btn fi-btn-size-sm fi-btn-color-gray" onclick="window.crmExportCallsChartImage()">Télécharger l’image PNG</button>
    <button type="button" class="fi-btn fi-btn-size-sm fi-btn-color-primary" onclick="window.crmPrintCallsChartPdf()">Exporter le graphique en PDF</button>
    <button type="button" class="fi-btn fi-btn-size-sm fi-btn-color-success" onclick="window.crmPrintPerformancePdf()">Tableau + camembert en PDF</button>
    <script>
        window.crmExportCallsChartImage = function () {
            const canvas = document.querySelector('canvas');
            if (!canvas) return;
            const link = document.createElement('a');
            link.download = 'evolution-journaliere-appels.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        };
        window.crmPrintCallsChartPdf = function () {
            const canvas = document.querySelector('canvas');
            if (!canvas) return;
            const win = window.open('', '_blank', 'width=1100,height=750');
            win.document.write('<html><head><title>Evolution journaliere des appels</title><style>body{font-family:Arial,sans-serif;padding:30px}img{max-width:100%;}h1{font-size:22px}</style></head><body><h1>Evolution journaliere des appels</h1><img src="' + canvas.toDataURL('image/png') + '"><script>window.onload=function(){window.print();}<\/script></body></html>');
            win.document.close();
        };
        window.crmPrintPerformancePdf = function () {
            const canvases = document.querySelectorAll('canvas');
            const table = document.querySelector('table');
            if (!canvases.length || !table) return;
            const win = window.open('', '_blank', 'width=1200,height=900');
            const images = Array.from(canvases).slice(-2).map(c => '<img class="chart" src="' + c.toDataURL('image/png') + '">').join('');
            win.document.write('<html><head><title>Performances agents et statuts d’appels</title><style>body{font-family:Arial,sans-serif;padding:28px;color:#111}.charts{display:flex;gap:20px;align-items:center}.chart{width:48%;max-height:380px;object-fit:contain}table{width:100%;border-collapse:collapse;margin-top:25px;font-size:12px}th,td{border:1px solid #ddd;padding:7px;text-align:left}th{background:#f3f4f6}h1{font-size:22px}</style></head><body><h1>Performances agents et répartition des statuts</h1><div class="charts">' + images + '</div>' + table.outerHTML + '<script>window.onload=function(){window.print();}<\/script></body></html>');
            win.document.close();
        };
    </script>
</div>
