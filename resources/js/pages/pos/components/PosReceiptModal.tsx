import { useEffect, useRef } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import type { PosSale } from '../types/Pos';
import { Printer, CheckCircle } from 'lucide-react';

interface PosReceiptModalProps {
    isOpen: boolean;
    onClose: () => void;
    sale: PosSale | null;
}

export function PosReceiptModal({ isOpen, onClose, sale }: PosReceiptModalProps) {
    const printAreaRef = useRef<HTMLDivElement>(null);

    const handlePrint = () => {
        window.print();
    };

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
                    className="my-2 p-4 bg-white text-black font-mono text-xs rounded-xl border border-dashed border-gray-300 shadow-inner print:m-0 print:p-0 print:border-none print:shadow-none print:w-[80mm]"
                >
                    {/* Header */}
                    <div className="text-center space-y-1 pb-3 border-b border-dashed border-gray-400">
                        <h3 className="font-black text-sm uppercase tracking-wider">COMPROBANTE DE VENTA</h3>
                        <p className="text-[11px] text-gray-700">TICKET POS: {sale.code}</p>
                        <p className="text-[10px] text-gray-600">Fecha: {sale.sale_date}</p>
                        <p className="text-[10px] text-gray-600">
                            Caja: {sale.pos_register_name || '01'} • Cajero: {sale.pos_session_code || ''}
                        </p>
                    </div>

                    {/* Customer */}
                    <div className="py-2 border-b border-dashed border-gray-400 text-[11px]">
                        <p><strong>Cliente:</strong> {sale.client_name || 'Consumidor Final'}</p>
                        {sale.client_tax_number && <p><strong>RIF/CI:</strong> {sale.client_tax_number}</p>}
                    </div>

                    {/* Items table */}
                    <div className="py-2 border-b border-dashed border-gray-400 space-y-1.5">
                        <div className="flex justify-between font-bold text-[10px] uppercase text-gray-600 pb-1">
                            <span>Cant • Descripción</span>
                            <span>Total</span>
                        </div>

                        {(sale.lines || []).map((line, idx) => (
                            <div key={idx} className="flex justify-between items-baseline text-[11px]">
                                <div className="truncate max-w-[190px]">
                                    <span className="font-bold">{line.quantity}</span> x ${line.unit_price.toFixed(2)}{' '}
                                    <span className="capitalize">{line.item_name}</span>
                                </div>
                                <span className="font-bold shrink-0">${line.total.toFixed(2)}</span>
                            </div>
                        ))}
                    </div>

                    {/* Totals */}
                    <div className="py-2 border-b border-dashed border-gray-400 space-y-1 text-[11px]">
                        <div className="flex justify-between">
                            <span>Subtotal:</span>
                            <span>${sale.subtotal.toFixed(2)}</span>
                        </div>
                        {sale.discount_amount > 0 && (
                            <div className="flex justify-between text-gray-700">
                                <span>Descuento:</span>
                                <span>-${sale.discount_amount.toFixed(2)}</span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span>IVA:</span>
                            <span>${sale.tax_amount.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between font-black text-sm pt-1 border-t border-dotted border-gray-400">
                            <span>TOTAL USD:</span>
                            <span>${sale.total.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between font-bold text-xs text-gray-700">
                            <span>TOTAL BS:</span>
                            <span>Bs. {sale.total_in_local_currency.toFixed(2)}</span>
                        </div>
                    </div>

                    {/* Payments breakdown */}
                    <div className="py-2 border-b border-dashed border-gray-400 space-y-1 text-[10px]">
                        <p className="font-bold uppercase text-gray-600">Formas de Pago:</p>
                        {(sale.payments || []).map((p, idx) => (
                            <div key={idx} className="flex justify-between">
                                <span className="capitalize">{p.payment_method.replace('_', ' ')}:</span>
                                <span>
                                    {p.currency === 'USD' ? '$' : 'Bs.'} {p.amount_tendered.toFixed(2)}
                                </span>
                            </div>
                        ))}

                        <div className="flex justify-between pt-1 font-bold">
                            <span>Vuelto USD:</span>
                            <span>${sale.change_amount_usd.toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between font-bold">
                            <span>Vuelto VES:</span>
                            <span>Bs. {sale.change_amount_ves.toFixed(2)}</span>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="text-center pt-3 space-y-0.5 text-[10px] text-gray-600">
                        <p>¡Gracias por su compra!</p>
                        <p>Conserve este ticket como comprobante</p>
                    </div>
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
                        width: 80mm !important;
                        margin: 0 !important;
                        padding: 2mm !important;
                    }
                }
            `}</style>
        </Dialog>
    );
}
