import { ShoppingCart, UserCheck, UserPlus } from 'lucide-react';
import { usePosCart } from '../context/PosCartContext';
import { PosCartLine } from './PosCartLine';
import { Button } from '@/components/ui/button';

interface PosCartProps {
    onOpenClientModal: () => void;
}

export function PosCart({ onOpenClientModal }: PosCartProps) {
    const { lines, selectedLineIndex, setSelectedLineIndex, client } = usePosCart();

    return (
        <div className="flex flex-col h-full rounded-2xl border bg-card shadow-xs overflow-hidden">
            {/* Client header bar */}
            <div className="flex items-center justify-between border-b bg-muted/30 px-3.5 py-2.5">
                <div className="flex items-center gap-2 overflow-hidden">
                    <UserCheck className="h-4 w-4 text-primary shrink-0" />
                    <div className="flex flex-col truncate">
                        <span className="text-[10px] uppercase font-bold text-muted-foreground">Cliente [F4]:</span>
                        <span className="text-xs font-semibold text-foreground truncate">
                            {client ? client.name : 'Consumidor Final'}
                        </span>
                    </div>
                </div>

                <Button
                    variant="ghost"
                    size="sm"
                    onClick={onOpenClientModal}
                    className="h-7 text-xs px-2 text-primary hover:text-primary hover:bg-primary/10"
                >
                    <UserPlus className="h-3.5 w-3.5 mr-1" />
                    Cambiar
                </Button>
            </div>

            {/* Cart items table */}
            <div className="flex-1 overflow-y-auto">
                {lines.length === 0 ? (
                    <div className="flex flex-col items-center justify-center h-full text-muted-foreground p-6">
                        <ShoppingCart className="h-12 w-12 stroke-1 text-muted-foreground/40 mb-3" />
                        <p className="text-sm font-semibold">Carrito vacío</p>
                        <p className="text-xs text-muted-foreground text-center mt-1">
                            Escanea un código de barras o selecciona productos del catálogo
                        </p>
                    </div>
                ) : (
                    <table className="w-full border-collapse">
                        <thead className="sticky top-0 bg-muted/80 backdrop-blur-xs text-[10px] uppercase tracking-wider text-muted-foreground border-b border-border z-10">
                            <tr>
                                <th className="py-2 pl-3 pr-1 text-center w-8">#</th>
                                <th className="py-2 px-2 text-left">Ítem</th>
                                <th className="py-2 px-2 text-right w-20">P. Unit</th>
                                <th className="py-2 px-2 text-center w-28">Cant [F5]</th>
                                <th className="py-2 px-2 text-right w-24">Total</th>
                                <th className="py-2 pl-1 pr-3 text-center w-8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {lines.map((line, idx) => (
                                <PosCartLine
                                    key={line.id}
                                    line={line}
                                    index={idx}
                                    isSelected={selectedLineIndex === idx}
                                    onSelect={() => setSelectedLineIndex(idx)}
                                />
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </div>
    );
}
