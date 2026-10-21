import type { PosZReport } from '../types/Pos';

function escapeHtml(text?: string | null): string {
    if (!text) return '';
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

export function generateReportXZHtml(report: PosZReport): string {
    const isZ = report.type === 'Z';
    const reportTitle = isZ ? 'CORTE DE CAJA (CORTE Z)' : 'LECTURA PARCIAL (CORTE X)';
    const reportSubtitle = isZ ? 'CIERRE DEFINITIVO DE TURNO' : 'ARQUEO EN CURSO';

    const companyName = report.company?.name || 'EMPRESA';
    const companyTax = report.company?.tax_number
        ? `<p class="meta-line">RIF/NIT: ${escapeHtml(report.company.tax_number)}</p>`
        : '';
    const companyAddress = report.company?.address
        ? `<p class="meta-line">${escapeHtml(report.company.address)}</p>`
        : '';

    const closedAtLine = isZ
        ? `<p class="meta-line">Cierre: ${escapeHtml(report.session.closed_at || 'Cerrado')}</p>`
        : '<p class="meta-line">Estado: EN CURSO (ABIERTO)</p>';

    // Payments Breakdown
    const payments = report.payments || {
        cash_usd: 0,
        cash_ves: 0,
        card_ves: 0,
        mobile_ves: 0,
        biopago_ves: 0,
        transfer_usd: 0,
        transfer_ves: 0,
        other: 0,
    };

    const biopagoHtml =
        payments.biopago_ves && payments.biopago_ves > 0
            ? `
                <div class="flex-between">
                    <span>Biopago:</span>
                    <span>Bs. ${payments.biopago_ves.toFixed(2)}</span>
                </div>
            `
            : '';

    const transferVesHtml =
        payments.transfer_ves && payments.transfer_ves > 0
            ? `
                <div class="flex-between">
                    <span>Transf. VES:</span>
                    <span>Bs. ${payments.transfer_ves.toFixed(2)}</span>
                </div>
            `
            : '';

    const otherPaymentsHtml =
        payments.other && payments.other > 0
            ? `
                <div class="flex-between">
                    <span>Otros:</span>
                    <span>$${payments.other.toFixed(2)}</span>
                </div>
            `
            : '';

    // Audit USD Differences
    const usdDiff = report.audit_usd.difference ?? 0;
    let usdDiffLabel = 'Cuadrado: $0.00';
    if (report.audit_usd.status === 'surplus' || usdDiff > 0.009) {
        usdDiffLabel = `Sobrante: +$${usdDiff.toFixed(2)}`;
    } else if (report.audit_usd.status === 'deficit' || usdDiff < -0.009) {
        usdDiffLabel = `Faltante: $${usdDiff.toFixed(2)}`;
    }

    const usdDeclaredSection =
        isZ || report.audit_usd.declared !== undefined && report.audit_usd.declared !== null
            ? `
                <div class="flex-between font-bold">
                    <span>Contado Físico:</span>
                    <span>$${(report.audit_usd.declared ?? 0).toFixed(2)}</span>
                </div>
                <div class="flex-between font-bold">
                    <span>Diferencia USD:</span>
                    <span>${escapeHtml(usdDiffLabel)}</span>
                </div>
            `
            : '';

    // Audit VES Differences
    const vesDiff = report.audit_ves.difference ?? 0;
    let vesDiffLabel = 'Cuadrado: Bs. 0.00';
    if (report.audit_ves.status === 'surplus' || vesDiff > 0.009) {
        vesDiffLabel = `Sobrante: +Bs. ${vesDiff.toFixed(2)}`;
    } else if (report.audit_ves.status === 'deficit' || vesDiff < -0.009) {
        vesDiffLabel = `Faltante: Bs. ${vesDiff.toFixed(2)}`;
    }

    const vesDeclaredSection =
        isZ || report.audit_ves.declared !== undefined && report.audit_ves.declared !== null
            ? `
                <div class="flex-between font-bold">
                    <span>Contado Físico:</span>
                    <span>Bs. ${(report.audit_ves.declared ?? 0).toFixed(2)}</span>
                </div>
                <div class="flex-between font-bold">
                    <span>Diferencia VES:</span>
                    <span>${escapeHtml(vesDiffLabel)}</span>
                </div>
            `
            : '';

    // Cash Movements (if any)
    const movements = report.cash_movements || [];
    const movementsHtml =
        movements.length > 0
            ? `
                <div class="dashed-border section-block">
                    <p class="section-title">Movimientos de Caja:</p>
                    ${movements
                        .map((m) => {
                            const sign = m.type === 'in' ? '+' : '-';
                            const curr = m.currency === 'USD' ? '$' : 'Bs.';
                            return `
                                <div class="item-row">
                                    <div class="flex-between">
                                        <span>${m.type === 'in' ? '🟢 Entrada' : '🔴 Salida'}: ${escapeHtml(m.concept || 'Movimiento')}</span>
                                        <span class="font-bold">${sign}${curr}${m.amount.toFixed(2)}</span>
                                    </div>
                                    ${m.reason ? `<div class="sub-text">${escapeHtml(m.reason)}</div>` : ''}
                                </div>
                            `;
                        })
                        .join('')}
                </div>
            `
            : '';

    // Denominations (if any)
    const denominations = report.denominations || [];
    const denominationsHtml =
        isZ && denominations.length > 0
            ? `
                <div class="dashed-border section-block">
                    <p class="section-title">Detalle Arqueo Físico:</p>
                    ${denominations
                        .map((d) => {
                            const sym = d.currency === 'USD' ? '$' : 'Bs.';
                            return `
                                <div class="flex-between">
                                    <span>${d.count} x ${sym}${d.denomination}:</span>
                                    <span>${sym}${d.total_amount.toFixed(2)}</span>
                                </div>
                            `;
                        })
                        .join('')}
                </div>
            `
            : '';

    // Closing notes
    const notesHtml = report.session.closing_notes
        ? `
            <div class="dashed-border section-block">
                <p class="section-title">Notas de Cierre:</p>
                <p class="notes-text">${escapeHtml(report.session.closing_notes)}</p>
            </div>
        `
        : '';

    const printDate = new Date().toLocaleString('es-VE', {
        dateStyle: 'short',
        timeStyle: 'medium',
    });

    return `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${escapeHtml(reportTitle)} - ${escapeHtml(report.session.code)}</title>
    <style>
        @page {
            size: 48mm 210mm;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        html, body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f3f4f6;
            color: #000;
            font-size: 11px;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        /* Screen Display */
        @media screen {
            body {
                padding: 16px;
                display: flex;
                flex-direction: column;
                align-items: center;
                background-color: #e5e7eb;
                min-height: 100vh;
            }
            .screen-actions {
                width: 100%;
                max-width: 320px;
                margin-bottom: 12px;
                background: #ffffff;
                padding: 12px;
                border-radius: 8px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.12);
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .screen-actions .btn-group {
                display: flex;
                gap: 8px;
            }
            .btn {
                flex: 1;
                padding: 8px 12px;
                font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                font-size: 12px;
                font-weight: 600;
                border-radius: 6px;
                cursor: pointer;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
            }
            .btn-print {
                background: #2563eb;
                color: #ffffff;
            }
            .btn-print:hover {
                background: #1d4ed8;
            }
            .btn-close {
                background: #f3f4f6;
                color: #374151;
                border: 1px solid #d1d5db;
            }
            .btn-close:hover {
                background: #e5e7eb;
            }
            .printer-tip {
                font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                font-size: 11px;
                color: #4b5563;
                text-align: center;
                line-height: 1.3;
            }
            .receipt-ticket {
                width: 58mm;
                background: #ffffff;
                padding: 12px 8px;
                border: 1px dashed #9ca3af;
                border-radius: 6px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            }
        }

        /* Print Settings (Thermal 58mm / 48mm printable) */
        @media print {
            .no-print {
                display: none !important;
            }
            html, body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 48mm !important;
            }
            .receipt-ticket {
                width: 48mm !important;
                max-width: 48mm !important;
                margin: 0 !important;
                padding: 1mm 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
        }

        /* Ticket details */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .font-black { font-weight: 900; }
        .uppercase { text-transform: uppercase; }
        .dashed-border {
            border-bottom: 1px dashed #4b5563;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }
        .dotted-border {
            border-top: 1px dotted #4b5563;
            padding-top: 4px;
            margin-top: 4px;
        }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        .company-name {
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .header-title {
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 3px;
        }
        .header-subtitle {
            font-size: 9px;
            font-weight: bold;
            color: #4b5563;
            letter-spacing: 0.5px;
        }
        .session-code {
            font-size: 11px;
            font-weight: bold;
            color: #1f2937;
            margin-top: 2px;
        }
        .meta-line {
            font-size: 10px;
            color: #4b5563;
        }
        .section-block {
            font-size: 10px;
            line-height: 1.35;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
            margin-bottom: 3px;
        }
        .sub-header {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #4b5563;
            margin-top: 4px;
            margin-bottom: 1px;
        }
        .item-row {
            margin-bottom: 3px;
        }
        .sub-text {
            font-size: 9px;
            color: #6b7280;
        }
        .total-highlight {
            font-size: 12px;
            font-weight: 900;
            padding-top: 2px;
        }
        .notes-text {
            font-size: 10px;
            font-style: italic;
            color: #374151;
            word-break: break-word;
        }
        .signature-area {
            text-align: center;
            margin-top: 14px;
            padding-top: 12px;
        }
        .signature-line {
            display: inline-block;
            width: 70%;
            border-top: 1px solid #000;
            margin-bottom: 3px;
        }
        .signature-label {
            font-size: 9px;
            color: #4b5563;
            text-transform: uppercase;
        }
        .cut-spacer {
            height: 25mm;
            line-height: 25mm;
            text-align: center;
            font-size: 6px;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="screen-actions no-print">
        <div class="printer-tip">
            Selecciona tu impresora térmica (ej. <strong>TECH CLA58</strong>) en la ventana de impresión del navegador.
        </div>
        <div class="btn-group">
            <button class="btn btn-print" onclick="window.print()">
                🖨️ Imprimir ${escapeHtml(report.type === 'Z' ? 'Corte Z' : 'Corte X')}
            </button>
            <button class="btn btn-close" onclick="window.close()">
                Cerrar
            </button>
        </div>
    </div>

    <div class="receipt-ticket" id="pos-report">
        <!-- Header -->
        <div class="text-center dashed-border">
            <div class="company-name">${escapeHtml(companyName)}</div>
            ${companyTax}
            ${companyAddress}
            <div style="margin-top: 4px;">
                <h3 class="header-title">${escapeHtml(reportTitle)}</h3>
                <p class="header-subtitle">${escapeHtml(reportSubtitle)}</p>
                <p class="session-code">TURNO: ${escapeHtml(report.session.code)}</p>
            </div>
            <div style="margin-top: 4px;">
                <p class="meta-line">Caja: ${escapeHtml(report.session.register_name || '01')}${report.session.warehouse_name ? ' (' + escapeHtml(report.session.warehouse_name) + ')' : ''}</p>
                <p class="meta-line">Cajero: ${escapeHtml(report.session.cashier_name || 'N/A')}</p>
                <p class="meta-line">Apertura: ${escapeHtml(report.session.opened_at)}</p>
                ${closedAtLine}
            </div>
        </div>

        <!-- 1. Operational Summary -->
        <div class="dashed-border section-block">
            <p class="section-title">Resumen de Operaciones:</p>
            <div class="flex-between">
                <span>Ventas Realizadas:</span>
                <span class="font-bold">${report.summary.sales_count}</span>
            </div>
            <div class="flex-between">
                <span>Tickets Anulados:</span>
                <span>${report.summary.cancelled_sales_count}</span>
            </div>
            <div class="flex-between">
                <span>Ticket Promedio:</span>
                <span>$${report.summary.average_ticket.toFixed(2)}</span>
            </div>
            <div class="flex-between">
                <span>Ventas Brutas:</span>
                <span>$${report.summary.total_gross.toFixed(2)}</span>
            </div>
            <div class="flex-between">
                <span>Descuentos:</span>
                <span>-$${report.summary.discounts.toFixed(2)}</span>
            </div>
            <div class="flex-between">
                <span>Base Imponible:</span>
                <span>$${report.summary.taxable_base.toFixed(2)}</span>
            </div>
            <div class="flex-between">
                <span>IVA:</span>
                <span>$${report.summary.total_tax.toFixed(2)}</span>
            </div>
            <div class="flex-between dotted-border total-highlight">
                <span>TOTAL FACTURADO:</span>
                <span>$${report.summary.total_net.toFixed(2)}</span>
            </div>
        </div>

        <!-- 2. Payments Breakdown -->
        <div class="dashed-border section-block">
            <p class="section-title">Cobros Recibidos:</p>
            <div class="flex-between">
                <span>Efectivo USD:</span>
                <span class="font-bold">$${payments.cash_usd.toFixed(2)}</span>
            </div>
            <div class="flex-between">
                <span>Efectivo VES:</span>
                <span class="font-bold">Bs. ${payments.cash_ves.toFixed(2)}</span>
            </div>
            <div class="flex-between">
                <span>Punto Débito:</span>
                <span>Bs. ${payments.card_ves.toFixed(2)}</span>
            </div>
            <div class="flex-between">
                <span>Pago Móvil:</span>
                <span>Bs. ${payments.mobile_ves.toFixed(2)}</span>
            </div>
            <div class="flex-between">
                <span>Transf./Zelle:</span>
                <span class="font-bold">$${(payments.transfer_usd ?? 0).toFixed(2)}</span>
            </div>
            ${biopagoHtml}
            ${transferVesHtml}
            ${otherPaymentsHtml}
        </div>

        <!-- 3. Cash Audit & Gaveta -->
        <div class="dashed-border section-block">
            <p class="section-title">Arqueo de Gaveta:</p>
            
            <p class="sub-header">Dólares (USD):</p>
            <div class="flex-between">
                <span>Fondo Inicial:</span>
                <span>$${report.audit_usd.opening_cash.toFixed(2)}</span>
            </div>
            <div class="flex-between">
                <span>Ventas Efectivo:</span>
                <span>+$${report.audit_usd.sales_cash.toFixed(2)}</span>
            </div>
            ${report.audit_usd.cash_in > 0 ? `<div class="flex-between"><span>Entradas Caja:</span><span>+$${report.audit_usd.cash_in.toFixed(2)}</span></div>` : ''}
            ${report.audit_usd.cash_out > 0 ? `<div class="flex-between"><span>Salidas Caja:</span><span>-$${report.audit_usd.cash_out.toFixed(2)}</span></div>` : ''}
            <div class="flex-between font-bold dotted-border">
                <span>Esperado en Gaveta:</span>
                <span>$${report.audit_usd.expected.toFixed(2)}</span>
            </div>
            ${usdDeclaredSection}

            <p class="sub-header" style="margin-top: 6px;">Bolívares (VES):</p>
            <div class="flex-between">
                <span>Fondo Inicial:</span>
                <span>Bs. ${report.audit_ves.opening_cash.toFixed(2)}</span>
            </div>
            <div class="flex-between">
                <span>Ventas Efectivo:</span>
                <span>+Bs. ${report.audit_ves.sales_cash.toFixed(2)}</span>
            </div>
            ${report.audit_ves.cash_in > 0 ? `<div class="flex-between"><span>Entradas Caja:</span><span>+Bs. ${report.audit_ves.cash_in.toFixed(2)}</span></div>` : ''}
            ${report.audit_ves.cash_out > 0 ? `<div class="flex-between"><span>Salidas Caja:</span><span>-Bs. ${report.audit_ves.cash_out.toFixed(2)}</span></div>` : ''}
            <div class="flex-between font-bold dotted-border">
                <span>Esperado en Gaveta:</span>
                <span>Bs. ${report.audit_ves.expected.toFixed(2)}</span>
            </div>
            ${vesDeclaredSection}
        </div>

        <!-- 4. Movements (if any) -->
        ${movementsHtml}

        <!-- 5. Denominations (if any) -->
        ${denominationsHtml}

        <!-- 6. Closing Notes (if any) -->
        ${notesHtml}

        <!-- Signatures & Footer -->
        <div class="signature-area">
            <span class="signature-line"></span>
            <p class="signature-label">Firma Responsable / Cajero</p>
            <p class="meta-line" style="margin-top: 6px; font-size: 8px;">Impreso: ${escapeHtml(printDate)}</p>
        </div>

        <!-- Cutting spacer to advance paper past physical tear bar -->
        <div class="cut-spacer">.</div>
    </div>

    <script>
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.close();
            } else if (e.key === 'Enter') {
                window.print();
            }
        });

        // Trigger native print dialog automatically
        window.addEventListener('load', function() {
            window.focus();
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>`;
}

export function printThermalReportXZ(report: PosZReport): void {
    const width = 450;
    const height = 750;
    const left = Math.max(0, Math.round((window.screen.width - width) / 2));
    const top = Math.max(0, Math.round((window.screen.height - height) / 2));

    const printWindow = window.open(
        '',
        '_blank',
        `width=${width},height=${height},left=${left},top=${top},toolbar=no,menubar=no,scrollbars=yes,status=no`
    );

    if (!printWindow) {
        // Fallback to in-page print if popup blocker prevents opening
        window.print();
        return;
    }

    const html = generateReportXZHtml(report);
    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}
