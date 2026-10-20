import { useState, useMemo } from 'react';
import type { PosCategory, PosItem } from '../types/Pos';
import { usePosCart } from '../context/PosCartContext';
import { usePosSession } from '../context/PosSessionContext';
import { Badge } from '@/components/ui/badge';
import { Layers, Package } from 'lucide-react';

interface PosCatalogGridProps {
    items: PosItem[];
    categories: PosCategory[];
    searchQuery: string;
}

export function PosCatalogGrid({ items, categories, searchQuery }: PosCatalogGridProps) {
    const { addItem } = usePosCart();
    const { rate } = usePosSession();
    const [selectedCategoryId, setSelectedCategoryId] = useState<string | null>(null);

    const filteredItems = useMemo(() => {
        let result = items;

        if (selectedCategoryId) {
            result = result.filter((item) => item.category_id === selectedCategoryId);
        }

        if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase().trim();
            result = result.filter(
                (item) =>
                    item.name.toLowerCase().includes(query) ||
                    item.code.toLowerCase().includes(query) ||
                    (item.sku && item.sku.toLowerCase().includes(query)) ||
                    (item.barcode && item.barcode.toLowerCase().includes(query))
            );
        }

        return result;
    }, [items, selectedCategoryId, searchQuery]);

    return (
        <div className="flex flex-col h-full gap-3 overflow-hidden">
            {/* Category horizontal pills */}
            <div className="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <button
                    type="button"
                    onClick={() => setSelectedCategoryId(null)}
                    className={`shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all ${
                        selectedCategoryId === null
                            ? 'bg-primary text-primary-foreground shadow-xs'
                            : 'bg-muted/70 text-muted-foreground hover:bg-muted hover:text-foreground'
                    }`}
                >
                    <span className="flex items-center gap-1.5">
                        <Layers className="h-3.5 w-3.5" />
                        Todas
                    </span>
                </button>

                {categories.map((cat) => (
                    <button
                        key={cat.id}
                        type="button"
                        onClick={() => setSelectedCategoryId(cat.id)}
                        className={`shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all ${
                            selectedCategoryId === cat.id
                                ? 'bg-primary text-primary-foreground shadow-xs'
                                : 'bg-muted/70 text-muted-foreground hover:bg-muted hover:text-foreground'
                        }`}
                    >
                        {cat.name}
                    </button>
                ))}
            </div>

            {/* Products grid */}
            <div className="flex-1 overflow-y-auto pr-1">
                {filteredItems.length === 0 ? (
                    <div className="flex flex-col items-center justify-center h-48 text-muted-foreground">
                        <Package className="h-10 w-10 stroke-1 text-muted-foreground/50 mb-2" />
                        <p className="text-sm font-medium">No se encontraron productos</p>
                        <p className="text-xs">Prueba con otra categoría o término de búsqueda</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5">
                        {filteredItems.map((item) => {
                            const isOutOfStock = item.moves_stock && item.stock <= 0;
                            const priceVes = (item.unit_price * rate).toFixed(2);

                            return (
                                <button
                                    key={item.id}
                                    type="button"
                                    onClick={() => !isOutOfStock && addItem(item)}
                                    disabled={isOutOfStock}
                                    className={`group flex flex-col justify-between rounded-xl border p-3 text-left transition-all active:scale-[0.98] ${
                                        isOutOfStock
                                            ? 'opacity-50 cursor-not-allowed bg-muted/40 border-border'
                                            : 'bg-card border-border hover:border-primary/50 hover:shadow-sm'
                                    }`}
                                >
                                    <div>
                                        <div className="flex items-start justify-between gap-1 mb-1">
                                            <span className="font-mono text-[10px] text-muted-foreground uppercase">
                                                {item.barcode || item.sku || item.code}
                                            </span>
                                            {item.moves_stock && (
                                                <Badge
                                                    variant={item.stock > 10 ? 'outline' : item.stock > 0 ? 'secondary' : 'destructive'}
                                                    className="text-[10px] px-1.5 py-0 h-4"
                                                >
                                                    {item.stock} {item.measurement_unit_symbol || 'und'}
                                                </Badge>
                                            )}
                                        </div>
                                        <h4 className="font-medium text-xs text-foreground line-clamp-2 group-hover:text-primary transition-colors">
                                            {item.name}
                                        </h4>
                                    </div>

                                    <div className="mt-3 pt-2 border-t border-dashed border-border/70 flex flex-col">
                                        <span className="font-mono text-base font-bold text-foreground">
                                            $ {item.unit_price.toFixed(2)}
                                        </span>
                                        <span className="font-mono text-[11px] text-muted-foreground">
                                            Bs. {priceVes}
                                        </span>
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                )}
            </div>
        </div>
    );
}
