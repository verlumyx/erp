<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <title>Plan de Carga / Hoja de Ruta - {{ $plan->code }}</title>
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
            margin-bottom: 6px;
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
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 2px;
        }
        .doc-code-badge {
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
            font-family: 'Courier New', Courier, monospace;
            text-align: right;
            margin-top: 2px;
        }

        .status-badge {
            display: inline-block;
            font-size: 7.5px;
            font-weight: bold;
            padding: 2px 7px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 3px;
        }
        .status-draft { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .status-in_transit { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .status-confirmed { background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .status-cancelled { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .divider {
            height: 2px;
            background-color: #2563eb;
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
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 6px 9px;
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
            padding: 1.5px 0;
            vertical-align: top;
        }
        .data-label {
            width: 35%;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
        }
        .data-value {
            width: 65%;
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
            border-spacing: 5px 0;
            margin-left: -5px;
            margin-right: -5px;
            margin-bottom: 9px;
        }
        .kpi-cell {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 5px 7px;
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
        .kpi-val-primary { color: #1e40af; }
        .kpi-val-success { color: #059669; }

        /* Section Title */
        .section-header {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e40af;
            background-color: #eff6ff;
            border: 1px solid #dbeafe;
            padding: 4px 8px;
            border-radius: 4px;
            margin-top: 8px;
            margin-bottom: 5px;
        }

        /* Tables */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 8px;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7px;
            letter-spacing: 0.4px;
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .items-table td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .text-bold { font-weight: bold; }
        .text-muted { color: #64748b; font-size: 7.5px; }

        .sec-badge {
            display: inline-block;
            background-color: #eff6ff;
            color: #1e40af;
            font-weight: bold;
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 8px;
        }

        /* Signatures */
        .signatures-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 12px 0;
            margin-left: -12px;
            margin-right: -12px;
            margin-top: 14px;
        }
        .signature-cell {
            width: 33.33%;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 6px 8px;
            text-align: center;
            vertical-align: top;
            background-color: #ffffff;
        }
        .signature-line {
            height: 38px;
            border-bottom: 1px dashed #94a3b8;
            margin-bottom: 4px;
        }
        .signature-title {
            font-size: 7.5px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .signature-meta {
            font-size: 7px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Footer */
        .footer-table {
            width: 100%;
            margin-top: 10px;
            padding-top: 4px;
            border-top: 1px dotted #cbd5e1;
            font-size: 7px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    @php
        $statusKey = $plan->status;
        $statusLabels = [
            'draft' => 'En proceso / Borrador',
            'in_transit' => 'En tránsito',
            'confirmed' => 'Confirmado / Entregado',
            'cancelled' => 'Anulado',
        ];
        $statusLabel = $statusLabels[$statusKey] ?? strtoupper($statusKey);
        $statusClass = 'status-' . $statusKey;

        $weightCap = (float) ($plan->vehicle?->capacity_weight ?? 0);
        $volumeCap = (float) ($plan->vehicle?->capacity_volume ?? 0);
        $weightPct = $weightCap > 0 ? round(($plan->total_weight / $weightCap) * 100, 1) : 0;
        $volumePct = $volumeCap > 0 ? round(($plan->total_volume / $volumeCap) * 100, 1) : 0;
    @endphp

    <!-- Cabecera Empresa y Documento -->
    <table class="header-table">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                <div class="company-name">{{ $company->name ?? 'EMPRESA' }}</div>
                <div class="company-meta">
                    @if(!empty($company->tax_number))
                        <strong>RIF/ID Fiscal:</strong> {{ $company->tax_number }}<br>
                    @endif
                    @if(!empty($company->address))
                        <strong>Dirección:</strong> {{ $company->address }}<br>
                    @endif
                    @if(!empty($company->phone))
                        <strong>Teléfono:</strong> {{ $company->phone }}
                    @endif
                </div>
            </td>
            <td style="width: 42%; vertical-align: top; text-align: right;">
                <div class="doc-main-title">PLAN DE CARGA</div>
                <div class="doc-sub-title">Hoja de Ruta y Manifiesto de Despacho</div>
                <div class="doc-code-badge">{{ $plan->code }}</div>
                <div>
                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
                <div style="font-size: 8px; color: #64748b; margin-top: 3px;">
                    <strong>Fecha programada:</strong> {{ $plan->plan_date ? \Carbon\Carbon::parse($plan->plan_date)->format('d/m/Y') : '—' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- KPI Metrics -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-cell">
                <span class="kpi-label">Total Entregas</span>
                <span class="kpi-val kpi-val-primary">{{ $plan->total_dispatches }} despachos</span>
            </td>
            <td class="kpi-cell">
                <span class="kpi-label">Peso Total Cargado</span>
                <span class="kpi-val">{{ number_format($plan->total_weight, 2, ',', '.') }} kg</span>
                <span style="font-size: 6.5px; color: #64748b;">{{ $weightCap > 0 ? "{$weightPct}% de {$weightCap} kg" : 'Sin límite' }}</span>
            </td>
            <td class="kpi-cell">
                <span class="kpi-label">Volumen Total</span>
                <span class="kpi-val">{{ number_format($plan->total_volume, 3, ',', '.') }} m³</span>
                <span style="font-size: 6.5px; color: #64748b;">{{ $volumeCap > 0 ? "{$volumePct}% de {$volumeCap} m³" : 'Sin límite' }}</span>
            </td>
            <td class="kpi-cell">
                <span class="kpi-label">Ruta / Flete</span>
                <span class="kpi-val kpi-val-primary" style="font-size: 9.5px;">{{ $plan->route?->name ?? '—' }}</span>
            </td>
        </tr>
    </table>

    <!-- Info Grid: Camión y Personal -->
    <table class="info-grid-table">
        <tr>
            <td class="info-card">
                <div class="card-header-title">Vehículo de Reparto y Ocupación</div>
                <table class="data-row-table">
                    <tr>
                        <td class="data-label">Placa:</td>
                        <td class="data-value data-value-strong">{{ $plan->vehicle?->plate ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Vehículo:</td>
                        <td class="data-value">{{ $plan->vehicle?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Capacidades:</td>
                        <td class="data-value">
                            {{ number_format($weightCap, 0, ',', '.') }} kg | {{ number_format($volumeCap, 2, ',', '.') }} m³
                        </td>
                    </tr>
                    <tr>
                        <td class="data-label">Bodega salida:</td>
                        <td class="data-value">{{ $plan->warehouse?->name ?? 'Bodega general' }}</td>
                    </tr>
                </table>
            </td>
            <td class="info-card">
                <div class="card-header-title">Personal Asignado y Logística</div>
                <table class="data-row-table">
                    <tr>
                        <td class="data-label">Conductor:</td>
                        <td class="data-value data-value-strong">{{ $plan->driver_name }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Ayudante(s):</td>
                        <td class="data-value">{{ $plan->helper_name ?? 'Sin ayudante registrado' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Ruta comercial:</td>
                        <td class="data-value">{{ $plan->route?->code ? $plan->route->code . ' - ' : '' }}{{ $plan->route?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="data-label">Planificador:</td>
                        <td class="data-value">{{ $plan->creator?->name ?? '—' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if(!empty($plan->description))
        <div style="font-size: 7.5px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 4px 8px; margin-bottom: 7px;">
            <strong style="color: #64748b;">Observaciones especiales del viaje:</strong> {{ $plan->description }}
        </div>
    @endif

    <!-- Sección 1: Recorrido y Detalle de Despachos -->
    <div class="section-header">1. Hoja de Ruta — Recorrido y Despachos a Entregar ({{ count($plan->planDispatches) }})</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">Sec.</th>
                <th style="width: 65px;">N° Despacho</th>
                <th style="width: 75px;">Factura / Pedido</th>
                <th style="width: 130px;">Cliente / Destinatario</th>
                <th>Dirección de Entrega</th>
                <th style="width: 50px;" class="text-right">Peso (kg)</th>
                <th style="width: 45px;" class="text-right">Vol (m³)</th>
                <th style="width: 60px;" class="text-center">Firma Recibido</th>
            </tr>
        </thead>
        <tbody>
            @forelse($plan->planDispatches as $idx => $planDispatch)
                @php
                    $d = $planDispatch->dispatch;
                    $recipientName = $d?->recipient?->name ?? '—';
                    $address = $d?->clientAddress?->address ?? $d?->clientAddress?->name ?? '—';
                    $invoiceCode = $d?->sourceable_type === 'sales_invoice' ? $d->sourceable?->code : null;
                    $orderCode = $d?->sourceable_type === 'sales_order' ? $d->sourceable?->code : null;
                @endphp
                <tr>
                    <td class="text-center">
                        <span class="sec-badge">{{ $planDispatch->sequence ?: ($idx + 1) }}</span>
                    </td>
                    <td>
                        <div class="text-bold">{{ $d?->code ?? '—' }}</div>
                        <div class="text-muted">{{ $d?->dispatch_date ? \Carbon\Carbon::parse($d->dispatch_date)->format('d/m/Y') : '' }}</div>
                    </td>
                    <td>
                        @if($invoiceCode)
                            <div style="color: #047857; font-weight: bold;">{{ $invoiceCode }}</div>
                        @endif
                        @if($orderCode)
                            <div class="text-muted">Ped: {{ $orderCode }}</div>
                        @endif
                        @if(!$invoiceCode && !$orderCode)
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="text-bold">{{ $recipientName }}</div>
                        @if(!empty($d?->recipient?->tax_number))
                            <div class="text-muted">{{ $d->recipient->tax_number }}</div>
                        @endif
                    </td>
                    <td style="font-size: 7.5px;">{{ $address }}</td>
                    <td class="text-right text-bold">{{ number_format($d?->total_weight ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($d?->total_volume ?? 0, 3, ',', '.') }}</td>
                    <td style="border-bottom: 1px dotted #94a3b8; height: 20px;"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted" style="padding: 12px;">
                        No hay despachos vinculados a este plan de carga.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="5" class="text-right">TOTAL CONSOLIDADO EN RUTA:</td>
                <td class="text-right">{{ number_format($plan->total_weight, 2, ',', '.') }} kg</td>
                <td class="text-right">{{ number_format($plan->total_volume, 3, ',', '.') }} m³</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Sección 2: Consolidado de Picking de Almacén (Productos a Montar en el Camión) -->
    @if(!empty($consolidatedItems))
        <div class="section-header" style="margin-top: 10px;">2. Carga Consolidada — Resumen de Picking para Carga del Camión ({{ count($consolidatedItems) }} productos)</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">#</th>
                    <th style="width: 80px;">Código / SKU</th>
                    <th>Descripción del Producto</th>
                    <th style="width: 70px;" class="text-right">Cantidad Total</th>
                    <th style="width: 50px;" class="text-center">Unidad</th>
                    <th style="width: 60px;" class="text-center">Check Almacén</th>
                </tr>
            </thead>
            <tbody>
                @foreach($consolidatedItems as $i => $item)
                    <tr>
                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                        <td class="text-bold">{{ $item['sku'] }}</td>
                        <td>{{ $item['name'] }}</td>
                        <td class="text-right text-bold" style="font-size: 9px; color: #1e40af;">
                            {{ number_format($item['quantity'], 2, ',', '.') }}
                        </td>
                        <td class="text-center text-muted">{{ $item['unit'] }}</td>
                        <td class="text-center" style="font-size: 11px; color: #cbd5e1;">[ &nbsp; ]</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Firmas de Responsabilidad -->
    <table class="signatures-table">
        <tr>
            <td class="signature-cell">
                <div class="signature-line"></div>
                <div class="signature-title">Despachador de Almacén</div>
                <div class="signature-meta">Revisó carga y entregó al camión</div>
            </td>
            <td class="signature-cell">
                <div class="signature-line"></div>
                <div class="signature-title">Conductor / Chofer</div>
                <div class="signature-meta">{{ $plan->driver_name }} (Recibió conforme)</div>
            </td>
            <td class="signature-cell">
                <div class="signature-line"></div>
                <div class="signature-title">Supervisor de Logística</div>
                <div class="signature-meta">Autorizó salida de viaje</div>
            </td>
        </tr>
    </table>

    <!-- Pie de Página -->
    <table class="footer-table">
        <tr>
            <td style="width: 50%;">
                ERP Logística • Plan de Carga {{ $plan->code }} • {{ $company->name ?? '' }}
            </td>
            <td style="width: 50%; text-align: right;">
                Generado el {{ now()->format('d/m/Y H:i:s') }}
            </td>
        </tr>
    </table>

</body>
</html>
