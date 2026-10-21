<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $report['type'] === 'Z' ? 'Corte Z' : 'Corte X' }} - {{ $report['session']['code'] }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 12mm 14mm 14mm 14mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            line-height: 1.35;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-meta {
            font-size: 9px;
            color: #4b5563;
            margin-top: 2px;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
            text-align: right;
            letter-spacing: 0.5px;
        }
        .report-subtitle {
            font-size: 9px;
            font-weight: 600;
            color: #6b7280;
            text-align: right;
            text-transform: uppercase;
        }
        .session-code-badge {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
            font-weight: bold;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 4px;
            margin-top: 4px;
            float: right;
        }

        /* Section Containers */
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #374151;
            background-color: #f3f4f6;
            padding: 4px 8px;
            border-left: 3px solid #2563eb;
            margin-top: 10px;
            margin-bottom: 6px;
        }

        /* Metadata Grid Table */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
        .meta-table td {
            padding: 6px 10px;
            vertical-align: top;
            border-right: 1px solid #f3f4f6;
        }
        .meta-table td:last-child {
            border-right: none;
        }
        .meta-label {
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
            color: #6b7280;
            display: block;
            margin-bottom: 2px;
        }
        .meta-value {
            font-size: 10px;
            font-weight: 600;
            color: #111827;
        }

        /* KPI and Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 8px;
            text-align: left;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #d1d5db;
        }
        .data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 9.5px;
        }
        .data-table tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 600;
        }
        .font-bold {
            font-weight: bold;
        }

        /* KPI Boxes Table (4 columns) */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin-bottom: 8px;
        }
        .kpi-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 6px 8px;
            text-align: left;
            width: 25%;
        }
        .kpi-box-highlight {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
        }
        .kpi-title {
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
            color: #6b7280;
            display: block;
        }
        .kpi-value {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin-top: 2px;
            display: block;
        }
        .kpi-value-highlight {
            color: #1d4ed8;
            font-size: 14px;
        }

        /* 2-Column Split Table */
        .split-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .split-col {
            width: 48.5%;
            vertical-align: top;
        }
        .split-space {
            width: 3%;
        }

        .audit-card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background-color: #ffffff;
            overflow: hidden;
        }
        .audit-header {
            padding: 5px 8px;
            background-color: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
            font-weight: bold;
            font-size: 9.5px;
        }
        .audit-row-table {
            width: 100%;
            border-collapse: collapse;
        }
        .audit-row-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 9px;
        }
        .audit-row-table tr:last-child td {
            border-bottom: none;
        }

        .badge-exact {
            color: #065f46;
            background-color: #d1fae5;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        .badge-surplus {
            color: #1e40af;
            background-color: #dbeafe;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        .badge-deficit {
            color: #991b1b;
            background-color: #fee2e2;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }

        /* Signatures */
        .signature-table {
            width: 100%;
            margin-top: 35px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-cell {
            width: 45%;
            text-align: center;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1px solid #374151;
            width: 70%;
            margin: 0 auto 4px auto;
        }
        .signature-role {
            font-size: 9px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
        }
        .signature-name {
            font-size: 8px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* Footer */
        .footer-note {
            margin-top: 20px;
            padding-top: 6px;
            border-top: 1px dotted #d1d5db;
            font-size: 7.5px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="vertical-align: top; width: 60%;">
                <div class="company-name">{{ $report['company']['name'] ?: 'EMPRESA' }}</div>
                @if(!empty($report['company']['tax_number']))
                    <div class="company-meta"><strong>RIF / NIT:</strong> {{ $report['company']['tax_number'] }}</div>
                @endif
                @if(!empty($report['company']['address']))
                    <div class="company-meta"><strong>Dirección:</strong> {{ $report['company']['address'] }}</div>
                @endif
            </td>
            <td style="vertical-align: top; width: 40%; text-align: right;">
                <div class="report-title">
                    {{ $report['type'] === 'Z' ? 'Corte de Caja (Corte Z)' : 'Lectura Parcial (Corte X)' }}
                </div>
                <div class="report-subtitle">
                    {{ $report['type'] === 'Z' ? 'Comprobante de Cierre Definitivo' : 'Arqueo en Curso' }}
                </div>
                <div>
                    <span class="session-code-badge">{{ $report['session']['code'] }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Metadata Section -->
    <table class="meta-table">
        <tr>
            <td style="width: 25%;">
                <span class="meta-label">Caja / Terminal</span>
                <span class="meta-value">{{ $report['session']['register_name'] ?: 'Caja Principal' }}</span>
                @if(!empty($report['session']['warehouse_name']))
                    <div style="font-size: 8px; color: #6b7280;">({{ $report['session']['warehouse_name'] }})</div>
                @endif
            </td>
            <td style="width: 25%;">
                <span class="meta-label">Cajero Responsable</span>
                <span class="meta-value">{{ $report['session']['cashier_name'] ?: 'N/A' }}</span>
                @if(!empty($report['session']['cashier_email']))
                    <div style="font-size: 8px; color: #6b7280;">{{ $report['session']['cashier_email'] }}</div>
                @endif
            </td>
            <td style="width: 25%;">
                <span class="meta-label">Apertura de Turno</span>
                <span class="meta-value font-mono">{{ $report['session']['opened_at'] }}</span>
                @if(!empty($report['session']['supervisor_name']))
                    <div style="font-size: 8px; color: #6b7280; margin-top: 2px;">
                        <strong>Supervisor:</strong> {{ $report['session']['supervisor_name'] }}
                    </div>
                @endif
            </td>
            <td style="width: 25%;">
                <span class="meta-label">Cierre de Turno</span>
                <span class="meta-value font-mono">
                    {{ $report['session']['closed_at'] ?: 'En curso (Abierto)' }}
                </span>
                <div style="font-size: 8px; color: #2563eb; margin-top: 2px; font-weight: bold; text-transform: uppercase;">
                    Estado: {{ $report['session']['status'] }}
                </div>
            </td>
        </tr>
    </table>

    <!-- 1. Resumen de Operaciones -->
    <div class="section-title">1. Resumen de Operaciones</div>
    <table class="kpi-table">
        <tr>
            <td class="kpi-box">
                <span class="kpi-title">Ventas Realizadas</span>
                <span class="kpi-value font-mono">{{ $report['summary']['sales_count'] }}</span>
                @if($report['summary']['cancelled_sales_count'] > 0)
                    <span style="font-size: 7.5px; color: #dc2626;">({{ $report['summary']['cancelled_sales_count'] }} anuladas)</span>
                @endif
            </td>
            <td class="kpi-box">
                <span class="kpi-title">Ticket Promedio</span>
                <span class="kpi-value font-mono">$ {{ number_format($report['summary']['average_ticket'], 2) }}</span>
            </td>
            <td class="kpi-box">
                <span class="kpi-title">Base Imponible / IVA</span>
                <span class="kpi-value font-mono" style="font-size: 11px;">
                    $ {{ number_format($report['summary']['taxable_base'], 2) }}
                </span>
                <span style="font-size: 7.5px; color: #6b7280;">IVA: $ {{ number_format($report['summary']['total_tax'], 2) }}</span>
            </td>
            <td class="kpi-box kpi-box-highlight">
                <span class="kpi-title" style="color: #1e40af;">Total Facturado Neto</span>
                <span class="kpi-value kpi-value-highlight font-mono">
                    $ {{ number_format($report['summary']['total_net'], 2) }}
                </span>
                @if($report['summary']['discounts'] > 0)
                    <span style="font-size: 7.5px; color: #4b5563;">Dctos: -$ {{ number_format($report['summary']['discounts'], 2) }}</span>
                @endif
            </td>
        </tr>
    </table>

    <!-- 2. Cobros por Medio de Pago -->
    <div class="section-title">2. Cobros por Medio de Pago</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Efectivo USD</th>
                <th style="width: 25%;">Efectivo VES</th>
                <th style="width: 25%;">Punto / Débito (Bs)</th>
                <th style="width: 25%;">Pago Móvil (Bs)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-mono font-bold">$ {{ number_format($report['payments']['cash_usd'], 2) }}</td>
                <td class="font-mono font-bold">Bs. {{ number_format($report['payments']['cash_ves'], 2) }}</td>
                <td class="font-mono font-bold">Bs. {{ number_format($report['payments']['card_ves'], 2) }}</td>
                <td class="font-mono font-bold">Bs. {{ number_format($report['payments']['mobile_ves'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="data-table" style="margin-top: -5px;">
        <thead>
            <tr>
                <th style="width: 25%;">Transferencia / Zelle ($)</th>
                @if(!empty($report['payments']['biopago_ves']) && $report['payments']['biopago_ves'] > 0)
                    <th style="width: 25%;">Biopago (Bs)</th>
                @else
                    <th style="width: 25%; color: #9ca3af;">Biopago (Bs)</th>
                @endif

                @if(!empty($report['payments']['transfer_ves']) && $report['payments']['transfer_ves'] > 0)
                    <th style="width: 25%;">Transferencia (Bs)</th>
                @else
                    <th style="width: 25%; color: #9ca3af;">Transferencia (Bs)</th>
                @endif

                @if(!empty($report['payments']['other']) && $report['payments']['other'] > 0)
                    <th style="width: 25%;">Otros Métodos ($)</th>
                @else
                    <th style="width: 25%; color: #9ca3af;">Otros Métodos ($)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-mono font-bold">$ {{ number_format($report['payments']['transfer_usd'] ?? 0, 2) }}</td>
                <td class="font-mono font-bold">Bs. {{ number_format($report['payments']['biopago_ves'] ?? 0, 2) }}</td>
                <td class="font-mono font-bold">Bs. {{ number_format($report['payments']['transfer_ves'] ?? 0, 2) }}</td>
                <td class="font-mono font-bold">$ {{ number_format($report['payments']['other'] ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- 3. Conciliación y Auditoría de Efectivo (2 Columns) -->
    <div class="section-title">3. Auditoría y Conciliación de Efectivo en Gaveta</div>
    <table class="split-table">
        <tr>
            <!-- USD Audit -->
            <td class="split-col">
                <div class="audit-card">
                    <div class="audit-header">
                        <table style="width: 100%;">
                            <tr>
                                <td style="font-weight: bold; color: #111827;">EFECTIVO DÓLARES (USD)</td>
                                <td style="text-align: right;">
                                    @if($report['audit_usd']['status'] === 'exact')
                                        <span class="badge-exact">Cuadrado</span>
                                    @elseif($report['audit_usd']['status'] === 'surplus')
                                        <span class="badge-surplus">Sobrante</span>
                                    @elseif($report['audit_usd']['status'] === 'deficit')
                                        <span class="badge-deficit">Faltante</span>
                                    @else
                                        <span style="color: #6b7280; font-size: 8px;">Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <table class="audit-row-table">
                        <tr>
                            <td>(+) Fondo Inicial de Apertura:</td>
                            <td class="text-right font-mono font-bold">$ {{ number_format($report['audit_usd']['opening_cash'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>(+) Cobros por Ventas en Efectivo:</td>
                            <td class="text-right font-mono font-bold">$ {{ number_format($report['audit_usd']['sales_cash'], 2) }}</td>
                        </tr>
                        @if($report['audit_usd']['cash_in'] > 0)
                        <tr>
                            <td style="color: #059669;">(+) Entradas de Efectivo:</td>
                            <td class="text-right font-mono font-bold" style="color: #059669;">+$ {{ number_format($report['audit_usd']['cash_in'], 2) }}</td>
                        </tr>
                        @endif
                        @if($report['audit_usd']['cash_out'] > 0)
                        <tr>
                            <td style="color: #dc2626;">(-) Retiros / Sangrías de Caja:</td>
                            <td class="text-right font-mono font-bold" style="color: #dc2626;">-$ {{ number_format($report['audit_usd']['cash_out'], 2) }}</td>
                        </tr>
                        @endif
                        <tr style="background-color: #f9fafb; font-weight: bold;">
                            <td>(=) Total Esperado en Sistema:</td>
                            <td class="text-right font-mono" style="font-size: 10px;">$ {{ number_format($report['audit_usd']['expected'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Físico Contado y Declarado:</td>
                            <td class="text-right font-mono font-bold">
                                {{ $report['audit_usd']['declared'] !== null ? '$ ' . number_format($report['audit_usd']['declared'], 2) : 'N/A' }}
                            </td>
                        </tr>
                        <tr style="background-color: #f3f4f6; font-weight: bold;">
                            <td>DIFERENCIA USD:</td>
                            <td class="text-right font-mono" style="font-size: 10.5px; color: {{ ($report['audit_usd']['difference'] ?? 0) < -0.009 ? '#dc2626' : (($report['audit_usd']['difference'] ?? 0) > 0.009 ? '#2563eb' : '#059669') }};">
                                {{ $report['audit_usd']['difference'] !== null ? ($report['audit_usd']['difference'] > 0 ? '+' : '') . '$ ' . number_format($report['audit_usd']['difference'], 2) : 'N/A' }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>

            <td class="split-space"></td>

            <!-- VES Audit -->
            <td class="split-col">
                <div class="audit-card">
                    <div class="audit-header">
                        <table style="width: 100%;">
                            <tr>
                                <td style="font-weight: bold; color: #111827;">EFECTIVO BOLÍVARES (VES)</td>
                                <td style="text-align: right;">
                                    @if($report['audit_ves']['status'] === 'exact')
                                        <span class="badge-exact">Cuadrado</span>
                                    @elseif($report['audit_ves']['status'] === 'surplus')
                                        <span class="badge-surplus">Sobrante</span>
                                    @elseif($report['audit_ves']['status'] === 'deficit')
                                        <span class="badge-deficit">Faltante</span>
                                    @else
                                        <span style="color: #6b7280; font-size: 8px;">Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <table class="audit-row-table">
                        <tr>
                            <td>(+) Fondo Inicial de Apertura:</td>
                            <td class="text-right font-mono font-bold">Bs. {{ number_format($report['audit_ves']['opening_cash'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>(+) Cobros por Ventas en Efectivo:</td>
                            <td class="text-right font-mono font-bold">Bs. {{ number_format($report['audit_ves']['sales_cash'], 2) }}</td>
                        </tr>
                        @if($report['audit_ves']['cash_in'] > 0)
                        <tr>
                            <td style="color: #059669;">(+) Entradas de Efectivo:</td>
                            <td class="text-right font-mono font-bold" style="color: #059669;">+Bs. {{ number_format($report['audit_ves']['cash_in'], 2) }}</td>
                        </tr>
                        @endif
                        @if($report['audit_ves']['cash_out'] > 0)
                        <tr>
                            <td style="color: #dc2626;">(-) Retiros / Sangrías / Gastos:</td>
                            <td class="text-right font-mono font-bold" style="color: #dc2626;">-Bs. {{ number_format($report['audit_ves']['cash_out'], 2) }}</td>
                        </tr>
                        @endif
                        <tr style="background-color: #f9fafb; font-weight: bold;">
                            <td>(=) Total Esperado en Sistema:</td>
                            <td class="text-right font-mono" style="font-size: 10px;">Bs. {{ number_format($report['audit_ves']['expected'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Físico Contado y Declarado:</td>
                            <td class="text-right font-mono font-bold">
                                {{ $report['audit_ves']['declared'] !== null ? 'Bs. ' . number_format($report['audit_ves']['declared'], 2) : 'N/A' }}
                            </td>
                        </tr>
                        <tr style="background-color: #f3f4f6; font-weight: bold;">
                            <td>DIFERENCIA VES:</td>
                            <td class="text-right font-mono" style="font-size: 10.5px; color: {{ ($report['audit_ves']['difference'] ?? 0) < -0.009 ? '#dc2626' : (($report['audit_ves']['difference'] ?? 0) > 0.009 ? '#2563eb' : '#059669') }};">
                                {{ $report['audit_ves']['difference'] !== null ? ($report['audit_ves']['difference'] > 0 ? '+' : '') . 'Bs. ' . number_format($report['audit_ves']['difference'], 2) : 'N/A' }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- 4. Desglose de Billetes Físicos (if any) -->
    @if(!empty($report['denominations']) && count($report['denominations']) > 0)
        <div class="section-title">4. Desglose del Conteo Físico de Billetes</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Moneda</th>
                    <th>Denominación</th>
                    <th class="text-center">Cantidad de Billetes</th>
                    <th class="text-right">Monto Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['denominations'] as $denom)
                    <tr>
                        <td class="font-bold">{{ $denom['currency'] }}</td>
                        <td class="font-mono">{{ $denom['currency'] === 'USD' ? '$' : 'Bs.' }} {{ number_format($denom['denomination'], 2) }}</td>
                        <td class="text-center font-mono">{{ $denom['count'] }}</td>
                        <td class="text-right font-mono font-bold">{{ $denom['currency'] === 'USD' ? '$' : 'Bs.' }} {{ number_format($denom['total_amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- 5. Movimientos Manuales de Caja (if any) -->
    @if(!empty($report['cash_movements']) && count($report['cash_movements']) > 0)
        <div class="section-title">5. Movimientos de Caja del Turno</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha / Hora</th>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th class="text-right">Monto</th>
                    <th>Motivo / Razón</th>
                    <th>Autorizado Por</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['cash_movements'] as $movement)
                    <tr>
                        <td class="font-mono">{{ $movement['created_at'] }}</td>
                        <td>
                            <span style="font-weight: bold; color: {{ $movement['type'] === 'in' ? '#059669' : '#dc2626' }};">
                                {{ $movement['type'] === 'in' ? 'ENTRADA' : 'SALIDA' }}
                            </span>
                        </td>
                        <td>{{ $movement['concept'] }}</td>
                        <td class="text-right font-mono font-bold">
                            {{ $movement['currency'] === 'USD' ? '$' : 'Bs.' }} {{ number_format($movement['amount'], 2) }}
                        </td>
                        <td>{{ $movement['reason'] ?: '-' }}</td>
                        <td>{{ $movement['authorized_by'] ?: 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- 6. Observaciones de Cierre (if any) -->
    @if(!empty($report['session']['closing_notes']))
        <div class="section-title">6. Observaciones del Cajero / Cierre</div>
        <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 6px 10px; font-style: italic; font-size: 9px; color: #374151;">
            "{{ $report['session']['closing_notes'] }}"
        </div>
    @endif

    <!-- 7. Firmas de Conformidad -->
    <table class="signature-table">
        <tr>
            <td class="signature-cell">
                <div class="signature-line"></div>
                <div class="signature-role">Cajero Responsable</div>
                <div class="signature-name">{{ $report['session']['cashier_name'] ?: 'Firma' }}</div>
            </td>
            <td style="width: 10%;"></td>
            <td class="signature-cell">
                <div class="signature-line"></div>
                <div class="signature-role">Supervisor / Gerente de Tienda</div>
                <div class="signature-name">{{ $report['session']['supervisor_name'] ?: 'Firma y Sello' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Documento generado el {{ date('d/m/Y H:i:s') }} | Sistema POS StreamCRM | ID Sesión: {{ $report['session']['id'] }}
    </div>

</body>
</html>
