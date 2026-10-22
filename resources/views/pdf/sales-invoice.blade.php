<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <title>Factura de Venta - {{ $invoice->code }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 10mm 12mm 12mm 12mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            color: #0f172a;
            line-height: 1.3;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .company-rif {
            font-size: 11px;
            font-weight: bold;
            color: #1e40af;
            margin-top: 2px;
        }
        .company-meta {
            font-size: 8px;
            color: #475569;
            margin-top: 2px;
            line-height: 1.25;
        }
        .taxpayer-tag {
            display: inline-block;
            font-size: 7.5px;
            font-weight: bold;
            color: #1e3a8a;
            background-color: #dbeafe;
            border: 1px solid #bfdbfe;
            padding: 1px 5px;
            border-radius: 3px;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Fiscal Box (Right Side) */
        .fiscal-box {
            border: 2px solid #1e3a8a;
            border-radius: 6px;
            padding: 6px 10px;
            text-align: center;
            background-color: #f8fafc;
            width: 42%;
        }
        .doc-title {
            font-size: 15px;
            font-weight: 900;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .fiscal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .fiscal-table td {
            padding: 1.5px 0;
        }
        .fiscal-label {
            font-weight: bold;
            color: #475569;
            text-align: left;
            text-transform: uppercase;
            font-size: 7.5px;
        }
        .fiscal-value {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
            font-family: 'Courier New', Courier, monospace;
            font-size: 9px;
        }
        .control-number {
            color: #b91c1c;
            font-size: 10.5px;
            font-weight: 900;
        }

        /* Divider */
        .divider {
            height: 2px;
            background-color: #1e3a8a;
            width: 100%;
            margin-top: 4px;
            margin-bottom: 8px;
            border-radius: 2px;
        }

        /* 2-Column Info Grid */
        .info-grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-left: -6px;
            margin-right: -6px;
            margin-bottom: 8px;
        }
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 6px 8px;
            vertical-align: top;
            width: 50%;
        }
        .card-header-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e40af;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 2px;
            margin-bottom: 4px;
        }
        .data-row-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-row-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .data-label {
            width: 32%;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
        }
        .data-value {
            width: 68%;
            font-size: 8px;
            color: #0f172a;
            font-weight: 500;
        }
        .data-value-strong {
            font-weight: bold;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .items-table thead th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 5px 4px;
            border: 1px solid #1e3a8a;
        }
        .items-table tbody td {
            padding: 4px 4px;
            border: 1px solid #e2e8f0;
            font-size: 8px;
            vertical-align: middle;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .font-bold { font-weight: bold; }

        .badge-tax {
            display: inline-block;
            font-size: 7px;
            font-weight: bold;
            color: #1e40af;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 1px 4px;
            border-radius: 2px;
        }
        .badge-exempt {
            display: inline-block;
            font-size: 7px;
            font-weight: bold;
            color: #15803d;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 1px 4px;
            border-radius: 2px;
        }

        /* Totals and Summary Layout */
        .totals-container-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .totals-container-table td {
            vertical-align: top;
        }
        .totals-left-box {
            width: 50%;
            padding-right: 8px;
        }
        .totals-right-box {
            width: 50%;
            padding-left: 8px;
        }

        /* Breakdown Table */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .summary-table td {
            padding: 2.5px 6px;
            border-bottom: 1px solid #e2e8f0;
        }
        .summary-label {
            color: #475569;
            font-weight: 500;
        }
        .summary-usd {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
            color: #0f172a;
        }
        .summary-ves {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
            color: #1e40af;
        }
        .summary-header-row th {
            background-color: #f1f5f9;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            padding: 3px 6px;
            border-bottom: 1px solid #cbd5e1;
        }
        .total-highlight-row td {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            font-size: 9.5px;
            padding: 5px 6px;
            border: none;
        }
        .total-highlight-row .summary-usd,
        .total-highlight-row .summary-ves {
            color: #ffffff;
            font-size: 10px;
        }

        .rate-box {
            background-color: #eff6ff;
            border: 1px dashed #3b82f6;
            border-radius: 4px;
            padding: 6px 8px;
            margin-top: 6px;
            font-size: 8px;
            line-height: 1.35;
        }

        .legal-notice-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 7.5px;
            color: #334155;
            line-height: 1.3;
        }

        /* Footer */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            margin-top: 6px;
            font-size: 7px;
            color: #64748b;
        }
    </style>
