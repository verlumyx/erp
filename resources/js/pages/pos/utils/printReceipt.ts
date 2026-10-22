import type { PosSale } from '../types/Pos';

export interface ReceiptCompanyInfo {
    name?: string;
    rif?: string;
    fiscal_address?: string;
    taxpayer_type?: string;
    phone?: string;
}

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

function getTaxpayerTypeLabel(type?: string): string {
    switch (type) {
        case 'special':
            return 'CONTRIBUYENTE ESPECIAL';
        case 'formal':
            return 'CONTRIBUYENTE FORMAL';
        case 'ordinary':
        default:
            return 'CONTRIBUYENTE ORDINARIO';
    }
}

function formatDateTime(saleDateStr?: string | null): { date: string; time: string } {
    if (!saleDateStr) {
        const now = new Date();
        const d = String(now.getDate()).padStart(2, '0');
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const y = now.getFullYear();
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        return { date: `${d}/${m}/${y}`, time: `${hh}:${mm}` };
    }

    const [datePart, timePart] = saleDateStr.split(' ');
    let formattedDate = datePart;
    if (datePart && datePart.includes('-')) {
        const [y, m, d] = datePart.split('-');
        formattedDate = `${d}/${m}/${y}`;
    }

    let formattedTime = '12:00';
    if (timePart) {
        formattedTime = timePart.slice(0, 5);
    }

    return { date: formattedDate, time: formattedTime };
}

