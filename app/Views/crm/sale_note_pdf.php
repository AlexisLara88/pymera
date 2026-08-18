<?php

/**
 * @var array{
 *     business_name: string,
 *     currency_code: string,
 *     customer_name: string,
 *     customer_email: string|null,
 *     customer_phone: string|null,
 *     customer_identity_document: string,
 *     description: string,
 *     sale_date: string,
 *     amount: string
 * } $saleNote
 */

$parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $saleNote['sale_date']);
$saleDate = $parsedDate === false
    ? $saleNote['sale_date']
    : $parsedDate->format('d/m/Y');
$amount = $saleNote['currency_code'] . ' '
    . number_format((float) $saleNote['amount'], 2, ',', '.');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { color: #17343a; font-family: sans-serif; font-size: 11pt; }
        .header { width: 100%; border-bottom: 2px solid #0f6f68; }
        .brand { padding: 0 0 12px; vertical-align: bottom; }
        .product-logo { width: 24px; height: 24px; margin-right: 7px; vertical-align: middle; }
        .brand-mark { color: #0f6f68; font-size: 11pt; font-weight: bold; letter-spacing: .6px; vertical-align: middle; }
        .business-name { margin-top: 4px; font-size: 15pt; font-weight: bold; }
        .title { padding: 0 0 12px; text-align: right; vertical-align: bottom; }
        .title h1 { margin: 0; color: #17343a; font-size: 24pt; letter-spacing: .5px; }
        .title p { margin: 5px 0 0; color: #5e7276; font-size: 9pt; }
        .section { margin-top: 22px; }
        .section-title { margin-bottom: 7px; color: #0f6f68; font-size: 9pt; font-weight: bold; letter-spacing: .7px; text-transform: uppercase; }
        .customer { width: 100%; border: 1px solid #d9e2e4; border-collapse: collapse; }
        .customer td { padding: 9px 11px; border-bottom: 1px solid #e5ebed; }
        .customer tr:last-child td { border-bottom: 0; }
        .label { width: 22%; color: #6b7c80; font-size: 9pt; font-weight: bold; }
        .items { width: 100%; border: 1px solid #cfdadd; border-collapse: collapse; }
        .items th { padding: 9px; color: #fff; background: #0f6f68; font-size: 9pt; text-align: left; }
        .items td { padding: 12px 9px; border-bottom: 1px solid #e2e9eb; vertical-align: top; }
        .items .quantity { width: 13%; text-align: center; }
        .items .amount { width: 24%; text-align: right; white-space: nowrap; }
        .total-table { width: 42%; margin: 18px 0 0 auto; border-collapse: collapse; }
        .total-table td { padding: 10px 12px; border-top: 1px solid #d4dee0; }
        .total-label { color: #53686d; font-size: 10pt; font-weight: bold; }
        .total-value { color: #0f6f68; font-size: 14pt; font-weight: bold; text-align: right; }
        .footer { margin-top: 34px; padding: 13px 15px; border: 1px solid #d9e2e4; color: #62767a; background: #f4f7f7; font-size: 8.5pt; line-height: 1.45; }
        .footer strong { color: #314e54; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="brand">
                <div>
                    <img class="product-logo" src="<?= esc(FCPATH . 'assets/brand/pymera-symbol.svg', 'attr') ?>" alt="">
                    <span class="brand-mark">PyMERA</span>
                </div>
                <div class="business-name"><?= esc($saleNote['business_name']) ?></div>
            </td>
            <td class="title">
                <h1>NOTA DE VENTA</h1>
                <p>Fecha de venta: <?= esc($saleDate) ?></p>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Cliente</div>
        <table class="customer">
            <tr><td class="label">Nombre</td><td><?= esc($saleNote['customer_name']) ?></td></tr>
            <tr><td class="label">DNI/CI</td><td><?= esc($saleNote['customer_identity_document']) ?></td></tr>
            <?php if ($saleNote['customer_email'] !== null): ?>
                <tr><td class="label">Correo</td><td><?= esc($saleNote['customer_email']) ?></td></tr>
            <?php endif ?>
            <?php if ($saleNote['customer_phone'] !== null): ?>
                <tr><td class="label">Teléfono</td><td><?= esc($saleNote['customer_phone']) ?></td></tr>
            <?php endif ?>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Detalle de la venta</div>
        <table class="items">
            <thead><tr><th class="quantity">Cantidad</th><th>Descripción</th><th class="amount">Importe</th></tr></thead>
            <tbody><tr><td class="quantity">1</td><td><?= esc($saleNote['description']) ?></td><td class="amount"><?= esc($amount) ?></td></tr></tbody>
        </table>
        <table class="total-table">
            <tr><td class="total-label">TOTAL</td><td class="total-value"><?= esc($amount) ?></td></tr>
        </table>
    </div>

    <div class="footer">
        <strong>Comprobante comercial no fiscal.</strong><br>
        Generado con PyMERA. Esta nota resume una venta confirmada y no constituye factura, documento tributario ni comprobante de pago.
    </div>
</body>
</html>
