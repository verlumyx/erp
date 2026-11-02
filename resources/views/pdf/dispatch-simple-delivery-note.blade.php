<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <title>Nota de Entrega - {{ $dispatch->code }}</title>
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
            line-height: 1.35;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Header Section */
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
        .company-meta {
            font-size: 8px;
            color: #475569;
            margin-top: 2px;
            line-height: 1.25;
        }

        /* Box Right */
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
        .status-delivered {
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
            padding: 5px 6px;
            border: 1px solid #1e3a8a;
        }
        .items-table tbody td {
            padding: 4px 6px;
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

        .item-trace {
            font-size: 7px;
            color: #64748b;
            margin-top: 1.5px;
            line-height: 1.25;
        }

        /* Totals / Summary bar */
        .summary-bar-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .summary-bar-table td {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            font-size: 8px;
        }

        /* Notes Box */
        .notes-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            font-size: 7.5px;
            color: #334155;
            line-height: 1.3;
            margin-bottom: 10px;
        }

        /* Signatures Section */
        .signatures-container {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 48%;
            text-align: center;
            vertical-align: top;
            background-color: #fafbfc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 8px 10px 10px 10px;
        }
        .signature-space {
            height: 38px;
        }
        .signature-line {
            border-top: 1.2px solid #334155;
            width: 85%;
            margin: 0 auto 5px auto;
        }
        .signature-title {
            font-size: 8px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .signature-fields {
            margin-top: 6px;
            text-align: left;
            font-size: 7px;
            color: #475569;
            line-height: 1.4;
            border-top: 1px dotted #e2e8f0;
            padding-top: 4px;
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
        $statusKey = $dispatch->status;
        $statusLabels = [
            'draft' => 'Borrador',
            'confirmed' => 'Confirmado',
            'delivered' => 'Entregado',
            'cancelled' => 'Anulado',
        ];
        $statusLabel = $statusLabels[$statusKey] ?? strtoupper($statusKey);
        $statusBadgeClass = match($statusKey) {
            'confirmed' => 'status-confirmed',
            'delivered' => 'status-delivered',
            'cancelled' => 'status-cancelled',
            default => 'status-confirmed',
        };

        // Resolve recipient details
        $recipientName = '—';
        $recipientDoc = '—';
        $recipientPhone = '—';
        $recipientEmail = '—';

        if ($dispatch->recipient) {
            $recipientName = $dispatch->recipient->name;
            if (!empty($dispatch->recipient->legal_name) && $dispatch->recipient->legal_name !== $dispatch->recipient->name) {
                $recipientName .= ' (' . $dispatch->recipient->legal_name . ')';
            }

            if (!empty($dispatch->recipient->tax_number)) {
                $recipientDoc = $dispatch->recipient->tax_number;
            } elseif (!empty($dispatch->recipient->document_number)) {
                $docType = $dispatch->recipient->document_type ?? '';
                $recipientDoc = $docType ? "{$docType}-{$dispatch->recipient->document_number}" : (string) $dispatch->recipient->document_number;
            } elseif (!empty($dispatch->recipient->code)) {
                $recipientDoc = $dispatch->recipient->code;
            }

            $recipientPhone = $dispatch->recipient->phone ?? $dispatch->recipient->mobile ?? '—';
            $recipientEmail = $dispatch->recipient->email ?? '—';
        }

        if ($dispatch->recipient_type === 'warehouse' && $dispatch->recipient) {
            $recipientName = ($company->name ?? 'Misma Empresa') . ' — Bodega ' . ($dispatch->recipient->name ?? '');
            $recipientDoc = $company->rif ?? '—';
            $recipientPhone = $company->phone ?? '—';
        }

        // Resolve delivery address
        $deliveryAddress = null;
        if ($dispatch->clientAddress) {
            $parts = array_filter([
                $dispatch->clientAddress->address ?: $dispatch->clientAddress->name,
                $dispatch->clientAddress->city,
                $dispatch->clientAddress->state,
                $dispatch->clientAddress->country,
            ]);
            $deliveryAddress = implode(', ', $parts);
        } elseif ($dispatch->recipient && !empty($dispatch->recipient->address)) {
            $parts = array_filter([
                $dispatch->recipient->address,
                $dispatch->recipient->city ?? null,
                $dispatch->recipient->state ?? null,
                $dispatch->recipient->country ?? null,
            ]);
            $deliveryAddress = implode(', ', $parts);
        }
        if (empty($deliveryAddress)) {
            $deliveryAddress = $dispatch->client_address_name ?? '—';
        }

        // Resolve source document label
        $sourceDocLabel = 'Directo / Sin pedido previo';
        if ($dispatch->sourceable) {
            $sourceTypeName = match($dispatch->sourceable_type) {
                'sales_order' => 'Pedido de Venta',
                'transfer' => 'Traslado de Mercancía',
                'purchase_return' => 'Devolución a Proveedor',
                'sales_invoice' => 'Factura de Venta',
                default => 'Doc. Origen',
            };
            $sourceDocLabel = "{$sourceTypeName} #" . ($dispatch->sourceable->code ?? $dispatch->sourceable_id);
        } elseif (!empty($dispatch->sourceable_code)) {
            $sourceDocLabel = $dispatch->sourceable_code;
        }

        $activeLines = ($dispatch->lines ?? collect())->filter(fn($l) => $l->status === 'active')->sortBy('line_number');
    @endphp

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <!-- Company Info Left -->
            <td style="width: 58%; vertical-align: top;">
                <div class="company-name">{{ $company->name ?? config('app.name') }}</div>
                @if(!empty($company->fiscal_address) || !empty($company->address))
                    <div class="company-meta"><strong>Dirección:</strong> {{ $company->fiscal_address ?? $company->address }}</div>
                @endif
                @if(!empty($company->phone))
                    <div class="company-meta"><strong>Teléfono:</strong> {{ $company->phone }}</div>
                @endif
            </td>

            <!-- Order Box Right -->
            <td class="order-box">
                <div class="doc-title">NOTA DE ENTREGA</div>
                <table class="order-table">
                    <tr>
                        <td class="order-label">N° DE DESPACHO:</td>
                        <td class="order-value">{{ $dispatch->code }}</td>
                    </tr>
                    <tr>
                        <td class="order-label">FECHA EMISIÓN:</td>
                        <td class="order-value">{{ $dispatch->dispatch_date?->format('d/m/Y') ?? date('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="order-label">FECHA ENTREGA:</td>
                        <td class="order-value">{{ $dispatch->delivery_date?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="order-label">ESTADO:</td>
                        <td class="order-value">
                            <span class="status-badge {{ $statusBadgeClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Recipient & Dispatch Details Grid -->
    <table class="info-grid-table">
        <tr>
            <!-- Recipient Card -->
            <td class="info-card">
                <div class="card-header-title">DATOS DEL DESTINATARIO</div>
                <table class="data-row-table">
                    <tr>
                        <td class="data-label">Destinatario:</td>
                        <td class="data-value data-value-strong">{{ $recipientName }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Documento / C.I.:</td>
                        <td class="data-value data-value-strong">{{ $recipientDoc }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Dirección Entrega:</td>
                        <td class="data-value">{{ $deliveryAddress }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Teléfono:</td>
                        <td class="data-value">{{ $recipientPhone }}</td>
                    </tr>
                    @if($recipientEmail !== '—')
                        <tr>
                            <td class="data-label">Correo:</td>
                            <td class="data-value">{{ $recipientEmail }}</td>
                        </tr>
                    @endif
                </table>
            </td>

            <!-- Delivery Details Card -->
            <td class="info-card">
                <div class="card-header-title">DETALLES DEL TRASLADO / ENVÍO</div>
                <table class="data-row-table">
                    <tr>
                        <td class="data-label">Almacén Salida:</td>
                        <td class="data-value">{{ $dispatch->warehouse->name ?? 'Principal' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Doc. Origen:</td>
                        <td class="data-value">{{ $sourceDocLabel }}</td>
                    </tr>
                    @if(!empty($dispatch->driver?->name))
                        <tr>
                            <td class="data-label">Conductor:</td>
                            <td class="data-value">{{ $dispatch->driver->name }}</td>
                        </tr>
                    @endif
                    @if(!empty($dispatch->vehicle_plate))
                        <tr>
                            <td class="data-label">Vehículo / Placa:</td>
                            <td class="data-value">{{ $dispatch->vehicle_plate }}</td>
                        </tr>
                    @endif
                    @if(!empty($dispatch->carrier))
                        <tr>
                            <td class="data-label">Transportista:</td>
                            <td class="data-value">{{ $dispatch->carrier }}</td>
                        </tr>
                    @endif
                    @if(!empty($dispatch->tracking_number))
                        <tr>
                            <td class="data-label">Guía / Rastreo:</td>
                            <td class="data-value">{{ $dispatch->tracking_number }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Products Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 15%;">CÓDIGO</th>
                <th style="width: 45%;">DESCRIPCIÓN DEL ARTÍCULO</th>
                <th style="width: 15%;" class="text-right">CANTIDAD</th>
                <th style="width: 20%;" class="text-center">UNIDAD</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activeLines as $idx => $line)
                @php
                    $lineQty = (float) ($line->quantity ?? 0);
                    $lotNames = $line->lots->map(fn($l) => $l->lot?->lot_number)->filter()->all();
                    $serials = $line->serials->map(fn($s) => $s->serial?->serial_number)->filter()->all();
                @endphp
                <tr>
                    <td class="text-center font-mono">{{ $idx + 1 }}</td>
                    <td class="font-mono text-left">{{ $line->item->sku ?? $line->item->code ?? '—' }}</td>
                    <td class="text-left font-bold">
                        {{ $line->item->name ?? $line->notes ?? 'Producto' }}
                        @if(!empty($lotNames) || !empty($serials))
                            <div class="item-trace">
                                @if(!empty($lotNames))
                                    <span><strong>Lotes:</strong> {{ implode(', ', $lotNames) }}</span>
                                @endif
                                @if(!empty($serials))
                                    @if(!empty($lotNames)) &nbsp;|&nbsp; @endif
                                    <span><strong>Series:</strong> {{ implode(', ', $serials) }}</span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="text-right font-mono font-bold">{{ number_format($lineQty, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $line->measurementUnit->abbreviation ?? $line->measurementUnit->code ?? $line->measurementUnit->name ?? 'UND' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 10px; color: #64748b;">
                        Sin artículos registrados en el despacho.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Summary Bar -->
    <table class="summary-bar-table">
        <tr>
            <td style="width: 50%;">
                <strong>Total Artículos:</strong> {{ $activeLines->count() }}
            </td>
            <td style="width: 50%; text-align: right;">
                <strong>Total Unidades Despachadas:</strong> {{ number_format((float) ($dispatch->total_quantity ?? $activeLines->sum('quantity')), 2, ',', '.') }}
            </td>
        </tr>
    </table>

    <!-- Observations -->
    @if(!empty($dispatch->notes))
        <div class="notes-box">
            <strong>Observaciones:</strong><br>
            {{ $dispatch->notes }}
        </div>
    @endif

    <!-- Footer -->
    <table class="footer-table">
        <tr>
            <td style="text-align: left;">
                Nota de Entrega · {{ $dispatch->code }}
            </td>
            <td style="text-align: right;">
                Fecha y hora de impresión: {{ date('d/m/Y H:i:s') }}
            </td>
        </tr>
    </table>

</body>
</html>
