import { Minus, Plus, Trash2 } from 'lucide-react';
import type { PosCartLine as PosCartLineType } from '../types/Pos';
import { usePosCart } from '../context/PosCartContext';
import { usePosSession } from '../context/PosSessionContext';

interface PosCartLineProps {
    line: PosCartLineType;
    index: number;
    isSelected: boolean;
    onSelect: () => void;
}

export function PosCartLine({ line, index, isSelected, onSelect }: PosCartLineProps) {
    const { updateQuantity, incrementQuantity, decrementQuantity, removeLine } = usePosCart();
    const { session } = usePosSession();

    return (
        <tr
            onClick={onSelect}
            className={`border-b transition-colors cursor-pointer text-xs ${
                isSelected
                    ? 'bg-primary/10 border-primary/30 font-medium'
                    : 'hover:bg-muted/50 border-border'
            }`}
        >
            <td className="py-2.5 pl-3 pr-1 text-center font-mono text-muted-foreground w-8">
                {index + 1}
            </td>

            <td className="py-2.5 px-2 max-w-[180px]">
                <div className="font-semibold text-foreground line-clamp-1">{line.item.name}</div>
                <div className="flex items-center gap-2 text-[10px] text-muted-foreground">
                    <span className="font-mono">{line.item.barcode || line.item.sku || line.item.code}</span>
                    {line.discount_percent > 0 && (
                        <span className="text-amber-600 font-semibold">
                            -{line.discount_percent}% desc
                        </span>
                    )}
                </div>
            </td>

            <td className="py-2.5 px-2 text-right font-mono text-muted-foreground w-20">
                $ {line.unit_price.toFixed(2)}
            </td>

            <td className="py-2.5 px-2 w-28" onClick={(e) => e.stopPropagation()}>
                <div className="flex items-center justify-center gap-1 rounded-lg border bg-background p-0.5 shadow-2xs">
                    <button
                        type="button"
                        onClick={() => decrementQuantity(index)}
                        className="flex h-5 w-5 items-center justify-center rounded text-muted-foreground hover:bg-muted hover:text-foreground"
                    >
                        <Minus className="h-3 w-3" />
                    </button>
                    <input
                        type="number"
                        min="1"
                        step="any"
                        value={line.quantity}
                        onChange={(e) => updateQuantity(index, parseFloat(e.target.value) || 1)}
                        className="w-10 text-center font-mono text-xs font-bold border-none p-0 focus:outline-hidden focus:ring-0"
                    />
                    <button
                        type="button"
                        onClick={() => incrementQuantity(index)}
                        className="flex h-5 w-5 items-center justify-center rounded text-muted-foreground hover:bg-muted hover:text-foreground"
                    >
                        <Plus className="h-3 w-3" />
                    </button>
                </div>
            </td>

            <td className="py-2.5 px-2 text-right font-mono font-bold text-foreground w-24">
                $ {line.total.toFixed(2)}
            </td>

            <td className="py-2.5 pl-1 pr-3 text-center w-8" onClick={(e) => e.stopPropagation()}>
                <button
                    type="button"
                    onClick={() => removeLine(index)}
                    className="flex h-6 w-6 items-center justify-center rounded text-muted-foreground/60 hover:text-destructive hover:bg-destructive/10 transition-colors"
                    title="Eliminar fila [F6]"
                >
                    <Trash2 className="h-3.5 w-3.5" />
                </button>
            </td>
        </tr>
    );
}
