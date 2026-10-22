<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <title>Guía de Despacho / Orden de Entrega - {{ $dispatch->code }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 14mm 16mm 14mm 16mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9px;
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
            margin-bottom: 6px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .company-meta {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.3;
        }
        .doc-main-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
            text-align: right;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .doc-sub-title {
            font-size: 7.5px;
            font-weight: bold;
            color: #64748b;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 2px;
        }
        .tax-legend {
            font-size: 8px;
            font-weight: bold;
            color: #b91c1c;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            text-align: right;
            margin-top: 3px;
        }
        .control-number-container {
            font-size: 8px;
            color: #0f172a;
            margin-top: 3px;
            text-align: right;
        }
        .control-number-badge {
            font-size: 10px;
            font-weight: bold;
            color: #b91c1c;
            font-family: 'Courier New', Courier, monospace;
            letter-spacing: 0.5px;
        }
        .operation-badge {
            display: inline-block;
            font-size: 7px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .operation-sale {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .operation-nonsale {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .code-badge {
            display: inline-block;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-weight: bold;
            font-size: 11px;
            padding: 3px 9px;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }
        .status-badge {
            display: inline-block;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 2px 7px;
            border-radius: 3px;
            letter-spacing: 0.3px;
            margin-left: 4px;
        }
        .status-draft {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
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

        .header-divider {
            height: 2.5px;
            background-color: #2563eb;
            width: 100%;
            margin-top: 6px;
            margin-bottom: 10px;
            border-radius: 2px;
        }

        /* 2-Column Info Grid */
        .info-grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-left: -8px;
            margin-right: -8px;
            margin-bottom: 8px;
        }
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 7px 10px;
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
            padding-bottom: 3px;
            margin-bottom: 5px;
        }
        .data-row-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-row-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }
        .data-label {
            width: 32%;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.2px;
        }
        .data-value {
            width: 68%;
            font-size: 8.5px;
            color: #0f172a;
            font-weight: 500;
        }
        .data-value-strong {
            font-weight: bold;
        }

        /* KPI / Metrics strip */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-left: -6px;
            margin-right: -6px;
            margin-bottom: 9px;
        }
        .kpi-cell {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 5px 8px;
            text-align: center;
            vertical-align: middle;
            width: 25%;
        }
        .kpi-label {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            display: block;
            letter-spacing: 0.3px;
        }
        .kpi-val {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 1px;
            display: block;
        }
        .kpi-val-primary {
            color: #1e40af;
        }

        /* Section Title */
        .section-header {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e293b;
            background-color: #f1f5f9;
            padding: 4px 8px;
            border-left: 3px solid #2563eb;
            margin-top: 4px;
            margin-bottom: 6px;
            border-radius: 0 3px 3px 0;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 9px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 5px 6px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        .items-table td {
            padding: 4.5px 6px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 8px;
            color: #1e293b;
            vertical-align: top;
        }
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .item-code {
            font-weight: bold;
            color: #334155;
            font-size: 8px;
            white-space: nowrap;
        }
        .item-name {
            font-weight: bold;
            color: #0f172a;
            font-size: 8.5px;
        }
        .item-trace {
            font-size: 7px;
            color: #64748b;
            margin-top: 1.5px;
            line-height: 1.25;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }

        /* Notes Box */
        .notes-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #64748b;
            border-radius: 0 4px 4px 0;
            padding: 5px 8px;
            font-size: 8px;
            color: #334155;
            margin-bottom: 10px;
            line-height: 1.35;
        }
        .notes-title {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 2px;
        }

        /* Conformity text */
        .conformity-text {
            font-size: 7px;
            color: #64748b;
            text-align: justify;
            font-style: italic;
            margin-top: 6px;
            margin-bottom: 12px;
            line-height: 1.3;
            border-top: 1px dashed #cbd5e1;
            padding-top: 4px;
        }

        /* Signatures Section */
        .signatures-container {
            width: 100%;
            margin-top: 24px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 44%;
            text-align: center;
            vertical-align: top;
            background-color: #fafbfc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 8px 10px 10px 10px;
        }
        .signature-space {
            height: 40px;
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
        .signature-subtitle {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 2px;
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
            margin-top: 14px;
            padding-top: 5px;
            border-top: 1px dotted #cbd5e1;
            font-size: 7px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    @php
        // Resolve status label and badge class
        $statusKey = $dispatch->status;
        $statusLabels = [
            'draft' => 'Borrador',
            'confirmed' => 'Confirmado',
            'delivered' => 'Entregado',
            'cancelled' => 'Anulado',
        ];
        $statusLabel = $statusLabels[$statusKey] ?? strtoupper($statusKey);
        $statusBadgeClass = 'status-' . ($statusKey ?: 'draft');

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

        // Filter active lines
        $activeLines = ($dispatch->lines ?? collect())->filter(fn($l) => $l->status === 'active')->sortBy('line_number');
        $hasOrderedQty = $activeLines->some(fn($l) => !empty($l->sourceable?->quantity) || !empty($l->source_quantity));

        $isSale = method_exists($dispatch, 'isSale') ? $dispatch->isSale() : (bool) ($dispatch->is_sale ?? true);
        $reasonLabel = $dispatch->transfer_reason_label ?? ($isSale ? 'Venta de bienes' : 'Traslado sin efecto de venta');

        if ($dispatch->recipient_type === 'warehouse' && $dispatch->recipient) {
            $recipientName = ($company->name ?? 'Misma Empresa') . ' — Bodega ' . ($dispatch->recipient->name ?? '');
            $recipientDoc = $company->rif ?? '—';
            $recipientPhone = $company->phone ?? '—';
        }
    @endphp

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 53%; vertical-align: middle;">
                <div class="company-name">{{ $company->name ?? 'EMPRESA' }}</div>
                @if(!empty($company->rif))
                    <div class="company-meta"><strong>RIF:</strong> {{ $company->rif }}</div>
                @endif
                @if(!empty($company->fiscal_address) || !empty($company->address))
                    <div class="company-meta"><strong>Domicilio Fiscal:</strong> {{ $company->fiscal_address ?? $company->address }}</div>
                @endif
                @if(!empty($company->phone))
                    <div class="company-meta"><strong>Teléfono:</strong> {{ $company->phone }}</div>
                @endif
                @if(!empty($company->taxpayer_type))
                    <div class="company-meta">
                        <strong>Condición IVA:</strong>
                        {{ match($company->taxpayer_type) {
                            'formal' => 'CONTRIBUYENTE FORMAL',
                            'special' => 'CONTRIBUYENTE ESPECIAL',
                            default => 'CONTRIBUYENTE ORDINARIO',
                        } }}
                    </div>
                @endif
            </td>
            <td style="width: 47%; vertical-align: middle; text-align: right;">
                <div class="doc-main-title">Guía de Despacho</div>
                <div class="doc-sub-title">Orden de Entrega</div>
                <div class="tax-legend">SIN DERECHO A CRÉDITO FISCAL</div>
                <div style="margin-top: 4px;">
                    <span class="code-badge">{{ $dispatch->code }}</span>
                    <span class="status-badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                </div>
                <div class="control-number-container">
                    <strong style="color: #64748b; font-size: 8px;">N° DE CONTROL:</strong>
                    <span class="control-number-badge">{{ $dispatch->control_number ?? '00-00000000' }}</span>
                </div>
                <div class="company-meta" style="margin-top: 3px;">
                    <strong>Fecha Emisión:</strong> {{ $dispatch->dispatch_date?->format('d/m/Y') ?? date('d/m/Y') }}
                    @if($dispatch->delivery_date)
                        &nbsp;|&nbsp; <strong>Fecha Entrega:</strong> {{ $dispatch->delivery_date->format('d/m/Y') }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="header-divider"></div>

    <!-- 2-Column Info Grid -->
    <table class="info-grid-table">
        <tr>
            <!-- Destinatario Card -->
            <td class="info-card">
                <div class="card-header-title">Datos del Destinatario</div>
                <table class="data-row-table">
                    <tr>
                        <td class="data-label">Destinatario:</td>
                        <td class="data-value data-value-strong">{{ $recipientName }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">RIF / C.I.:</td>
                        <td class="data-value">{{ $recipientDoc }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Teléfono:</td>
                        <td class="data-value">{{ $recipientPhone }}</td>
                    </tr>
                    @if($recipientEmail !== '—')
                        <tr>
                            <td class="data-label">Email:</td>
                            <td class="data-value">{{ $recipientEmail }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="data-label">Dirección:</td>
                        <td class="data-value">{{ $deliveryAddress }}</td>
                    </tr>
                </table>
            </td>

            <!-- Logística y Transporte Card -->
            <td class="info-card">
                <div class="card-header-title">Logística y Transporte</div>
                <table class="data-row-table">
                    <tr>
                        <td class="data-label">Almacén Origen:</td>
                        <td class="data-value data-value-strong">
                            {{ $dispatch->warehouse?->name ?? '—' }}
                            @if($dispatch->warehouse?->code)
                                <span style="color: #64748b; font-weight: normal;">({{ $dispatch->warehouse->code }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="data-label">Doc. Origen:</td>
                        <td class="data-value">{{ $sourceDocLabel }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Operación:</td>
                        <td class="data-value">
                            <span class="operation-badge {{ $isSale ? 'operation-sale' : 'operation-nonsale' }}">
                                {{ $isSale ? 'Venta de Bienes' : 'Traslado (No Venta)' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="data-label">Motivo Traslado:</td>
                        <td class="data-value data-value-strong">
                            {{ $reasonLabel }}
                        </td>
                    </tr>
                    @if(!empty($dispatch->transfer_reason_detail))
                        <tr>
                            <td class="data-label">Justificación:</td>
                            <td class="data-value" style="font-style: italic;">
                                {{ $dispatch->transfer_reason_detail }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="data-label">Conductor:</td>
                        <td class="data-value">{{ $dispatch->driver?->name ?? $dispatch->driver_name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Vehículo / Placa:</td>
                        <td class="data-value">{{ $dispatch->vehicle_plate ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Transportista:</td>
                        <td class="data-value">
                            {{ $dispatch->carrier ?: 'Transporte Interno' }}
                            @if($dispatch->tracking_number)
                                &nbsp;·&nbsp; Guía: {{ $dispatch->tracking_number }}
                            @endif
                        </td>
                    </tr>
                    @if($dispatch->deliveryRoute)
                        <tr>
                            <td class="data-label">Ruta:</td>
                            <td class="data-value">
                                {{ $dispatch->deliveryRoute->code ? $dispatch->deliveryRoute->code . ' - ' : '' }}{{ $dispatch->deliveryRoute->name }}
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- KPI / Metrics strip -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-cell">
                <span class="kpi-label">Unidades Despachadas</span>
                <span class="kpi-val kpi-val-primary">{{ number_format((float) $dispatch->total_quantity, 2, ',', '.') }}</span>
            </td>
            <td class="kpi-cell">
                <span class="kpi-label">Renglones / Ítems</span>
                <span class="kpi-val">{{ $activeLines->count() }}</span>
            </td>
            <td class="kpi-cell">
                <span class="kpi-label">Peso Total</span>
                <span class="kpi-val">
                    {{ (float)$dispatch->total_weight > 0 ? number_format((float)$dispatch->total_weight, 2, ',', '.') . ' kg' : '—' }}
                </span>
            </td>
            <td class="kpi-cell">
                <span class="kpi-label">Volumen Total</span>
                <span class="kpi-val">
                    {{ (float)$dispatch->total_volume > 0 ? number_format((float)$dispatch->total_volume, 2, ',', '.') . ' m³' : '—' }}
                </span>
            </td>
        </tr>
    </table>

    <!-- Aviso Legal de Operación -->
    @if(!$isSale)
        <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-left: 3.5px solid #d97706; border-radius: 4px; padding: 6px 10px; font-size: 8px; color: #92400e; margin-bottom: 9px; line-height: 1.35;">
            <strong style="text-transform: uppercase;">Aviso Legal — Traslado que no representa venta (Arts. 20 y 21 Providencia SENIAT N° SNAT/2011/00071):</strong><br>
            Este documento ampara única y exclusivamente el traslado y circulación física de los bienes muebles descritos por motivo de <strong>{{ strtolower($reasonLabel) }}</strong>{{ !empty($dispatch->transfer_reason_detail) ? ' (' . $dispatch->transfer_reason_detail . ')' : '' }}. La presente operación no constituye venta ni genera derecho a crédito fiscal. En virtud del Art. 21, se omite el precio comercial.
        </div>
    @else
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-left: 3.5px solid #16a34a; border-radius: 4px; padding: 5px 10px; font-size: 7.5px; color: #166534; margin-bottom: 9px; line-height: 1.35;">
            <strong>OPERACIÓN DE VENTA (Art. 20 Providencia SENIAT N° SNAT/2011/00071):</strong>
            La respectiva factura comercial deberá emitirse dentro del mismo período de imposición mensual, haciendo referencia expresa a la presente Guía de Despacho / Orden de Entrega.
        </div>
    @endif

    <!-- Detalle de Productos Despachados -->
    <div class="section-header">Artículos Despachados</div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 14%;">Código</th>
                <th style="width: {{ $hasOrderedQty ? '35%' : '44%' }};">Descripción del Artículo</th>
                <th style="width: 9%;" class="text-center">U.M.</th>
                <th style="width: 13%;">Ubicación</th>
                <th style="width: 13%;">Lote / Series</th>
                @if($hasOrderedQty)
                    <th style="width: 6%;" class="text-right">Ped.</th>
                @endif
                <th style="width: 6%;" class="text-right">Cant.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activeLines as $line)
                @php
                    $lineLots = ($line->lots ?? collect())->filter(fn($lt) => $lt->status === 'active');
                    $lineSerials = ($line->serials ?? collect())->filter(fn($s) => $s->status === 'active');
                    $orderedQty = $line->sourceable?->quantity ?? $line->source_quantity ?? null;
                @endphp
                <tr>
                    <td class="text-center font-mono" style="color: #64748b;">{{ $line->line_number }}</td>
                    <td class="item-code font-mono">{{ $line->item?->code ?? $line->item_code ?? '—' }}</td>
                    <td>
                        <div class="item-name">{{ $line->item?->name ?? $line->item_name ?? 'Artículo' }}</div>
                        @if($line->notes)
                            <div class="item-trace" style="font-style: italic;">Obs: {{ $line->notes }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $line->measurementUnit?->name ?? $line->measurement_unit_name ?? 'UND' }}</td>
                    <td>{{ $line->location?->name ?? $line->location_name ?? '—' }}</td>
                    <td>
                        @if($lineLots->isNotEmpty())
                            @foreach($lineLots as $lot)
                                <div class="item-trace">
                                    <strong>Lote:</strong> {{ $lot->lot?->lot_number ?? $lot->lot_number ?? 'S/L' }} ({{ (float)$lot->quantity }})
                                </div>
                            @endforeach
                        @endif
                        @if($lineSerials->isNotEmpty())
                            <div class="item-trace">
                                <strong>Series ({{ $lineSerials->count() }}):</strong>
                                {{ $lineSerials->map(fn($s) => $s->serial?->serial_number ?? $s->serial_number ?? $s->serial_id)->take(4)->join(', ') }}
                                @if($lineSerials->count() > 4)
                                    ... (+{{ $lineSerials->count() - 4 }})
                                @endif
                            </div>
                        @endif
                        @if($lineLots->isEmpty() && $lineSerials->isEmpty())
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>
                    @if($hasOrderedQty)
                        <td class="text-right font-mono" style="color: #64748b;">
                            {{ $orderedQty !== null ? number_format((float)$orderedQty, 2, ',', '.') : '—' }}
                        </td>
                    @endif
                    <td class="text-right font-mono" style="font-weight: bold; color: #0f172a;">
                        {{ number_format((float)$line->quantity, 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $hasOrderedQty ? 8 : 7 }}" class="text-center" style="padding: 14px; color: #64748b;">
                        Este despacho no contiene líneas activas.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Observaciones -->
    @if(!empty($dispatch->notes))
        <div class="notes-box">
            <div class="notes-title">Observaciones / Instrucciones de Entrega:</div>
            <div>{{ $dispatch->notes }}</div>
        </div>
    @endif

    <!-- Declaración de Conformidad -->
    <div class="conformity-text">
        <strong>Declaración de recepción y traslado:</strong> El receptor confirma haber recibido conforme la totalidad de los artículos detallados en la presente Guía de Despacho / Orden de Entrega, en las cantidades descritas y en perfecto estado físico y embalaje. Documento emitido sin derecho a crédito fiscal (Art. 21 Providencia Administrativa SENIAT N° SNAT/2011/00071).
    </div>

    <!-- Firmas de Entrega y Recepción -->
    <table class="signatures-container">
        <tr>
            <td class="signature-box">
                <div class="signature-space"></div>
                <div class="signature-line"></div>
                <div class="signature-title">Entregado por / Conductor</div>
                <div class="signature-subtitle">
                    {{ $dispatch->driver?->name ?? $dispatch->driver_name ?? 'Firma del Transportista / Despachador' }}
                </div>
                <div class="signature-fields">
                    <div><strong>Nombre:</strong> {{ $dispatch->driver?->name ?? $dispatch->driver_name ?? '__________________________________' }}</div>
                    <div><strong>C.I. / Doc:</strong> ___________________________________</div>
                    <div><strong>Fecha:</strong> ______ / ______ / 20_____ &nbsp; <strong>Hora:</strong> ________</div>
                </div>
            </td>
            <td style="width: 12%;"></td>
            <td class="signature-box">
                <div class="signature-space"></div>
                <div class="signature-line"></div>
                <div class="signature-title">Recibido Conforme / Destinatario</div>
                <div class="signature-subtitle">
                    Firma y Sello de Recepción
                </div>
                <div class="signature-fields">
                    <div><strong>Nombre:</strong> ___________________________________</div>
                    <div><strong>C.I. / RIF:</strong> ___________________________________</div>
                    <div><strong>Fecha:</strong> ______ / ______ / 20_____ &nbsp; <strong>Hora:</strong> ________</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <table class="footer-table">
        <tr>
            <td style="text-align: left;">
                Guía de Despacho / Orden de Entrega · Providencia Administrativa SENIAT N° SNAT/2011/00071 (Art. 20 y 21) · <strong>SIN DERECHO A CRÉDITO FISCAL</strong>
            </td>
            <td style="text-align: right;">
                Fecha y hora de impresión: {{ date('d/m/Y H:i:s') }}
            </td>
        </tr>
    </table>

</body>
</html>
