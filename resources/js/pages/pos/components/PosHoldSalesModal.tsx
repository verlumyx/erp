import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import type { PosCartLine, PosItem, PosSale } from '../types/Pos';
import { usePosCart } from '../context/PosCartContext';
import { usePosSession } from '../context/PosSessionContext';
import { Clock, Play, Trash2, ShoppingBag } from 'lucide-react';
import { posApi } from '../utils/posApi';

interface PosHoldSalesModalProps {
    isOpen: boolean;
    onClose: () => void;
    heldSales: PosSale[];
    onRefreshHeldSales: () => void;
}

export function PosHoldSalesModal({
    isOpen,
    onClose,
    heldSales,
    onRefreshHeldSales,
}: PosHoldSalesModalProps) {
    const { loadLines } = usePosCart();
    const { companyId } = usePosSession();
    const [loadingId, setLoadingId] = useState<string | null>(null);

    const handleResume = (sale: PosSale) => {
        // Convert sale lines back to PosCartLine
        const restoredLines: PosCartLine[] = (sale.lines || []).map((line) => {
            const item: PosItem = {
                id: line.item_id,
                code: line.item_code || '',
                barcode: line.item_barcode,
                name: line.item_name || 'Producto',
                unit_price: line.unit_price,
                stock: 999,
                moves_stock: true,
                measurement_unit_id: '',
                measurement_unit_name: line.measurement_unit_name,
                measurement_unit_symbol: line.measurement_unit_symbol,
                tax_id: line.tax_id || null,
                tax_percent: line.tax_percent,
            };

            return {
                id: line.id,
                item,
                quantity: line.quantity,
                unit_price: line.unit_price,
                discount_percent: line.discount_percent,
                discount_amount: line.discount_amount,
                tax_id: line.tax_id || null,
                tax_percent: line.tax_percent,
                tax_amount: line.tax_amount,
                subtotal: line.subtotal,
                total: line.total,
            };
        });

        const client = sale.client_id
            ? {
                  id: sale.client_id,
                  name: sale.client_name || 'Consumidor Final',
                  tax_number: sale.client_tax_number,
              }
            : null;

        loadLines(restoredLines, client);
        onClose();
    };

    const handleDiscard = async (saleId: string) => {
        if (!confirm('¿Deseas descartar este ticket en espera?')) return;

        setLoadingId(saleId);
        try {
            await posApi(`/${companyId}/pos/held/${saleId}`, { method: 'DELETE' });
            onRefreshHeldSales();
        } catch (err) {
            console.error('Error discarding held sale', err);
        } finally {
            setLoadingId(null);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-xl p-6 rounded-2xl">
                <DialogHeader className="border-b pb-3">
                    <DialogTitle className="text-lg font-bold flex items-center gap-2">
                        <Clock className="h-5 w-5 text-blue-500" />
                        <span>Tickets en Espera (Pausados) [F9]</span>
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-3 py-2 max-h-96 overflow-y-auto">
                    {heldSales.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
                            <ShoppingBag className="h-10 w-10 text-muted-foreground/40 mb-2 stroke-1" />
                            <p className="text-sm font-medium">No hay tickets pausados en esta sesión</p>
                            <p className="text-xs">Usa [F8] para pausar una venta en progreso</p>
                        </div>
                    ) : (
                        heldSales.map((sale) => (
                            <div
                                key={sale.id}
                                className="flex items-center justify-between p-3.5 rounded-xl border bg-card hover:bg-muted/30 transition-all shadow-2xs"
                            >
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2">
                                        <span className="font-mono text-xs font-bold text-primary">
                                            {sale.code}
                                        </span>
                                        <span className="text-xs font-semibold text-foreground">
                                            {sale.client_name || 'Consumidor Final'}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-3 text-xs text-muted-foreground">
                                        <span>{sale.lines?.length || 0} productos</span>
                                        <span>•</span>
                                        <span className="font-mono">{sale.sale_date}</span>
                                    </div>
                                    <div className="font-mono text-sm font-black text-foreground">
                                        $ {sale.total.toFixed(2)} USD
                                        <span className="text-xs font-normal text-muted-foreground ml-2">
                                            (Bs. {sale.total_in_local_currency.toFixed(2)})
                                        </span>
                                    </div>
                                </div>

                                <div className="flex items-center gap-2">
                                    <Button
                                        size="sm"
                                        onClick={() => handleResume(sale)}
                                        className="h-8 text-xs font-bold bg-primary text-primary-foreground gap-1.5"
                                    >
                                        <Play className="h-3.5 w-3.5 fill-current" />
                                        <span>Recuperar</span>
                                    </Button>

                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        disabled={loadingId === sale.id}
                                        onClick={() => handleDiscard(sale.id)}
                                        className="h-8 w-8 p-0 text-muted-foreground hover:text-destructive"
                                        title="Descartar ticket"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