</head>
<body>

    @php
        $exchangeRate = (float) ($invoice->exchange_rate ?? 1);
        if ($exchangeRate <= 0) {
            $exchangeRate = 1.0;
        }

        $controlNumber = $invoice->control_number
            ?? ('00-' . str_pad((string) preg_replace('/[^0-9]/', '', $invoice->code ?? '1'), 8, '0', STR_PAD_LEFT));

        $taxpayerType = $company->taxpayer_type ?? 'ordinary';
        $taxpayerLabel = match($taxpayerType) {
            'special' => 'CONTRIBUYENTE ESPECIAL',
            'formal' => 'CONTRIBUYENTE FORMAL',
            default => 'CONTRIBUYENTE ORDINARIO',
        };

        // Bases calculation
        $exemptBase = 0.0;
        $taxGroups = [];

        foreach ($invoice->lines as $line) {
            $taxPercent = (float) ($line->tax_percent ?? 0);
            $subtotal = (float) ($line->subtotal ?? 0);
            $taxAmount = (float) ($line->tax_amount ?? 0);

            if ($taxPercent <= 0) {
                $exemptBase += $subtotal;
            } else {
                $pKey = (string) $taxPercent;
                if (!isset($taxGroups[$pKey])) {
                    $taxGroups[$pKey] = [
                        'percent' => $taxPercent,
                        'base' => 0.0,
                        'tax' => 0.0,
                    ];
                }
                $taxGroups[$pKey]['base'] += $subtotal;
                $taxGroups[$pKey]['tax'] += $taxAmount;
            }
        }
        krsort($taxGroups);
    @endphp

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <!-- Company Info Left -->
            <td style="width: 58%; vertical-align: top;">
                <div class="company-name">{{ $company->name ?? config('app.name') }}</div>
                <div class="company-rif">RIF: {{ $company->rif ?? 'J-00000000-0' }}</div>
                <div class="company-meta">
                    <strong>Domicilio Fiscal:</strong> {{ $company->fiscal_address ?? $company->address ?? 'Venezuela' }}
                </div>
                @if(!empty($company->phone))
                    <div class="company-meta"><strong>Teléfono:</strong> {{ $company->phone }}</div>
                @endif
                <div>
                    <span class="taxpayer-tag">{{ $taxpayerLabel }}</span>
                </div>
            </td>

            <!-- Fiscal Box Right -->
            <td class="fiscal-box">
                <div class="doc-title">FACTURA</div>
                <table class="fiscal-table">
                    <tr>
                        <td class="fiscal-label">FACTURA N°:</td>
                        <td class="fiscal-value">{{ $invoice->code }}</td>
                    </tr>
                    <tr>
                        <td class="fiscal-label">N° DE CONTROL:</td>
                        <td class="fiscal-value control-number">{{ $controlNumber }}</td>
                    </tr>
                    <tr>
                        <td class="fiscal-label">FECHA EMISIÓN:</td>
                        <td class="fiscal-value">{{ $invoice->invoice_date?->format('d/m/Y') ?? date('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="fiscal-label">HORA:</td>
                        <td class="fiscal-value">{{ $invoice->created_at?->format('H:i:s') ?? date('H:i:s') }}</td>
                    </tr>
                    <tr>
                        <td class="fiscal-label">VENCIMIENTO:</td>
                        <td class="fiscal-value">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="fiscal-label">CONDICIÓN:</td>
                        <td class="fiscal-value">{{ $invoice->sale_type === 'cash' ? 'CONTADO' : 'CRÉDITO' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Customer and Sale Details Grid -->
    <table class="info-grid-table">
        <tr>
            <!-- Customer Card -->
            <td class="info-card">
                <div class="card-header-title">DATOS DEL CLIENTE / RECEPTOR</div>
                <table class="data-row-table">
                    <tr>
                        <td class="data-label">Razón Social:</td>
                        <td class="data-value data-value-strong">{{ $invoice->client->name ?? 'Consumidor Final' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">RIF / C.I.:</td>
                        <td class="data-value data-value-strong">
                            {{ $invoice->client->tax_number ?? $invoice->client->document_number ?? 'V-00000000-0' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="data-label">Dirección:</td>
                        <td class="data-value">
                            {{ $invoice->clientAddress?->address ?? $invoice->client?->address ?? 'No registrada' }}
                        </td>
                    </tr>
                    @if(!empty($invoice->client?->phone))
                        <tr>
                            <td class="data-label">Teléfono:</td>
                            <td class="data-value">{{ $invoice->client->phone }}</td>
                        </tr>
                    @endif
                </table>
            </td>

            <!-- Transaction Card -->
            <td class="info-card">
                <div class="card-header-title">DETALLES DE LA OPERACIÓN</div>
                <table class="data-row-table">
                    <tr>
                        <td class="data-label">Almacén:</td>
                        <td class="data-value">{{ $invoice->warehouse->name ?? 'Principal' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Vendedor:</td>
                        <td class="data-value">{{ $invoice->salesperson->name ?? 'Venta Directa' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Moneda Doc.:</td>
                        <td class="data-value"><strong>{{ $invoice->currency ?? 'USD' }}</strong> (Dólares Americanos)</td>
                    </tr>
                    @if(!empty($invoice->dispatch_id) && $invoice->dispatch)
                        <tr>
                            <td class="data-label">Guía Despacho:</td>
                            <td class="data-value"><strong>{{ $invoice->dispatch->code }}</strong></td>
                        </tr>
                    @endif
                    <tr>
                        <td class="data-label">Estado:</td>
                        <td class="data-value"><strong style="text-transform: uppercase;">{{ $invoice->status }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 14%;">CÓDIGO</th>
                <th style="width: 33%;">DESCRIPCIÓN</th>
                <th style="width: 7%;" class="text-right">CANT.</th>
                <th style="width: 6%;" class="text-center">UND</th>
                <th style="width: 8%;" class="text-center">ALÍCUOTA</th>
                <th style="width: 13%;" class="text-right">P. UNIT ($)</th>
                <th style="width: 15%;" class="text-right">TOTAL ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->lines as $idx => $line)
                @php
                    $lineTaxPercent = (float) ($line->tax_percent ?? 0);
                    $lineTotal = (float) ($line->total ?? 0);
                    $lineUnitPrice = (float) ($line->unit_price ?? 0);
                    $lineQty = (float) ($line->quantity ?? 0);
                @endphp
                <tr>
                    <td class="text-center font-mono">{{ $idx + 1 }}</td>
                    <td class="font-mono text-left">{{ $line->item->code ?? '—' }}</td>
                    <td class="text-left font-bold">
                        {{ $line->item->name ?? $line->notes ?? 'Producto' }}
                    </td>
                    <td class="text-right font-mono">{{ number_format($lineQty, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $line->measurementUnit->symbol ?? 'UND' }}</td>
                    <td class="text-center">
                        @if($lineTaxPercent <= 0)
                            <span class="badge-exempt">(E)</span>
                        @else
                            <span class="badge-tax">({{ number_format($lineTaxPercent, 0) }}%)</span>
                        @endif
                    </td>
                    <td class="text-right font-mono">{{ number_format($lineUnitPrice, 2, ',', '.') }}</td>
                    <td class="text-right font-mono font-bold">{{ number_format($lineTotal, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 10px; color: #64748b;">
                        Sin líneas registradas.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Totals and Fiscal Breakdown Section -->
    <table class="totals-container-table">
        <tr>
            <!-- Left Side: Notes & Legal Notice -->
            <td class="totals-left-box">
                @if(!empty($invoice->notes))
                    <div class="legal-notice-box" style="margin-bottom: 6px;">
                        <strong>Observaciones:</strong><br>
                        {{ $invoice->notes }}
                    </div>
                @endif

                <div class="rate-box">
                    <strong>TASA DE CAMBIO OFICIAL BCV:</strong><br>
                    <span><strong>1 USD = Bs. {{ number_format($exchangeRate, 4, ',', '.') }}</strong></span><br>
                    <span style="font-size: 7px; color: #475569;">
                        Monto expresado en Bolívares conforme al Art. 13 de la Providencia 00071 del SENIAT y la Ley del IVA.
                    </span>
                </div>

                <div class="legal-notice-box" style="margin-top: 6px;">
                    <strong>NORMATIVA LEGAL SENIAT:</strong><br>
                    Factura emitida bajo Sistema Computarizado conforme a la Providencia Administrativa N° SNAT/2011/00071. Este documento goza de plena validez fiscal y comercial.
                </div>
            </td>

            <!-- Right Side: Numerical Summary -->
            <td class="totals-right-box">
                <table class="summary-table">
                    <thead>
                        <tr class="summary-header-row">
                            <th class="text-left">CONCEPTO</th>
                            <th class="text-right" style="width: 32%;">MONTO (USD)</th>
                            <th class="text-right" style="width: 34%;">EQUIV. (VES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="summary-label">Subtotal de la Operación:</td>
                            <td class="summary-usd">$ {{ number_format((float)$invoice->subtotal, 2, ',', '.') }}</td>
                            <td class="summary-ves">Bs. {{ number_format((float)$invoice->subtotal * $exchangeRate, 2, ',', '.') }}</td>
                        </tr>

                        @if((float)$invoice->discount_amount > 0)
                            <tr>
                                <td class="summary-label">Descuentos Aplicados:</td>
                                <td class="summary-usd" style="color: #b91c1c;">-$ {{ number_format((float)$invoice->discount_amount, 2, ',', '.') }}</td>
                                <td class="summary-ves" style="color: #b91c1c;">-Bs. {{ number_format((float)$invoice->discount_amount * $exchangeRate, 2, ',', '.') }}</td>
                            </tr>
                        @endif

                        @if($exemptBase > 0)
                            <tr>
                                <td class="summary-label">Monto Total Exento / Exonerado (E):</td>
                                <td class="summary-usd">$ {{ number_format($exemptBase, 2, ',', '.') }}</td>
                                <td class="summary-ves">Bs. {{ number_format($exemptBase * $exchangeRate, 2, ',', '.') }}</td>
                            </tr>
                        @endif

                        @foreach($taxGroups as $g)
                            <tr>
                                <td class="summary-label">Base Imponible ({{ number_format($g['percent'], 0) }}%):</td>
                                <td class="summary-usd">$ {{ number_format($g['base'], 2, ',', '.') }}</td>
                                <td class="summary-ves">Bs. {{ number_format($g['base'] * $exchangeRate, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="summary-label">Impuesto IVA ({{ number_format($g['percent'], 0) }}%):</td>
                                <td class="summary-usd">$ {{ number_format($g['tax'], 2, ',', '.') }}</td>
                                <td class="summary-ves">Bs. {{ number_format($g['tax'] * $exchangeRate, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach

                        @if(count($taxGroups) === 0 && (float)$invoice->tax_amount > 0)
                            <tr>
                                <td class="summary-label">Total Impuesto IVA:</td>
                                <td class="summary-usd">$ {{ number_format((float)$invoice->tax_amount, 2, ',', '.') }}</td>
                                <td class="summary-ves">Bs. {{ number_format((float)$invoice->tax_amount * $exchangeRate, 2, ',', '.') }}</td>
                            </tr>
                        @endif

                        <tr class="total-highlight-row">
                            <td class="text-left font-bold">TOTAL FACTURA:</td>
                            <td class="summary-usd">$ {{ number_format((float)$invoice->total, 2, ',', '.') }}</td>
                            <td class="summary-ves">Bs. {{ number_format((float)$invoice->total * $exchangeRate, 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <table class="footer-table">
        <tr>
            <td style="text-align: left;">
                Factura de Venta · Providencia Administrativa SENIAT N° SNAT/2011/00071 (Art. 13 y 15)
            </td>
            <td style="text-align: right;">
                Fecha y hora de impresión: {{ date('d/m/Y H:i:s') }}
            </td>
        </tr>
    </table>

</body>
</html>
