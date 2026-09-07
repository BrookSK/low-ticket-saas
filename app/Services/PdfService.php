<?php

namespace App\Services;

use App\Core\Response;
use App\Core\View;

/**
 * Geracao de PDF. Usa Dompdf quando disponivel (via Composer); caso contrario,
 * retorna HTML pronto para impressao (window.print) como fallback funcional.
 */
class PdfService
{
    protected View $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function available(): bool
    {
        return class_exists(\Dompdf\Dompdf::class);
    }

    /**
     * Renderiza uma view e devolve como PDF (ou HTML imprimivel no fallback).
     */
    public function fromView(string $template, array $data, string $filename = 'documento.pdf', bool $download = false): Response
    {
        $html = $this->view->render($template, $data)->getContent();
        return $this->fromHtml($html, $filename, $download);
    }

    public function fromHtml(string $html, string $filename = 'documento.pdf', bool $download = false): Response
    {
        if ($this->available()) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $output = $dompdf->output();

            $disposition = $download ? 'attachment' : 'inline';
            return Response::make($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            ]);
        }

        // Fallback: HTML com botao/auto-print. Deixa claro que e o modo alternativo.
        $printable = $html . '<script>window.onload=function(){setTimeout(function(){window.print();},300);}</script>';
        return Response::make($printable, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
