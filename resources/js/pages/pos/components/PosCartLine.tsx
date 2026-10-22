import { Minus, Plus, Trash2 } from 'lucide-react';
import type { PosCartLine as PosCartLineType } from '../types/Pos';
import { usePosCart } from '../context/PosCartContext';
import { usePosSession } from '../context/PosSessionContext';
import { NumberInput } from '@/components/ui/number-input';

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

            <td className="py-2.5 px-2 min-w-0">
                <div className="font-semibold text-foreground truncate" title={line.item.name}>
                    {line.item.name}
                </div>
                <div className="flex items-center gap-2 text-[10px] text-muted-foreground">
                    <span className="font-mono">{line.item.barcode || line.item.sku || line.item.code}</span>
                    {line.discount_percent > 0 && (
                        <span className="text-amber-600 font-semibold">
                            -{line.discount_percent}% desc
                        </span>
                    )}
                    {line.tax_percent > 0 ? (
                        <span className="text-emerald-700 dark:text-emerald-400 font-mono font-medium">
                            +{line.tax_percent}% IVA
                        </span>
                    ) : (
                        <span className="text-muted-foreground/60 font-mono">
                            Exento
                        </span>
                    )}
                </div>
            </td>

            <td className="py-2.5 px-2 text-right font-mono text-muted-foreground w-24">
                $ {line.unit_price.toFixed(2)}
            </td>

            <td className="py-2.5 px-2 w-36" onClick={(e) => e.stopPropagation()}>
                <div className="flex items-center justify-center gap-1 rounded-lg border bg-background p-0.5 shadow-2xs">
                    <button
                        type="button"
                        onClick={() => decrementQuantity(index)}
                        className="flex h-6 w-6 shrink-0 items-center justify-center rounded text-muted-foreground hover:bg-muted hover:text-foreground"
                    >
                        <Minus className="h-3 w-3" />
                    </button>
                    <NumberInput
                        id={`pos-cart-qty-${line.id}`}
                        value={line.quantity}
                        onValueChange={(val) => {
                            if (val > 0) {
                                updateQuantity(index, val);
                            }
                        }}
                        onBlur={(e) => {
                            const parsed = parseFloat(e.target.value);
                            if (!parsed || parsed <= 0) {
                                updateQuantity(index, 1);
                            }
                        }}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                (e.target as HTMLInputElement).blur();
                                const searchInput = document.getElementById('pos-search-input') as HTMLInputElement | null;
                                searchInput?.focus();
                                searchInput?.select();
                            } else if (e.key === 'Escape') {
                                e.preventDefault();
                                (e.target as HTMLInputElement).blur();
                                const searchInput = document.getElementById('pos-search-input') as HTMLInputElement | null;
                                searchInput?.focus();
                            }
                        }}
                        min={0.0001}
                        decimals={4}
                        className="h-6 w-16 border-0 bg-transparent px-1 py-0 text-center font-mono text-xs font-bold shadow-none focus-visible:ring-0 focus-visible:border-transparent"
                    />
                    <button
                        type="button"
                        onClick={() => incrementQuantity(index)}
                        className="flex h-6 w-6 shrink-0 items-center justify-center rounded text-muted-foreground hover:bg-muted hover:text-foreground"
                    >
                        <Plus className="h-3 w-3" />
                    </button>
                </div>
            </td>

            <td className="py-2.5 px-2 text-right font-mono font-bold text-foreground w-28">
                $ {line.total.toFixed(2)}
            </td>

            <td className="py-2.5 pl-1 pr-3 text-center w-9" onClick={(e) => e.stopPropagation()}>
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
