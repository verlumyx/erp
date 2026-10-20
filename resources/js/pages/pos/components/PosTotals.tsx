import { Clock, Pause, Trash2, ArrowRight } from 'lucide-react';
import { usePosCart } from '../context/PosCartContext';
import { Button } from '@/components/ui/button';

interface PosTotalsProps {
    heldSalesCount: number;
    onOpenPaymentModal: () => void;
    onHoldCurrentSale: () => void;
    onOpenHeldModal: () => void;
    onOpenDiscountModal: () => void;
}

export function PosTotals({
    heldSalesCount,
    onOpenPaymentModal,
    onHoldCurrentSale,
    onOpenHeldModal,
    onOpenDiscountModal,
}: PosTotalsProps) {
    const { subtotal, discountAmount, taxAmount, total, totalVes, clearCart, lines } = usePosCart();
    const hasItems = lines.length > 0;

    return (
        <div className="flex flex-col rounded-2xl border bg-card p-4 shadow-xs gap-3">
            {/* Totals summary rows */}
            <div className="space-y-1.5 text-xs">
                <div className="flex items-center justify-between text-muted-foreground">
                    <span>Subtotal:</span>
                    <span className="font-mono font-medium text-foreground">$ {subtotal.toFixed(2)}</span>
                </div>

                <div className="flex items-center justify-between text-muted-foreground">
                    <button
                        type="button"
                        onClick={onOpenDiscountModal}
                        className="text-primary hover:underline flex items-center gap-1 cursor-pointer"
                    >
                        <span>Descuento [F3]:</span>
                    </button>
                    <span className="font-mono font-medium text-amber-600 dark:text-amber-400">
                        -$ {discountAmount.toFixed(2)}
                    </span>
                </div>

                <div className="flex items-center justify-between text-muted-foreground">
                    <span>IVA (16%):</span>
                    <span className="font-mono font-medium text-foreground">$ {taxAmount.toFixed(2)}</span>
                </div>

                <div className="pt-2 border-t flex flex-col gap-0.5">
                    <div className="flex items-baseline justify-between">
                        <span className="text-sm font-bold uppercase tracking-wider text-muted-foreground">
                            TOTAL USD:
                        </span>
                        <span className="font-mono text-2xl font-black text-foreground">
                            $ {total.toFixed(2)}
                        </span>
                    </div>

                    <div className="flex items-baseline justify-between">
                        <span className="text-xs font-medium text-muted-foreground">
                            TOTAL BS:
                        </span>
                        <span className="font-mono text-sm font-bold text-muted-foreground">
                            Bs. {totalVes.toFixed(2)}
                        </span>
                    </div>
                </div>
            </div>

            {/* Bottom Actions Bar */}
            <div className="pt-2 border-t flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={!hasItems}
                        onClick={onHoldCurrentSale}
                        className="h-9 text-xs px-2.5 font-medium"
                        title="Poner ticket actual en espera [F8]"
                    >
                        <Pause className="h-3.5 w-3.5 mr-1 text-amber-500" />
                        <span>En Espera [F8]</span>
                    </Button>

                    <Button
                        variant="outline"
                        size="sm"
                        onClick={onOpenHeldModal}
                        className="h-9 text-xs px-2.5 font-medium relative"
                        title="Ver tickets pausados [F9]"
                    >
                        <Clock className="h-3.5 w-3.5 mr-1 text-blue-500" />
                        <span>Pausados</span>
                        {heldSalesCount > 0 && (
                            <span className="ml-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold text-primary-foreground">
                                {heldSalesCount}
                            </span>
                        )}
                    </Button>

                    <Button
                        variant="ghost"
                        size="sm"
                        disabled={!hasItems}
                        onClick={clearCart}
                        className="h-9 text-xs px-2 text-muted-foreground hover:text-destructive"
                        title="Limpiar carrito [Esc]"
                    >
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                </div>

                <Button
                    size="lg"
                    disabled={!hasItems}
                    onClick={onOpenPaymentModal}
                    className="flex-1 min-w-[180px] h-12 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-base shadow-md transition-all active:scale-[0.99] rounded-xl flex items-center justify-center gap-2 cursor-pointer"
                >
                    <span>COBRAR [F2]</span>
                    <ArrowRight className="h-5 w-5" />
                </Button>
            </div>
        </div>
    );
}
