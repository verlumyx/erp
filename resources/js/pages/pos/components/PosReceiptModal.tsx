import { useEffect, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import type { PosSale } from '../types/Pos';
import { Printer, CheckCircle } from 'lucide-react';
import { printThermalReceipt } from '../utils/printReceipt';

interface PosReceiptModalProps {
    isOpen: boolean;
    onClose: () => void;
    sale: PosSale | null;
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

export function PosReceiptModal({ isOpen, onClose, sale }: PosReceiptModalProps) {
    const printAreaRef = useRef<HTMLDivElement>(null);
    const { currentCompany } = usePage<{
        currentCompany?: {
            id: string;
            name: string;
            rif?: string;
            fiscal_address?: string;
            taxpayer_type?: string;
            phone?: string;
        };
    }>().props;

    const handlePrint = () => {
        if (!sale) return;
        printThermalReceipt(sale, currentCompany);
    };

    const exchangeRate = Number(sale?.exchange_rate) || 1;
    const dateTime = formatDateTime(sale?.sale_date);

    const controlNumber = (sale as any)?.control_number
        || (sale?.sales_invoice_code
            ? '00-' + String(sale.sales_invoice_code.replace(/[^0-9]/g, '') || '00001045').padStart(8, '0')
            : '00-' + String(sale?.code ? sale.code.replace(/[^0-9]/g, '') : '00001045').padStart(8, '0'));

    const invoiceNumber = sale?.sales_invoice_code || sale?.code || '';

    let exemptBase = 0;
    const taxGroups: Record<number, { base: number; tax: number }> = {};

    (sale?.lines || []).forEach((line) => {
        const percent = Number(line.tax_percent) || 0;
        const subtotal = Number(line.subtotal) || 0;
        const taxAmount = Number(line.tax_amount) || 0;

        if (percent === 0) {
            exemptBase += subtotal;
        } else {
            if (!taxGroups[percent]) {
                taxGroups[percent] = { base: 0, tax: 0 };
            }
            taxGroups[percent].base += subtotal;
            taxGroups[percent].tax += taxAmount;
        }
    });

    const sortedTaxGroups = Object.entries(taxGroups)
        .sort(([a], [b]) => Number(b) - Number(a))
        .map(([percentStr, data]) => ({
            percent: Number(percentStr),
            base: data.base,
            tax: data.tax,
        }));

    useEffect(() => {
        if (isOpen && sale) {
            // Auto trigger print if needed or let user press Enter
            const handleKeyDown = (e: KeyboardEvent) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handlePrint();
                }
            };
            window.addEventListener('keydown', handleKeyDown);
            return () => window.removeEventListener('keydown', handleKeyDown);
        }
    }, [isOpen, sale]);

    if (!sale) return null;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-md p-6 rounded-2xl">
                <DialogHeader className="border-b pb-3 print:hidden">
                    <DialogTitle className="text-lg font-bold flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <CheckCircle className="h-5 w-5 text-emerald-600" />
                            <span>Venta Completada</span>
                        </div>
                        <span className="font-mono text-xs font-bold text-muted-foreground">{sale.code}</span>
                    </DialogTitle>
                </DialogHeader>

                {/* Printable Ticket Receipt */}
                <div
                    ref={printAreaRef}
                    id="pos-thermal-receipt"
                    className="my-2 p-3 bg-white text-black font-mono text-xs rounded-xl border border-dashed border-gray-300 shadow-inner print:m-0 print:p-0 print:border-none print:shadow-none print:w-[58mm] space-y-0.5"
                >
                    <div className="text-center font-bold tracking-tighter text-[11px]">================================</div>
                    <div className="font-bold text-xs uppercase text-center">{currentCompany?.name || 'EMPRESA'}</div>
                    {currentCompany?.rif && (
                        <div className="text-center text-[10px]">RIF: {currentCompany.rif}</div>
                    )}
                    {currentCompany?.fiscal_address && (
                        <div className="text-center text-[9px] leading-tight text-gray-700">{currentCompany.fiscal_address}</div>
                    )}
                    {currentCompany?.phone && (
                        <div className="text-center text-[10px]">Tel: {currentCompany.phone}</div>
                    )}
                    <div className="text-center font-bold tracking-tighter text-[11px]">================================</div>

                    <div className="font-black text-sm uppercase tracking-wider text-center py-0.5">FACTURA</div>
                    <div className="flex justify-between">
                        <span>FACTURA N°:</span>
                        <span className="font-bold">{invoiceNumber}</span>
                    </div>
                    <div className="flex justify-between">
                        <span>N° CONTROL:</span>
                        <span className="font-bold">{controlNumber}</span>
                    </div>
                    <div className="flex justify-between">
                        <span>FECHA: {dateTime.date}</span>
                        <span>HORA: {dateTime.time}</span>
                    </div>
                    <div className="flex justify-between">
                        <span>CAJA: {sale.pos_register_name || '01'}</span>
                        <span>CAJERO: {sale.pos_session_code || 'USR-001'}</span>
                    </div>
                    <div className="text-center font-bold tracking-tighter text-[11px]">--------------------------------</div>

                    <div className="flex justify-between">
                        <span>CLIENTE:</span>
                        <span className="font-bold uppercase">{sale.client_name || 'CONSUMIDOR FINAL'}</span>
                    </div>
                    <div className="flex justify-between">
                        <span>CI/RIF:</span>
                        <span className="font-bold">{sale.client_tax_number || 'V-00000000-0'}</span>
                    </div>
                    {currentCompany?.fiscal_address && (
                        <div className="flex justify-between">
                            <span>DIR:</span>
                            <span className="text-right text-[10px] text-gray-700">{currentCompany.fiscal_address}</span>
                        </div>
                    )}
                    <div className="text-center font-bold tracking-tighter text-[11px]">--------------------------------</div>

                    <div className="flex justify-between font-bold text-[10px] uppercase text-gray-700">
                        <span>CANT DESCRIPCIÓN</span>
                        <span>TOTAL $</span>
                    </div>
                    <div className="text-center font-bold tracking-tighter text-[11px]">--------------------------------</div>

                    {(sale.lines || []).map((line, idx) => {
                        const percent = Number(line.tax_percent) || 0;
                        const taxTag = percent === 0 ? '(E)' : `(${percent}%)`;
                        return (
                            <div key={idx} className="flex justify-between items-baseline gap-2 py-0.5">
                                <span className="break-words">
                                    {line.quantity.toFixed(1)} {line.item_name || line.item_code || 'Producto'} {taxTag}
                                </span>
                                <span className="font-bold shrink-0">{line.total.toFixed(2)}</span>
                            </div>
                        );
                    })}
                    <div className="text-center font-bold tracking-tighter text-[11px]">--------------------------------</div>

                    <div className="flex justify-between">
                        <span>Subtotal:</span>
                        <span>$  {sale.subtotal.toFixed(2)}</span>
                    </div>
                    {sale.discount_amount > 0 && (
                        <div className="flex justify-between text-gray-700">
                            <span>Descuento:</span>
                            <span>-$  {sale.discount_amount.toFixed(2)}</span>
                        </div>
                    )}
                    {exemptBase > 0 && (
                        <div className="flex justify-between">
                            <span>Exento:</span>
                            <span>$  {exemptBase.toFixed(2)}</span>
                        </div>
                    )}
                    {sortedTaxGroups.map((g) => (
                        <div key={g.percent} className="space-y-0.5">
                            <div className="flex justify-between">
                                <span>Base Imponible ({g.percent}%):</span>
                                <span>$  {g.base.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>IVA ({g.percent}%):</span>
                                <span>$  {g.tax.toFixed(2)}</span>
                            </div>
                        </div>
                    ))}
                    <div className="text-center font-bold tracking-tighter text-[11px]">--------------------------------</div>

                    <div className="flex justify-between font-black text-sm">
                        <span>TOTAL USD:</span>
                        <span>$ {sale.total.toFixed(2)}</span>
                    </div>
                    <div className="flex justify-between text-[11px]">
                        <span>Tasa BCV:</span>
                        <span>{exchangeRate.toFixed(2)} Bs./$</span>
                    </div>
                    <div className="flex justify-between font-bold text-xs">
                        <span>TOTAL VES:</span>
                        <span>Bs.  {sale.total_in_local_currency.toFixed(2)}</span>
                    </div>
                    <div className="text-center font-bold tracking-tighter text-[11px]">--------------------------------</div>

                    <div className="font-bold text-[10px] uppercase">PAGOS:</div>
                    {(sale.payments || []).map((p, idx) => (
                        <div key={idx} className="flex justify-between">
                            <span>{PAYMENT_METHOD_NAMES[p.payment_method] || p.payment_method} {p.currency === 'USD' ? 'USD' : 'VES'}:</span>
                            <span>
                                {p.currency === 'USD' ? '$' : 'Bs.'} {p.amount_tendered.toFixed(2)}
                            </span>
                        </div>
                    ))}

                    {(sale.change_amount_usd > 0 || sale.change_amount_ves > 0) && (
                        <div className="pt-0.5">
                            {sale.change_amount_usd > 0 && (
                                <div className="flex justify-between font-bold">
                                    <span>Vuelto USD:</span>
                                    <span>$  {sale.change_amount_usd.toFixed(2)}</span>
                                </div>
                            )}
                            {sale.change_amount_ves > 0 && (
                                <div className="flex justify-between font-bold">
                                    <span>Vuelto VES:</span>
                                    <span>Bs.  {sale.change_amount_ves.toFixed(2)}</span>
                                </div>
                            )}
                        </div>
                    )}
                    <div className="text-center font-bold tracking-tighter text-[11px]">--------------------------------</div>

                    <div className="text-center font-bold py-0.5">¡GRACIAS POR SU COMPRA!</div>
                    <div className="text-center font-bold tracking-tighter text-[11px]">================================</div>
                </div>

                <DialogFooter className="border-t pt-4 flex justify-between items-center print:hidden">
                    <Button variant="ghost" onClick={onClose} className="h-10">
                        Cerrar [Esc]
                    </Button>

                    <Button
                        onClick={handlePrint}
                        className="h-10 px-5 font-bold bg-primary text-primary-foreground gap-2 cursor-pointer"
                    >
                        <Printer className="h-4 w-4" />
                        <span>Imprimir Ticket [Enter]</span>
                    </Button>
                </DialogFooter>
            </DialogContent>

            {/* Print specific CSS */}
            <style>{`
                @media print {
                    body * {
                        visibility: hidden !important;
                    }
                    #pos-thermal-receipt, #pos-thermal-receipt * {
                        visibility: visible !important;
                    }
                    #pos-thermal-receipt {
                        position: absolute !important;
                        left: 0 !important;
                        top: 0 !important;
                        width: 58mm !important;
                        margin: 0 !important;
                        padding: 2mm !important;
                    }
                }
            `}</style>
        </Dialog>
    );
}
