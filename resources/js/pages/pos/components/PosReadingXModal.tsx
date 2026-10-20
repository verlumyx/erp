import { useEffect, useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { usePosSession } from '../context/PosSessionContext';
import type { PosZReport } from '../types/Pos';
import { TrendingUp, Printer } from 'lucide-react';
import { posApi } from '../utils/posApi';

interface PosReadingXModalProps {
    isOpen: boolean;
    onClose: () => void;
}

export function PosReadingXModal({ isOpen, onClose }: PosReadingXModalProps) {
    const { session, companyId } = usePosSession();
    const [report, setReport] = useState<PosZReport | null>(null);
    const [loading, setLoading] = useState<boolean>(false);

    useEffect(() => {
        if (isOpen) {
            setLoading(true);
            posApi<{ reading: PosZReport }>(`/${companyId}/pos-sessions/${session.id}/reading-x`)
                .then((res) => setReport(res.reading))
                .catch((err: unknown) => console.error('Error fetching Reading X', err))
                .finally(() => setLoading(false));
        }
    }, [isOpen, session.id, companyId]);

    const handlePrint = () => {
        window.print();
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-md p-6 rounded-2xl">
                <DialogHeader className="border-b pb-3 print:hidden">
                    <DialogTitle className="text-lg font-bold flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <TrendingUp className="h-5 w-5 text-blue-500" />
                            <span>Lectura X (Arqueo Parcial) [F11]</span>
                        </div>
                        <span className="font-mono text-xs font-bold text-muted-foreground">{session.code}</span>
                    </DialogTitle>
                </DialogHeader>

                {loading || !report ? (
                    <div className="py-12 text-center text-xs text-muted-foreground">
                        Cargando balance acumulado del turno...
                    </div>
                ) : (
                    <div className="space-y-4 py-2 font-mono text-xs">
                        <div className="text-center pb-2 border-b border-dashed border-border">
                            <h4 className="font-bold text-sm">LECTURA PARCIAL DE CAJA (CORTE X)</h4>
                            <p className="text-[11px] text-muted-foreground">Turno: {report.session.code} • Caja: {report.session.register_name}</p>
                            <p className="text-[10px] text-muted-foreground">Cajero: {report.session.cashier_name}</p>
                            <p className="text-[10px] text-muted-foreground">Apertura: {report.session.opened_at}</p>
                        </div>

                        <div className="space-y-1.5 pb-2 border-b border-dashed border-border">
                            <div className="flex justify-between">
                                <span>Ventas Realizadas:</span>
                                <span className="font-bold">{report.summary.sales_count}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Tickets Anulados:</span>
                                <span>{report.summary.cancelled_sales_count}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Ticket Promedio:</span>
                                <span>$ {report.summary.average_ticket.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Total Ventas Brutas:</span>
                                <span>$ {report.summary.total_gross.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Descuentos Otorgados:</span>
                                <span>-$ {report.summary.discounts.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between font-bold text-sm pt-1 border-t">
                                <span>TOTAL FACTURADO:</span>
                                <span>$ {report.summary.total_net.toFixed(2)}</span>
                            </div>
                        </div>

                        <div className="space-y-1.5 pb-2 border-b border-dashed border-border">
                            <span className="font-bold uppercase text-[10px] text-muted-foreground">Cobros Recibidos:</span>
                            <div className="flex justify-between">
                                <span>Efectivo USD:</span>
                                <span>$ {report.payments.cash_usd.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Efectivo VES:</span>
                                <span>Bs. {report.payments.cash_ves.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Punto de Venta / Débito:</span>
                                <span>Bs. {report.payments.card_ves.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Pago Móvil:</span>
                                <span>Bs. {report.payments.mobile_ves.toFixed(2)}</span>
                            </div>
                            {report.payments.biopago_ves !== undefined && report.payments.biopago_ves > 0 && (
                                <div className="flex justify-between">
                                    <span>Biopago:</span>
                                    <span>Bs. {report.payments.biopago_ves.toFixed(2)}</span>
                                </div>
                            )}
                        </div>

                        <div className="space-y-1.5 bg-muted/40 p-2.5 rounded-lg border">
                            <span className="font-bold uppercase text-[10px] text-muted-foreground">Efectivo Esperado Actual:</span>
                            <div className="flex justify-between font-bold">
                                <span>USD en gaveta:</span>
                                <span className="text-emerald-600 dark:text-emerald-400">$ {report.audit_usd.expected.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between font-bold">
                                <span>VES en gaveta:</span>
                                <span className="text-emerald-600 dark:text-emerald-400">Bs. {report.audit_ves.expected.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                )}

                <DialogFooter className="border-t pt-4 flex justify-between items-center print:hidden">
                    <Button variant="ghost" onClick={onClose} className="h-10">
                        Cerrar [Esc]
                    </Button>
                    <Button onClick={handlePrint} className="h-10 font-bold gap-2">
                        <Printer className="h-4 w-4" />
                        <span>Imprimir Lectura</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
