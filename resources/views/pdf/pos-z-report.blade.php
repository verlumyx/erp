<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $report['type'] === 'Z' ? 'Corte Z' : 'Corte X' }} - {{ $report['session']['code'] }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 15mm 16mm 15mm 16mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            color: #0f172a;
            line-height: 1.35;
            background-color: #ffffff;
        }

        /* Header Section */
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .company-name {
            font-size: 17px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-meta {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 2.5px;
            line-height: 1.35;
        }
        .report-main-title {
            font-size: 15px;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
            text-align: right;
            letter-spacing: 0.5px;
        }
        .report-sub-title {
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 2px;
        }
        .session-code-badge {
            display: inline-block;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-weight: bold;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }

        .header-divider {
            height: 2.5px;
            background-color: #2563eb;
            width: 100%;
            margin-top: 8px;
            margin-bottom: 12px;
            border-radius: 2px;
        }

        /* Section Titles */
        .section-header {
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e293b;
            background-color: #f1f5f9;
            padding: 5px 9px;
            border-left: 3px solid #2563eb;
            margin-top: 13px;
            margin-bottom: 7px;
            border-radius: 0 4px 4px 0;
        }

        /* Metadata Card Table */
        .meta-card {
            width: 100%;
            border-collapse: collapse;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 8px;
        }
        .meta-cell {
            padding: 8px 10px;
            vertical-align: top;
            width: 25%;
            border-right: 1px solid #e2e8f0;
        }
        .meta-cell:last-child {
            border-right: none;
        }
        .meta-label {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            display: block;
            margin-bottom: 2px;
            letter-spacing: 0.3px;
        }
        .meta-value {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
        }
        .meta-subtext {
            font-size: 8px;
            color: #64748b;
            margin-top: 1.5px;
        }
        .status-pill {
            display: inline-block;
            padding: 1.5px 7px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #dbeafe;
            color: #1e40af;
            margin-top: 3px;
        }

        /* KPI Stat Cards (4 columns) */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 7px 0;
            margin-left: -7px;
            margin-right: -7px;
            margin-bottom: 8px;
        }
        .kpi-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            vertical-align: top;
            width: 25%;
        }
        .kpi-card-highlight {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
        }
        .kpi-label {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            display: block;
            letter-spacing: 0.3px;
        }
        .kpi-num {
            font-size: 13.5px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 3px;
            display: block;
        }
        .kpi-num-highlight {
            color: #1d4ed8;
            font-size: 15px;
        }
        .kpi-sub {
            font-size: 8px;
            color: #64748b;
            margin-top: 2.5px;
            display: block;
        }

        /* Payment Methods Grid */
        .payments-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 7px;
            margin-left: -7px;
            margin-right: -7px;
            margin-bottom: 6px;
        }
        .payment-cell {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 7px 9px;
            width: 25%;
            vertical-align: top;
        }
        .payment-cell-active {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
        }
        .payment-name {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            display: block;
            margin-bottom: 2px;
        }
        .payment-amount {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            display: block;
        }

        /* 2-Column Split Table for Cash Audit */
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
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background-color: #ffffff;
            overflow: hidden;
        }
        .audit-card-header {
            padding: 6px 9px;
            background-color: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
        }
        .audit-card-title {
            font-size: 9.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .audit-table {
            width: 100%;
            border-collapse: collapse;
        }
        .audit-table td {
            padding: 4.5px 9px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9px;
        }
        .audit-table tr:last-child td {
            border-bottom: none;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }

        .badge-status {
            padding: 2px 7px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .badge-exact {
            color: #065f46;
            background-color: #d1fae5;
            border: 1px solid #a7f3d0;
        }
        .badge-surplus {
            color: #1e40af;
            background-color: #dbeafe;
            border: 1px solid #bfdbfe;
        }
        .badge-deficit {
            color: #991b1b;
            background-color: #fee2e2;
            border: 1px solid #fecaca;
        }

        /* Generic Data Tables (Denominations, Movements) */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
        }
        .report-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 5px 9px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        .report-table td {
            padding: 5px 9px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9px;
            color: #1e293b;
        }
        .report-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .report-table tr:last-child td {
            border-bottom: none;
        }

        /* Signatures Section */
        .signatures-container {
            width: 100%;
            margin-top: 36px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 44%;
            text-align: center;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1.2px solid #334155;
            width: 75%;
            margin: 0 auto 6px auto;
        }
        .signature-title {
            font-size: 9px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .signature-subtitle {
            font-size: 8px;
            color: #64748b;
            margin-top: 2.5px;
        }

        /* Footer */
        .footer-table {
            width: 100%;
            margin-top: 22px;
            padding-top: 6px;
            border-top: 1px dotted #cbd5e1;
            font-size: 7.5px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                <div class="company-name">{{ $report['company']['name'] ?: 'EMPRESA' }}</div>
                @if(!empty($report['company']['tax_number']))
                    <div class="company-meta"><strong>RIF / NIT:</strong> {{ $report['company']['tax_number'] }}</div>
                @else
                    <div class="company-meta">Comprobante Oficial de Arqueo y Cierre de Caja</div>
                @endif
                @if(!empty($report['company']['address']))
                    <div class="company-meta">{{ $report['company']['address'] }}</div>
                @endif
            </td>
            <td style="width: 45%; vertical-align: middle; text-align: right;">
                <div class="report-main-title">
                    {{ $report['type'] === 'Z' ? 'Corte de Caja (Corte Z)' : 'Lectura Parcial (Corte X)' }}
                </div>
                <div class="report-sub-title">
                    {{ $report['type'] === 'Z' ? 'Comprobante de Cierre Definitivo' : 'Arqueo Parcial en Curso' }}
                </div>
                <div style="margin-top: 3px;">
                    <span class="session-code-badge">{{ $report['session']['code'] }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="header-divider"></div>

    <!-- Metadata Card -->
    <table class="meta-card">
        <tr>
            <td class="meta-cell">
                <span class="meta-label">Caja / Terminal</span>
                <span class="meta-value">{{ $report['session']['register_name'] ?: 'Caja Principal' }}</span>
                @if(!empty($report['session']['warehouse_name']))
                    <div class="meta-subtext">Almacén: {{ $report['session']['warehouse_name'] }}</div>
                @endif
            </td>
            <td class="meta-cell">
                <span class="meta-label">Cajero Responsable</span>
                <span class="meta-value">{{ $report['session']['cashier_name'] ?: 'N/A' }}</span>
                @if(!empty($report['session']['cashier_email']))
                    <div class="meta-subtext">{{ $report['session']['cashier_email'] }}</div>
                @endif
            </td>
            <td class="meta-cell">
                <span class="meta-label">Apertura de Turno</span>
                <span class="meta-value">{{ $report['session']['opened_at'] }}</span>
                @if(!empty($report['session']['supervisor_name']))
                    <div class="meta-subtext"><strong>Supervisor:</strong> {{ $report['session']['supervisor_name'] }}</div>
                @endif
            </td>
            <td class="meta-cell">
                <span class="meta-label">Cierre de Turno</span>
                <span class="meta-value">
                    {{ $report['session']['closed_at'] ?: 'En curso (Abierto)' }}
                </span>
                <div>
                    @php
                        $st = strtolower($report['session']['status'] ?? '');
                        $isClosed = in_array($st, ['closed', 'completed']);
                        $statusLabel = $isClosed ? 'CERRADO DEFINITIVO' : 'TURNO EN CURSO';
                    @endphp
                    <span class="status-pill">{{ $statusLabel }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- 1. Resumen de Operaciones -->
    <div class="section-header">1. Resumen de Operaciones</div>
    <table class="kpi-table">
        <tr>
            <td class="kpi-card">
                <span class="kpi-label">Ventas Realizadas</span>
                <span class="kpi-num">{{ $report['summary']['sales_count'] }}</span>
                @if($report['summary']['cancelled_sales_count'] > 0)
                    <span class="kpi-sub" style="color: #dc2626;">({{ $report['summary']['cancelled_sales_count'] }} anuladas)</span>
                @else
                    <span class="kpi-sub">0 anulaciones</span>
                @endif
            </td>
            <td class="kpi-card">
                <span class="kpi-label">Ticket Promedio</span>
                <span class="kpi-num">$ {{ number_format($report['summary']['average_ticket'], 2) }}</span>
                <span class="kpi-sub">Monto medio / ticket</span>
            </td>
            <td class="kpi-card">
                <span class="kpi-label">Base Imponible / IVA</span>
                <span class="kpi-num" style="font-size: 11.5px;">$ {{ number_format($report['summary']['taxable_base'], 2) }}</span>
                <span class="kpi-sub">IVA: $ {{ number_format($report['summary']['total_tax'], 2) }}</span>
            </td>
            <td class="kpi-card kpi-card-highlight">
                <span class="kpi-label" style="color: #1e40af;">Total Facturado Neto</span>
                <span class="kpi-num kpi-num-highlight">$ {{ number_format($report['summary']['total_net'], 2) }}</span>
                @if($report['summary']['discounts'] > 0)
                    <span class="kpi-sub" style="color: #1e40af;">Dctos: -$ {{ number_format($report['summary']['discounts'], 2) }}</span>
                @else
                    <span class="kpi-sub" style="color: #1e40af;">Sin descuentos</span>
                @endif
            </td>
        </tr>
    </table>

    <!-- 2. Cobros por Medio de Pago -->
    <div class="section-header">2. Cobros Recibidos por Medio de Pago</div>
    <table class="payments-table">
        <tr>
            <td class="payment-cell {{ $report['payments']['cash_usd'] > 0 ? 'payment-cell-active' : '' }}">
                <span class="payment-name">Efectivo Dólares (USD)</span>
                <span class="payment-amount">$ {{ number_format($report['payments']['cash_usd'], 2) }}</span>
            </td>
            <td class="payment-cell {{ $report['payments']['cash_ves'] > 0 ? 'payment-cell-active' : '' }}">
                <span class="payment-name">Efectivo Bolívares (VES)</span>
                <span class="payment-amount">Bs. {{ number_format($report['payments']['cash_ves'], 2) }}</span>
            </td>
            <td class="payment-cell {{ $report['payments']['card_ves'] > 0 ? 'payment-cell-active' : '' }}">
                <span class="payment-name">Punto de Venta / Débito</span>
                <span class="payment-amount">Bs. {{ number_format($report['payments']['card_ves'], 2) }}</span>
            </td>
            <td class="payment-cell {{ $report['payments']['mobile_ves'] > 0 ? 'payment-cell-active' : '' }}">
                <span class="payment-name">Pago Móvil (VES)</span>
                <span class="payment-amount">Bs. {{ number_format($report['payments']['mobile_ves'], 2) }}</span>
            </td>
        </tr>
        <tr>
            <td class="payment-cell {{ ($report['payments']['transfer_usd'] ?? 0) > 0 ? 'payment-cell-active' : '' }}">
                <span class="payment-name">Transferencia / Zelle ($)</span>
                <span class="payment-amount">$ {{ number_format($report['payments']['transfer_usd'] ?? 0, 2) }}</span>
            </td>
            <td class="payment-cell {{ ($report['payments']['biopago_ves'] ?? 0) > 0 ? 'payment-cell-active' : '' }}">
                <span class="payment-name">Biopago BDV (VES)</span>
                <span class="payment-amount">Bs. {{ number_format($report['payments']['biopago_ves'] ?? 0, 2) }}</span>
            </td>
            <td class="payment-cell {{ ($report['payments']['transfer_ves'] ?? 0) > 0 ? 'payment-cell-active' : '' }}">
                <span class="payment-name">Transferencia (VES)</span>
                <span class="payment-amount">Bs. {{ number_format($report['payments']['transfer_ves'] ?? 0, 2) }}</span>
            </td>
            <td class="payment-cell {{ ($report['payments']['other'] ?? 0) > 0 ? 'payment-cell-active' : '' }}">
                <span class="payment-name">Otros Métodos ($)</span>
                <span class="payment-amount">$ {{ number_format($report['payments']['other'] ?? 0, 2) }}</span>
            </td>
        </tr>
    </table>

    <!-- 3. Auditoría y Conciliación de Efectivo en Gaveta -->
    <div class="section-header">3. Auditoría y Conciliación de Efectivo en Gaveta</div>
    <table class="split-table">
        <tr>
            <!-- USD Box -->
            <td class="split-col">
                <div class="audit-card">
                    <div class="audit-card-header">
                        <table style="width: 100%;">
                            <tr>
                                <td class="audit-card-title">Efectivo Dólares (USD)</td>
                                <td style="text-align: right;">
                                    @if($report['audit_usd']['status'] === 'exact')
                                        <span class="badge-status badge-exact">Cuadrado</span>
                                    @elseif($report['audit_usd']['status'] === 'surplus')
                                        <span class="badge-status badge-surplus">Sobrante</span>
                                    @elseif($report['audit_usd']['status'] === 'deficit')
                                        <span class="badge-status badge-deficit">Faltante</span>
                                    @else
                                        <span class="badge-status" style="background-color: #f1f5f9; color: #64748b;">Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <table class="audit-table">
                        <tr>
                            <td>(+) Fondo Inicial de Apertura:</td>
                            <td class="text-right font-bold">$ {{ number_format($report['audit_usd']['opening_cash'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>(+) Cobros por Ventas Efectivo:</td>
                            <td class="text-right font-bold">$ {{ number_format($report['audit_usd']['sales_cash'], 2) }}</td>
                        </tr>
                        @if($report['audit_usd']['cash_in'] > 0)
                        <tr>
                            <td style="color: #059669;">(+) Entradas de Efectivo:</td>
                            <td class="text-right font-bold" style="color: #059669;">+$ {{ number_format($report['audit_usd']['cash_in'], 2) }}</td>
                        </tr>
                        @endif
                        @if($report['audit_usd']['cash_out'] > 0)
                        <tr>
                            <td style="color: #dc2626;">(-) Retiros / Sangrías de Caja:</td>
                            <td class="text-right font-bold" style="color: #dc2626;">-$ {{ number_format($report['audit_usd']['cash_out'], 2) }}</td>
                        </tr>
                        @endif
                        <tr style="background-color: #f8fafc; font-weight: bold;">
                            <td>(=) Total Esperado en Sistema:</td>
                            <td class="text-right font-bold" style="font-size: 9.5px; color: #0f172a;">$ {{ number_format($report['audit_usd']['expected'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Físico Declarado (Arqueo):</td>
                            <td class="text-right font-bold">
                                {{ $report['audit_usd']['declared'] !== null ? '$ ' . number_format($report['audit_usd']['declared'], 2) : 'N/A' }}
                            </td>
                        </tr>
                        <tr style="background-color: #f1f5f9; font-weight: bold;">
                            <td>DIFERENCIA USD:</td>
                            <td class="text-right font-bold" style="font-size: 10px; color: {{ ($report['audit_usd']['difference'] ?? 0) < -0.009 ? '#dc2626' : (($report['audit_usd']['difference'] ?? 0) > 0.009 ? '#2563eb' : '#059669') }};">
                                {{ $report['audit_usd']['difference'] !== null ? ($report['audit_usd']['difference'] > 0 ? '+' : '') . '$ ' . number_format($report['audit_usd']['difference'], 2) : 'N/A' }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>

            <td class="split-space"></td>

            <!-- VES Box -->
            <td class="split-col">
                <div class="audit-card">
                    <div class="audit-card-header">
                        <table style="width: 100%;">
                            <tr>
                                <td class="audit-card-title">Efectivo Bolívares (VES)</td>
                                <td style="text-align: right;">
                                    @if($report['audit_ves']['status'] === 'exact')
                                        <span class="badge-status badge-exact">Cuadrado</span>
                                    @elseif($report['audit_ves']['status'] === 'surplus')
                                        <span class="badge-status badge-surplus">Sobrante</span>
                                    @elseif($report['audit_ves']['status'] === 'deficit')
                                        <span class="badge-status badge-deficit">Faltante</span>
                                    @else
                                        <span class="badge-status" style="background-color: #f1f5f9; color: #64748b;">Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <table class="audit-table">
                        <tr>
                            <td>(+) Fondo Inicial de Apertura:</td>
                            <td class="text-right font-bold">Bs. {{ number_format($report['audit_ves']['opening_cash'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>(+) Cobros por Ventas Efectivo:</td>
                            <td class="text-right font-bold">Bs. {{ number_format($report['audit_ves']['sales_cash'], 2) }}</td>
                        </tr>
                        @if($report['audit_ves']['cash_in'] > 0)
                        <tr>
                            <td style="color: #059669;">(+) Entradas de Efectivo:</td>
                            <td class="text-right font-bold" style="color: #059669;">+Bs. {{ number_format($report['audit_ves']['cash_in'], 2) }}</td>
                        </tr>
                        @endif
                        @if($report['audit_ves']['cash_out'] > 0)
                        <tr>
                            <td style="color: #dc2626;">(-) Retiros / Sangrías / Gastos:</td>
                            <td class="text-right font-bold" style="color: #dc2626;">-Bs. {{ number_format($report['audit_ves']['cash_out'], 2) }}</td>
                        </tr>
                        @endif
                        <tr style="background-color: #f8fafc; font-weight: bold;">
                            <td>(=) Total Esperado en Sistema:</td>
                            <td class="text-right font-bold" style="font-size: 9.5px; color: #0f172a;">Bs. {{ number_format($report['audit_ves']['expected'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Físico Declarado (Arqueo):</td>
                            <td class="text-right font-bold">
                                {{ $report['audit_ves']['declared'] !== null ? 'Bs. ' . number_format($report['audit_ves']['declared'], 2) : 'N/A' }}
                            </td>
                        </tr>
                        <tr style="background-color: #f1f5f9; font-weight: bold;">
                            <td>DIFERENCIA VES:</td>
                            <td class="text-right font-bold" style="font-size: 10px; color: {{ ($report['audit_ves']['difference'] ?? 0) < -0.009 ? '#dc2626' : (($report['audit_ves']['difference'] ?? 0) > 0.009 ? '#2563eb' : '#059669') }};">
                                {{ $report['audit_ves']['difference'] !== null ? ($report['audit_ves']['difference'] > 0 ? '+' : '') . 'Bs. ' . number_format($report['audit_ves']['difference'], 2) : 'N/A' }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- 4. Desglose del Conteo Físico de Billetes (if present) -->
    @if(!empty($report['denominations']) && count($report['denominations']) > 0)
        <div class="section-header">4. Desglose del Conteo Físico de Billetes</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Moneda</th>
                    <th style="width: 25%;">Denominación</th>
                    <th class="text-center" style="width: 30%;">Cantidad de Billetes</th>
                    <th class="text-right" style="width: 30%;">Monto Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalCount = 0;
                @endphp
                @foreach($report['denominations'] as $denom)
                    @php $totalCount += $denom['count']; @endphp
                    <tr>
                        <td class="font-bold">{{ $denom['currency'] }}</td>
                        <td>{{ $denom['currency'] === 'USD' ? '$' : 'Bs.' }} {{ number_format($denom['denomination'], 2) }}</td>
                        <td class="text-center font-bold">{{ $denom['count'] }}</td>
                        <td class="text-right font-bold">{{ $denom['currency'] === 'USD' ? '$' : 'Bs.' }} {{ number_format($denom['total_amount'], 2) }}</td>
                    </tr>
                @endforeach
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="2">TOTAL BILLETES CONTADOS:</td>
                    <td class="text-center">{{ $totalCount }} unids</td>
                    <td class="text-right">-</td>
                </tr>
            </tbody>
        </table>
    @endif

    <!-- 5. Movimientos Manuales de Caja (if present) -->
    @if(!empty($report['cash_movements']) && count($report['cash_movements']) > 0)
        <div class="section-header">5. Movimientos de Caja Registrados</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Fecha / Hora</th>
                    <th style="width: 15%;">Tipo</th>
                    <th style="width: 20%;">Concepto</th>
                    <th class="text-right" style="width: 15%;">Monto</th>
                    <th style="width: 30%;">Motivo / Autorización</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['cash_movements'] as $movement)
                    <tr>
                        <td>{{ $movement['created_at'] }}</td>
                        <td>
                            <span style="font-weight: bold; color: {{ $movement['type'] === 'in' ? '#059669' : '#dc2626' }};">
                                {{ $movement['type'] === 'in' ? 'ENTRADA (+)' : 'SALIDA (-)' }}
                            </span>
                        </td>
                        <td>{{ $movement['concept'] }}</td>
                        <td class="text-right font-bold">
                            {{ $movement['currency'] === 'USD' ? '$' : 'Bs.' }} {{ number_format($movement['amount'], 2) }}
                        </td>
                        <td>
                            {{ $movement['reason'] ?: '-' }}
                            @if(!empty($movement['authorized_by']))
                                <span style="color: #64748b;">(Aut: {{ $movement['authorized_by'] }})</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- 6. Observaciones de Cierre (if present) -->
    @if(!empty($report['session']['closing_notes']))
        <div class="section-header">6. Observaciones de Cierre</div>
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 10px; font-style: italic; font-size: 8.5px; color: #334155;">
            "{{ $report['session']['closing_notes'] }}"
        </div>
    @endif

    <!-- Firmas de Conformidad -->
    <table class="signatures-container">
        <tr>
            <td class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Cajero Responsable</div>
                <div class="signature-subtitle">{{ $report['session']['cashier_name'] ?: 'Firma del Cajero' }}</div>
            </td>
            <td style="width: 12%;"></td>
            <td class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Supervisor / Auditor</div>
                <div class="signature-subtitle">{{ $report['session']['supervisor_name'] ?: 'Firma y Sello de Tienda' }}</div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <table class="footer-table">
        <tr>
            <td style="text-align: left;">
                Comprobante Oficial de Caja | Sistema ERP StreamCRM
            </td>
            <td style="text-align: right;">
                Generado el {{ date('d/m/Y H:i:s') }}
            </td>
        </tr>
    </table>

</body>
</html>
