<?php

declare(strict_types=1);

namespace App\Libraries;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

final class SaleNotePdfRenderer
{
    /**
     * @param array{
     *     business_name: string,
     *     currency_code: string,
     *     customer_name: string,
     *     customer_email: string|null,
     *     customer_phone: string|null,
     *     description: string,
     *     sale_date: string,
     *     amount: string
     * } $saleNote
     */
    public function render(array $saleNote): string
    {
        $pdf = new Mpdf([
            'mode'                 => 'utf-8',
            'format'               => 'A4',
            'margin_left'          => 16,
            'margin_right'         => 16,
            'margin_top'           => 16,
            'margin_bottom'        => 16,
            'tempDir'              => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pyme-erp-lite-mpdf',
            'cacheCleanupInterval' => 600,
        ]);
        $pdf->SetTitle('Nota de venta');
        $pdf->SetAuthor($saleNote['business_name']);
        $pdf->SetCreator('PyMERA');
        $pdf->WriteHTML(view('crm/sale_note_pdf', ['saleNote' => $saleNote]));

        return $pdf->Output('', Destination::STRING_RETURN);
    }
}
