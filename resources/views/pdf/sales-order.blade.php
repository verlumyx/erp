<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <title>Orden de Venta - {{ $order->code }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 12mm 14mm 12mm 14mm;
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

        /* Order Box (Right Side) */
        .order-box {
            border: 2px solid #1e3a8a;
            border-radius: 6px;
            padding: 6px 10px;
            text-align: center;
            background-color: #f8fafc;
            width: 42%;
        }
        .doc-title {
            font-size: 14px;
            font-weight: 900;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .order-table td {
            padding: 1.5px 0;
        }
        .order-label {
            font-weight: bold;
            color: #475569;
            text-align: left;
            text-transform: uppercase;
            font-size: 7.5px;
        }
        .order-value {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
            font-family: 'Courier New', Courier, monospace;
            font-size: 9px;
        }

        .status-badge {
            display: inline-block;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 1px 6px;
            border-radius: 3px;
            letter-spacing: 0.3px;
        }
        .status-confirmed {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .status-partial {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .status-completed {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
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
            width: 34%;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
        }
        .data-value {
            width: 66%;
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

        /* Totals Layout */
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
            padding: 3px 6px;
            border-bottom: 1px solid #e2e8f0;
        }
        .summary-label {
            color: #475569;
            font-weight: 500;
        }
        .summary-amount {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
            color: #0f172a;
        }
        .total-highlight-row td {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            font-size: 9.5px;
            padding: 5px 6px;
            border: none;
        }
        .total-highlight-row .summary-amount {
            color: #ffffff;
            font-size: 10px;
        }

        .notes-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 7.5px;
            color: #334155;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            margin-top: 28px;
            padding-top: 3px;
            font-size: 7.5px;
            font-weight: bold;
            color: #475569;
        }
        .signature-role {
            font-size: 7px;
            color: #64748b;
        }

        /* Footer */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            margin-top: 8px;
            font-size: 7px;
            color: #64748b;
        }
    </style>
</head>
<body>

    @php
        $currency = $order->currency ?? 'USD';
        $statusLabels = [
            'draft' => 'Borrador',
            'confirmed' => 'Aprobada',
            'partial' => 'Parcial',
            'completed' => 'Completada',
            'cancelled' => 'Anulada',
        ];
        $statusClass = match($order->status) {
            'confirmed' => 'status-confirmed',
            'partial' => 'status-partial',
            'completed' => 'status-completed',
            'cancelled' => 'status-cancelled',
            default => 'status-confirmed',
        };
        $activeLines = $order->lines->where('status', 'active');
    @endphp

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <!-- Company Info Left -->
            <td style="width: 58%; vertical-align: top;">
                <div class="company-name">{{ $company->name ?? config('app.name') }}</div>
                <div class="company-rif">RIF: {{ $company->rif ?? '—' }}</div>
                <div class="company-meta">
                    <strong>Domicilio Fiscal:</strong> {{ $company->fiscal_address ?? $company->address ?? '—' }}
                </div>
                @if(!empty($company->phone))
                    <div class="company-meta"><strong>Teléfono:</strong> {{ $company->phone }}</div>
                @endif
            </td>

            <!-- Order Box Right -->
            <td class="order-box">
                <div class="doc-title">ORDEN DE VENTA</div>
                <table class="order-table">
                    <tr>
                        <td class="order-label">N° DE ORDEN:</td>
                        <td class="order-value">{{ $order->code }}</td>
                    </tr>
                    <tr>
                        <td class="order-label">FECHA EMISIÓN:</td>
                        <td class="order-value">{{ $order->order_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="order-label">FECHA ENTREGA:</td>
                        <td class="order-value">{{ $order->expected_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="order-label">CONDICIÓN:</td>
                        <td class="order-value">{{ $order->payment_term_days ? "{$order->payment_term_days} DÍAS" : 'CONTADO' }}</td>
                    </tr>
                    <tr>
                        <td class="order-label">ESTADO:</td>
                        <td class="order-value">
                            <span class="status-badge {{ $statusClass }}">
                                {{ $statusLabels[$order->status] ?? ucfirst($order->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Client & Order Info Grid -->
    <table class="info-grid-table">
        <tr>
            <!-- Client Card -->
            <td class="info-card">
                <div class="card-header-title">DATOS DEL CLIENTE</div>
                <table class="data-row-table">
                    <tr>
                        <td class="data-label">Razón Social:</td>
                        <td class="data-value data-value-strong">{{ $order->client->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">RIF / C.I.:</td>
                        <td class="data-value data-value-strong">
                            {{ $order->client?->tax_number ?? $order->client?->document_number ?? '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="data-label">Dirección:</td>
                        <td class="data-value">
                            {{ $order->clientAddress?->address ?? $order->client?->address ?? '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="data-label">Teléfono:</td>
                        <td class="data-value">{{ $order->client?->phone ?? $order->client?->mobile ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Correo:</td>
                        <td class="data-value">{{ $order->client?->email ?? '—' }}</td>
                    </tr>
                </table>
            </td>

            <!-- Order Details Card -->
            <td class="info-card">
                <div class="card-header-title">DETALLES DEL PEDIDO</div>
                <table class="data-row-table">
                    <tr>
                        <td class="data-label">Bodega Origen:</td>
                        <td class="data-value">{{ $order->warehouse->name ?? 'Principal' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Vendedor:</td>
                        <td class="data-value">{{ $order->salesperson->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Ref. Cliente:</td>
                        <td class="data-value">{{ $order->client_reference ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Moneda:</td>
                        <td class="data-value"><strong>{{ $currency }}</strong></td>
                    </tr>
                    <tr>
                        <td class="data-label">Registrado por:</td>
                        <td class="data-value">{{ $order->creator->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Aprobado por:</td>
                        <td class="data-value">
                            {{ $order->approver->name ?? '—' }}
                            @if($order->approved_at)
                                ({{ $order->approved_at->format('d/m/Y') }})
                            @endif
                        </td>
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
                <th style="width: 32%;">DESCRIPCIÓN</th>
                <th style="width: 8%;" class="text-right">CANT.</th>
                <th style="width: 6%;" class="text-center">UND</th>
                <th style="width: 12%;" class="text-right">P. UNIT ({{ $currency }})</th>
                <th style="width: 7%;" class="text-center">DESC.</th>
                <th style="width: 7%;" class="text-center">IVA</th>
                <th style="width: 10%;" class="text-right">TOTAL ({{ $currency }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activeLines as $idx => $line)
                @php
                    $lineTaxPercent = (float) ($line->tax_percent ?? 0);
                    $lineDiscountPercent = (float) ($line->discount_percent ?? 0);
                    $lineTotal = (float) ($line->total ?? 0);
                    $lineUnitPrice = (float) ($line->unit_price ?? 0);
                    $lineQty = (float) ($line->quantity ?? 0);
                @endphp
                <tr>
                    <td class="text-center font-mono">{{ $idx + 1 }}</td>
                    <td class="font-mono text-left">{{ $line->item->sku ?? $line->item->code ?? '—' }}</td>
                    <td class="text-left font-bold">
                        {{ $line->item->name ?? $line->notes ?? 'Producto' }}
                    </td>
                    <td class="text-right font-mono">{{ number_format($lineQty, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $line->measurementUnit->abbreviation ?? $line->measurementUnit->code ?? $line->measurementUnit->name ?? 'UND' }}</td>
                    <td class="text-right font-mono">{{ number_format($lineUnitPrice, 2, ',', '.') }}</td>
                    <td class="text-center">
                        @if($lineDiscountPercent > 0)
                            {{ number_format($lineDiscountPercent, 0) }}%
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-center">
                        @if($lineTaxPercent <= 0)
                            <span class="badge-exempt">(E)</span>
                        @else
                            <span class="badge-tax">{{ number_format($lineTaxPercent, 0) }}%</span>
                        @endif
                    </td>
                    <td class="text-right font-mono font-bold">{{ number_format($lineTotal, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 10px; color: #64748b;">
                        Sin líneas registradas.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Totals & Notes Section -->
    <table class="totals-container-table">
        <tr>
            <!-- Left Side: Notes & Signatures -->
            <td class="totals-left-box">
                @if(!empty($order->notes))
                    <div class="notes-box">
                        <strong>Observaciones:</strong><br>
                        {{ $order->notes }}
                    </div>
                @endif

                <table class="signatures-table">
                    <tr>
                        <td>
                            <div class="signature-line">{{ $order->creator->name ?? 'Elaborado por' }}</div>
                            <div class="signature-role">Elaborado por</div>
                        </td>
                        <td>
                            <div class="signature-line">{{ $order->approver->name ?? 'Aprobado por' }}</div>
                            <div class="signature-role">Aprobado por</div>
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Right Side: Totals -->
            <td class="totals-right-box">
                <table class="summary-table">
                    <tbody>
                        <tr>
                            <td class="summary-label">Subtotal:</td>
                            <td class="summary-amount">{{ $currency }} {{ number_format((float)$order->subtotal, 2, ',', '.') }}</td>
                        </tr>

                        @if((float)$order->discount_amount > 0)
                            <tr>
                                <td class="summary-label">Descuentos:</td>
                                <td class="summary-amount" style="color: #b91c1c;">-{{ $currency }} {{ number_format((float)$order->discount_amount, 2, ',', '.') }}</td>
                            </tr>
                        @endif

                        <tr>
                            <td class="summary-label">Impuestos (IVA):</td>
                            <td class="summary-amount">{{ $currency }} {{ number_format((float)$order->tax_amount, 2, ',', '.') }}</td>
                        </tr>

                        <tr class="total-highlight-row">
                            <td class="text-left font-bold">TOTAL ORDEN:</td>
                            <td class="summary-amount">{{ $currency }} {{ number_format((float)$order->total, 2, ',', '.') }}</td>
                        </tr>

                        @if(!empty($order->base_currency) && $order->base_currency !== $order->currency && (float)$order->exchange_rate > 0)
                            <tr>
                                <td class="summary-label" style="font-size: 7px; color: #64748b;">
                                    Tasa Ref. (1 {{ $currency }} = {{ number_format((float)$order->exchange_rate, 4, ',', '.') }} {{ $order->base_currency }}):
                                </td>
                                <td class="summary-amount" style="font-size: 7.5px; color: #1e40af;">
                                    {{ $order->base_currency }} {{ number_format((float)$order->total * (float)$order->exchange_rate, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <table class="footer-table">
        <tr>
            <td style="text-align: left;">
                Orden de Venta · {{ $order->code }}
            </td>
            <td style="text-align: right;">
                Fecha y hora de impresión: {{ date('d/m/Y H:i:s') }}
            </td>
        </tr>
    </table>

</body>
</html>