export function generateReceiptHtml(
    sale: PosSale,
    companyOrName?: string | ReceiptCompanyInfo | null
): string {
    const company: ReceiptCompanyInfo =
        typeof companyOrName === 'string'
            ? { name: companyOrName }
            : companyOrName || {};

    const exchangeRate = Number(sale.exchange_rate) || 1;
    const dateTime = formatDateTime(sale.sale_date);

    const controlNumber = (sale as any).control_number
        || (sale.sales_invoice_code
            ? '00-' + String(sale.sales_invoice_code.replace(/[^0-9]/g, '') || '00001045').padStart(8, '0')
            : '00-' + String(sale.code.replace(/[^0-9]/g, '') || '00001045').padStart(8, '0'));

    const invoiceNumber = sale.sales_invoice_code || sale.code;

    // Group taxable bases and taxes dynamically by percentage
    let exemptBase = 0;
    const taxGroups: Record<number, { base: number; tax: number }> = {};

    const linesHtml = (sale.lines || [])
        .map((line) => {
            const desc = escapeHtml(line.item_name || line.item_code || 'Producto');
            const taxPercent = Number(line.tax_percent) || 0;
            const subtotal = Number(line.subtotal) || 0;
            const taxAmount = Number(line.tax_amount) || 0;

            if (taxPercent === 0) {
                exemptBase += subtotal;
            } else {
                if (!taxGroups[taxPercent]) {
                    taxGroups[taxPercent] = { base: 0, tax: 0 };
                }
                taxGroups[taxPercent].base += subtotal;
                taxGroups[taxPercent].tax += taxAmount;
            }

            const taxTag = taxPercent === 0 ? '(E)' : `(${taxPercent}%)`;
            const qty = line.quantity.toFixed(1);
            const total = line.total.toFixed(2);

            return `
                <div class="flex-between item-row">
                    <span class="item-desc">${qty} ${desc} ${taxTag}</span>
                    <span class="item-total">${total}</span>
                </div>
            `;
        })
        .join('');

    const paymentsHtml = (sale.payments || [])
        .map((p) => {
            const label = PAYMENT_METHOD_NAMES[p.payment_method] || p.payment_method.replace('_', ' ');
            const curr = p.currency === 'USD' ? '$' : 'Bs.';
            const amt = `${curr}  ${p.amount_tendered.toFixed(2)}`;
            const fullLabel = `${label} ${p.currency === 'USD' ? 'USD' : 'VES'}:`;
            return `
                <div class="flex-between">
                    <span>${escapeHtml(fullLabel)}</span>
                    <span>${escapeHtml(amt)}</span>
                </div>
            `;
        })
        .join('');

    const changeHtml = (sale.change_amount_usd > 0 || sale.change_amount_ves > 0)
        ? `
            ${sale.change_amount_usd > 0 ? `
            <div class="flex-between font-bold">
                <span>Vuelto USD:</span>
                <span>$  ${sale.change_amount_usd.toFixed(2)}</span>
            </div>` : ''}
            ${sale.change_amount_ves > 0 ? `
            <div class="flex-between font-bold">
                <span>Vuelto VES:</span>
                <span>Bs.  ${sale.change_amount_ves.toFixed(2)}</span>
            </div>` : ''}
        `
        : '';

    const discountHtml =
        sale.discount_amount > 0
            ? `
                <div class="flex-between">
                    <span>Descuento:</span>
                    <span>-$  ${sale.discount_amount.toFixed(2)}</span>
                </div>
            `
            : '';

    const exemptHtml =
        exemptBase > 0
            ? `
                <div class="flex-between">
                    <span>Exento:</span>
                    <span>$  ${exemptBase.toFixed(2)}</span>
                </div>
            `
            : '';

    const taxBreakdownHtml = Object.entries(taxGroups)
        .sort(([a], [b]) => Number(b) - Number(a))
        .map(([percentStr, data]) => {
            const percent = Number(percentStr);
            return `
                <div class="flex-between">
                    <span>Base Imponible (${percent}%):</span>
                    <span>$  ${data.base.toFixed(2)}</span>
                </div>
                <div class="flex-between">
                    <span>IVA (${percent}%):</span>
                    <span>$  ${data.tax.toFixed(2)}</span>
                </div>
            `;
        })
        .join('');

    const fallbackTaxHtml =
        Object.keys(taxGroups).length === 0 && sale.tax_amount > 0
            ? `
                <div class="flex-between">
                    <span>IVA:</span>
                    <span>$  ${sale.tax_amount.toFixed(2)}</span>
                </div>
            `
            : '';

    const rateHtml =
        exchangeRate > 1
            ? `
                <div class="flex-between meta-rate">
                    <span>Tasa BCV:</span>
                    <span>Bs. ${exchangeRate.toFixed(4)} / $</span>
                </div>
            `
            : '';

    const clientTaxHtml = sale.client_tax_number
        ? `<p><strong>RIF/CI:</strong> ${escapeHtml(sale.client_tax_number)}</p>`
        : '';

    const taxpayerLabel = company.taxpayer_type ? getTaxpayerTypeLabel(company.taxpayer_type) : '';

    const companyHeader = `
        <div class="company-name">${escapeHtml(company.name || 'COMPROBANTE DE VENTA')}</div>
        ${company.rif ? `<p class="company-meta"><strong>RIF:</strong> ${escapeHtml(company.rif)}</p>` : ''}
        ${taxpayerLabel ? `<p class="company-meta"><strong>${escapeHtml(taxpayerLabel)}</strong></p>` : ''}
        ${company.fiscal_address ? `<p class="company-meta">${escapeHtml(company.fiscal_address)}</p>` : ''}
        ${company.phone ? `<p class="company-meta">Tel: ${escapeHtml(company.phone)}</p>` : ''}
    `;

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
        .sep-double, .sep-single {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: -0.5px;
            text-align: center;
            overflow: hidden;
            white-space: nowrap;
            color: #000;
            margin: 3px 0;
            line-height: 1;
        }
        .text-meta {
            font-size: 9.5px;
            color: #374151;
            line-height: 1.2;
        }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 4px;
        }
        .company-name {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .company-meta {
            font-size: 10px;
            color: #1f2937;
            line-height: 1.25;
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
        .meta-rate {
            font-size: 10px;
            color: #374151;
            font-style: italic;
            padding-top: 2px;
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
        <div class="sep-double">================================</div>
        <div class="company-name text-center">${escapeHtml(company.name || 'EMPRESA')}</div>
        ${company.rif ? `<div class="text-center">RIF: ${escapeHtml(company.rif)}</div>` : ''}
        ${company.fiscal_address ? `<div class="text-center text-meta">${escapeHtml(company.fiscal_address)}</div>` : ''}
        ${company.phone ? `<div class="text-center">Tel: ${escapeHtml(company.phone)}</div>` : ''}
        <div class="sep-double">================================</div>

        <div class="header-title text-center">FACTURA</div>
        <div class="flex-between">
            <span>FACTURA N°:</span>
            <span class="font-bold">${escapeHtml(invoiceNumber)}</span>
        </div>
        <div class="flex-between">
            <span>N° CONTROL:</span>
            <span class="font-bold">${escapeHtml(controlNumber)}</span>
        </div>
        <div class="flex-between">
            <span>FECHA: ${escapeHtml(dateTime.date)}</span>
            <span>HORA: ${escapeHtml(dateTime.time)}</span>
        </div>
        <div class="flex-between">
            <span>CAJA: ${escapeHtml(sale.pos_register_name || '01')}</span>
            <span>CAJERO: ${escapeHtml(sale.pos_session_code || 'USR-001')}</span>
        </div>
        <div class="sep-single">--------------------------------</div>

        <div class="flex-between">
            <span>CLIENTE:</span>
            <span class="font-bold uppercase">${escapeHtml(sale.client_name || 'CONSUMIDOR FINAL')}</span>
        </div>
        <div class="flex-between">
            <span>CI/RIF:</span>
            <span class="font-bold">${escapeHtml(sale.client_tax_number || 'V-00000000-0')}</span>
        </div>
        ${company.fiscal_address ? `
        <div class="flex-between">
            <span>DIR:</span>
            <span class="text-right text-meta">${escapeHtml(company.fiscal_address)}</span>
        </div>` : ''}
        <div class="sep-single">--------------------------------</div>

        <div class="flex-between font-bold">
            <span>CANT DESCRIPCIÓN</span>
            <span>TOTAL $</span>
        </div>
        <div class="sep-single">--------------------------------</div>
        ${linesHtml}
        <div class="sep-single">--------------------------------</div>

        <!-- Totals -->
        <div class="flex-between">
            <span>Subtotal:</span>
            <span>$  ${sale.subtotal.toFixed(2)}</span>
        </div>
        ${discountHtml}
        ${exemptHtml}
        ${taxBreakdownHtml}
        ${fallbackTaxHtml}
        <div class="sep-single">--------------------------------</div>

        <div class="flex-between font-black total-usd">
            <span>TOTAL USD:</span>
            <span>$ ${sale.total.toFixed(2)}</span>
        </div>
        <div class="flex-between">
            <span>Tasa BCV:</span>
            <span>${exchangeRate.toFixed(2)} Bs./$</span>
        </div>
        <div class="flex-between font-bold total-bs">
            <span>TOTAL VES:</span>
            <span>Bs.  ${sale.total_in_local_currency.toFixed(2)}</span>
        </div>
        <div class="sep-single">--------------------------------</div>

        <!-- Payments -->
        <div class="font-bold" style="padding-bottom: 2px;">PAGOS:</div>
        ${paymentsHtml}
        ${changeHtml}
        <div class="sep-single">--------------------------------</div>

        <!-- Footer -->
        <div class="footer-section">
            <div class="font-bold">¡GRACIAS POR SU COMPRA!</div>
        </div>
        <div class="sep-double">================================</div>

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

export function printThermalReceipt(
    sale: PosSale,
    companyOrName?: string | ReceiptCompanyInfo | null
): void {
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

    const html = generateReceiptHtml(sale, companyOrName);
    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}
