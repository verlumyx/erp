import type { PosSale } from '../types/Pos';

const PAYMENT_METHOD_NAMES: Record<string, string> = {
    cash: 'Efectivo',
    card_debit: 'Punto Débito',
    card_credit: 'Punto Crédito',
    mobile_payment: 'Pago Móvil',
    biopago: 'Biopago',
    transfer: 'Transferencia',
    credit_note: 'Nota de Crédito',
    other: 'Otro',
};

function escapeHtml(text?: string | null): string {
    if (!text) return '';
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

export function generateReceiptHtml(sale: PosSale, companyName?: string): string {
    const linesHtml = (sale.lines || [])
        .map((line) => {
            const desc = escapeHtml(line.item_name || line.item_code || 'Producto');
            const qtyPrice = `${line.quantity} x $${line.unit_price.toFixed(2)}`;
            const total = `$${line.total.toFixed(2)}`;

            return `
                <div class="item-row">
                    <div class="flex-between">
                        <span><strong>${escapeHtml(qtyPrice)}</strong></span>
                        <span class="font-bold">${escapeHtml(total)}</span>
                    </div>
                    <div class="item-name">${desc}</div>
                </div>
            `;
        })
        .join('');

    const paymentsHtml = (sale.payments || [])
        .map((p) => {
            const label = PAYMENT_METHOD_NAMES[p.payment_method] || p.payment_method.replace('_', ' ');
            const amount = `${p.currency === 'USD' ? '$' : 'Bs.'} ${p.amount_tendered.toFixed(2)}`;
            return `
                <div class="flex-between">
                    <span>${escapeHtml(label)}:</span>
                    <span>${escapeHtml(amount)}</span>
                </div>
            `;
        })
        .join('');

    const discountHtml =
        sale.discount_amount > 0
            ? `
                <div class="flex-between">
                    <span>Descuento:</span>
                    <span>-$${sale.discount_amount.toFixed(2)}</span>
                </div>
            `
            : '';

    const clientTaxHtml = sale.client_tax_number
        ? `<p><strong>RIF/CI:</strong> ${escapeHtml(sale.client_tax_number)}</p>`
        : '';

    const companyHeader = companyName
        ? `<div class="company-name">${escapeHtml(companyName)}</div>`
        : '';

    return `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - ${escapeHtml(sale.code)}</title>
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
        .capitalize { text-transform: capitalize; }
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
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .header-title {
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .ticket-code {
            font-size: 11px;
            font-weight: bold;
            color: #1f2937;
        }
        .meta-line {
            font-size: 10px;
            color: #4b5563;
        }
        .client-section {
            font-size: 11px;
            line-height: 1.35;
        }
        .items-header {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #4b5563;
            padding-bottom: 3px;
        }
        .item-row {
            margin-bottom: 4px;
        }
        .item-name {
            font-size: 10px;
            color: #374151;
            word-break: break-word;
        }
        .totals-section {
            font-size: 11px;
            line-height: 1.35;
        }
        .total-usd {
            font-size: 13px;
            font-weight: 900;
        }
        .total-bs {
            font-size: 11px;
            font-weight: bold;
        }
        .payments-section {
            font-size: 10px;
            line-height: 1.35;
        }
        .payments-title {
            font-weight: bold;
            text-transform: uppercase;
            color: #4b5563;
            margin-bottom: 2px;
        }
        .footer-section {
            text-align: center;
            font-size: 10px;
            color: #4b5563;
            padding-top: 6px;
            line-height: 1.3;
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
                🖨️ Imprimir Ticket
            </button>
            <button class="btn btn-close" onclick="window.close()">
                Cerrar
            </button>
        </div>
    </div>

    <div class="receipt-ticket" id="pos-receipt">
        <!-- Header -->
        <div class="text-center dashed-border">
            ${companyHeader}
            <h3 class="header-title">COMPROBANTE DE VENTA</h3>
            <p class="ticket-code">TICKET POS: ${escapeHtml(sale.code)}</p>
            <p class="meta-line">Fecha: ${escapeHtml(sale.sale_date)}</p>
            <p class="meta-line">
                Caja: ${escapeHtml(sale.pos_register_name || '01')} • Cajero: ${escapeHtml(sale.pos_session_code || '')}
            </p>
        </div>

        <!-- Customer -->
        <div class="dashed-border client-section">
            <p><strong>Cliente:</strong> ${escapeHtml(sale.client_name || 'Consumidor Final')}</p>
            ${clientTaxHtml}
        </div>

        <!-- Items Table -->
        <div class="dashed-border">
            <div class="flex-between items-header">
                <span>Cant • Descripción</span>
                <span>Total</span>
            </div>
            ${linesHtml}
        </div>

        <!-- Totals -->
        <div class="dashed-border totals-section">
            <div class="flex-between">
                <span>Subtotal:</span>
                <span>$${sale.subtotal.toFixed(2)}</span>
            </div>
            ${discountHtml}
            <div class="flex-between">
                <span>IVA:</span>
                <span>$${sale.tax_amount.toFixed(2)}</span>
            </div>
            <div class="flex-between dotted-border total-usd">
                <span>TOTAL USD:</span>
                <span>$${sale.total.toFixed(2)}</span>
            </div>
            <div class="flex-between total-bs">
                <span>TOTAL BS:</span>
                <span>Bs. ${sale.total_in_local_currency.toFixed(2)}</span>
            </div>
        </div>

        <!-- Payments -->
        <div class="dashed-border payments-section">
            <p class="payments-title">Formas de Pago:</p>
            ${paymentsHtml}
            <div class="flex-between font-bold" style="padding-top: 3px;">
                <span>Vuelto USD:</span>
                <span>$${sale.change_amount_usd.toFixed(2)}</span>
            </div>
            <div class="flex-between font-bold">
                <span>Vuelto VES:</span>
                <span>Bs. ${sale.change_amount_ves.toFixed(2)}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-section">
            <p>¡Gracias por su compra!</p>
            <p>Conserve este ticket como comprobante</p>
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

export function printThermalReceipt(sale: PosSale, companyName?: string): void {
    const width = 450;
    const height = 700;
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

    const html = generateReceiptHtml(sale, companyName);
    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}
