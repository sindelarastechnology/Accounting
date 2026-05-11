<?php

namespace App\Traits;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;

trait HasReportExport
{
    abstract protected function pdfView(): string;

    abstract protected function pdfOrientation(): string;

    abstract protected function reportTitle(): string;

    public function exportPdf()
    {
        $data = $this->getData();

        $pdf = Pdf::loadView($this->pdfView(), [
            'data' => $data,
            'period' => method_exists($this, 'getPeriod') ? $this->getPeriod() : null,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', $this->pdfOrientation());

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $this->getPdfFilename());
    }

    public function printReport()
    {
        $this->dispatch('print-report');
    }

    public function getPdfFilename(): string
    {
        $date = now()->format('Ymd_His');
        $name = str_replace(' ', '_', $this->reportTitle());
        return "{$name}_{$date}.pdf";
    }
}
