import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Keyboard, ShoppingCart, Banknote, ShieldCheck, HelpCircle } from 'lucide-react';

interface PosShortcutsModalProps {
    isOpen: boolean;
    onClose: () => void;
}

interface ShortcutItem {
    keys: string[];
    action: string;
    description: string;
}

const TERMINAL_SHORTCUTS: ShortcutItem[] = [
    { keys: ['F1'], action: 'Buscar / Escanear', description: 'Enfoca el buscador para escribir o escanear código de barras.' },
    { keys: ['F2', 'Espacio'], action: 'Cobrar Venta', description: 'Abre la ventana de Cobro Express multimoneda (USD / VES).' },
    { keys: ['F3'], action: 'Descuento', description: 'Aplica descuento por monto o porcentaje a la línea o venta.' },
    { keys: ['F4'], action: 'Cliente', description: 'Cambia el cliente o crea uno nuevo de forma express.' },
    { keys: ['F5'], action: 'Sumar Cantidad (+1)', description: 'Incrementa la cantidad del ítem seleccionado en el carrito.' },
    { keys: ['F6'], action: 'Eliminar Ítem', description: 'Remueve la línea seleccionada del carrito de compra.' },
    { keys: ['Enter'], action: 'Agregar Producto', description: 'Inserta el ítem coincidente en el carrito desde el buscador.' },
];

const DRAWER_SHORTCUTS: ShortcutItem[] = [
    { keys: ['F8'], action: 'Poner en Espera', description: 'Pausa el ticket actual y limpia la pantalla para el siguiente cliente.' },
    { keys: ['F9'], action: 'Recuperar Tickets', description: 'Ver y restaurar ventas que estaban en espera.' },
    { keys: ['F10'], action: 'Movimiento de Efectivo', description: 'Registra una entrada (recarga cambio) o salida (sangría / gasto).' },
    { keys: ['F11'], action: 'Lectura X', description: 'Consulta el arqueo parcial acumulado del turno sin cerrar caja.' },
    { keys: ['Esc'], action: 'Cerrar / Cancelar', description: 'Cierra cualquier modal abierto o solicita confirmación para vaciar el carrito.' },
];

const PAYMENT_SHORTCUTS: ShortcutItem[] = [
    { keys: ['1'], action: 'Efectivo USD', description: 'Selecciona dólares en efectivo.' },
    { keys: ['2'], action: 'Efectivo VES', description: 'Selecciona bolívares en efectivo.' },
    { keys: ['3'], action: 'Punto Débito', description: 'Selecciona tarjeta de débito / datáfono.' },
    { keys: ['4'], action: 'Pago Móvil', description: 'Selecciona Pago Móvil en bolívares.' },
    { keys: ['5'], action: 'Biopago', description: 'Selecciona Biopago BDV en bolívares.' },
    { keys: ['6'], action: 'Transferencia', description: 'Selecciona transferencia / Zelle.' },
    { keys: ['Enter'], action: 'Completar Venta', description: 'Confirma la transacción, genera factura, despacho e imprime ticket.' },
];

export function PosShortcutsModal({ isOpen, onClose }: PosShortcutsModalProps) {
    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-3xl p-6 rounded-2xl max-h-[90vh] flex flex-col">
                <DialogHeader className="pb-3 border-b shrink-0">
                    <div className="flex items-center gap-2.5">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <Keyboard className="h-5 w-5" />
                        </div>
                        <div>
                            <DialogTitle className="text-xl font-bold">Atajos de Teclado del Terminal POS</DialogTitle>
                            <DialogDescription className="text-xs text-muted-foreground mt-0.5">
                                Guía rápida para operar el punto de venta a máxima velocidad sin utilizar el ratón
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto py-4 space-y-6 pr-1">
                    {/* Seccion 1: Ventas y Carrito */}
                    <div>
                        <div className="flex items-center gap-2 mb-3 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            <ShoppingCart className="h-4 w-4 text-primary" />
                            <span>Facturación y Carrito</span>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                            {TERMINAL_SHORTCUTS.map((item, index) => (
                                <div
                                    key={index}
                                    className="flex items-start justify-between p-2.5 rounded-xl border bg-muted/20 hover:bg-muted/40 transition-colors gap-3"
                                >
                                    <div className="min-w-0">
                                        <p className="text-xs font-bold text-foreground">{item.action}</p>
                                        <p className="text-[11px] text-muted-foreground leading-tight mt-0.5">{item.description}</p>
                                    </div>
                                    <div className="flex items-center gap-1 shrink-0">
                                        {item.keys.map((k, i) => (
                                            <kbd
                                                key={i}
                                                className="inline-flex h-6 min-w-6 items-center justify-center rounded-md border border-border bg-card px-2 font-mono text-[11px] font-bold text-foreground shadow-2xs"
                                            >
                                                {k}
                                            </kbd>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Seccion 2: Control de Turno y Gaveta */}
                    <div>
                        <div className="flex items-center gap-2 mb-3 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            <ShieldCheck className="h-4 w-4 text-amber-500" />
                            <span>Operaciones de Caja y Turno</span>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                            {DRAWER_SHORTCUTS.map((item, index) => (
                                <div
                                    key={index}
                                    className="flex items-start justify-between p-2.5 rounded-xl border bg-muted/20 hover:bg-muted/40 transition-colors gap-3"
                                >
                                    <div className="min-w-0">
                                        <p className="text-xs font-bold text-foreground">{item.action}</p>
                                        <p className="text-[11px] text-muted-foreground leading-tight mt-0.5">{item.description}</p>
                                    </div>
                                    <div className="flex items-center gap-1 shrink-0">
                                        {item.keys.map((k, i) => (
                                            <kbd
                                                key={i}
                                                className="inline-flex h-6 min-w-6 items-center justify-center rounded-md border border-border bg-card px-2 font-mono text-[11px] font-bold text-foreground shadow-2xs"
                                            >
                                                {k}
                                            </kbd>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Seccion 3: Modal de Cobro */}
                    <div>
                        <div className="flex items-center gap-2 mb-3 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                            <Banknote className="h-4 w-4 text-emerald-500" />
                            <span>Atajos dentro del Modal de Cobro (F2)</span>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                            {PAYMENT_SHORTCUTS.map((item, index) => (
                                <div
                                    key={index}
                                    className="flex items-start justify-between p-2.5 rounded-xl border bg-muted/20 hover:bg-muted/40 transition-colors gap-3"
                                >
                                    <div className="min-w-0">
                                        <p className="text-xs font-bold text-foreground">{item.action}</p>
                                        <p className="text-[11px] text-muted-foreground leading-tight mt-0.5">{item.description}</p>
                                    </div>
                                    <div className="flex items-center gap-1 shrink-0">
                                        {item.keys.map((k, i) => (
                                            <kbd
                                                key={i}
                                                className="inline-flex h-6 min-w-6 items-center justify-center rounded-md border border-border bg-card px-2 font-mono text-[11px] font-bold text-foreground shadow-2xs"
                                            >
                                                {k}
                                            </kbd>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                <DialogFooter className="border-t pt-3 flex justify-between sm:justify-between items-center shrink-0">
                    <span className="text-xs text-muted-foreground flex items-center gap-1">
                        <HelpCircle className="h-3.5 w-3.5" /> Presiona <kbd className="font-mono font-bold bg-muted px-1.5 py-0.5 rounded border text-[10px]">Esc</kbd> para salir
                    </span>
                    <Button onClick={onClose} size="sm" className="font-medium">
                        Entendido
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
