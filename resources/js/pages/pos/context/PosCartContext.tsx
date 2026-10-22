import { createContext, useContext, useState, useMemo, ReactNode } from 'react';
import type { PosCartLine, PosClient, PosItem } from '../types/Pos';

interface PosCartContextType {
    lines: PosCartLine[];
    selectedLineIndex: number;
    setSelectedLineIndex: (index: number) => void;
    lastAddedLineId: string | null;
    client: PosClient | null;
    setClient: (client: PosClient | null) => void;
    globalDiscountPercent: number;
    setGlobalDiscountPercent: (percent: number) => void;
    addItem: (item: PosItem, quantity?: number) => void;
    updateQuantity: (index: number, quantity: number) => void;
    incrementQuantity: (index: number) => void;
    decrementQuantity: (index: number) => void;
    updatePrice: (index: number, price: number) => void;
    updateDiscount: (index: number, discountPercent: number) => void;
    removeLine: (index: number) => void;
    clearCart: () => void;
    loadLines: (lines: PosCartLine[], client?: PosClient | null) => void;
    subtotal: number;
    discountAmount: number;
    taxAmount: number;
    total: number;
    totalVes: number;
    itemsCount: number;
}

const PosCartContext = createContext<PosCartContextType | undefined>(undefined);

export function PosCartProvider({
    defaultClient,
    rate,
    children,
}: {
    defaultClient: PosClient | null;
    rate: number;
    children: ReactNode;
}) {
    const [lines, setLines] = useState<PosCartLine[]>([]);
    const [selectedLineIndex, setSelectedLineIndex] = useState<number>(0);
    const [lastAddedLineId, setLastAddedLineId] = useState<string | null>(null);
    const [client, setClient] = useState<PosClient | null>(defaultClient);
    const [globalDiscountPercent, setGlobalDiscountPercent] = useState<number>(0);

    const calculateLineAmounts = (
        quantity: number,
        unitPrice: number,
        discountPercent: number,
        taxPercent: number
    ) => {
        const gross = quantity * unitPrice;
        const discount_amount = Number((gross * (discountPercent / 100)).toFixed(2));
        const subtotal = Number((gross - discount_amount).toFixed(2));
        const tax_amount = Number((subtotal * (taxPercent / 100)).toFixed(2));
        const total = Number((subtotal + tax_amount).toFixed(2));

        return { discount_amount, subtotal, tax_amount, total };
    };

    const addItem = (item: PosItem, quantity: number = 1) => {
        setLines((prev) => {
            const existingIndex = prev.findIndex((l) => l.item.id === item.id);

            if (existingIndex >= 0) {
                const existing = prev[existingIndex];
                const newQty = existing.quantity + quantity;
                const calc = calculateLineAmounts(
                    newQty,
                    existing.unit_price,
                    existing.discount_percent,
                    existing.tax_percent
                );

                const updated = [...prev];
                updated[existingIndex] = {
                    ...existing,
                    quantity: newQty,
                    ...calc,
                };
                setSelectedLineIndex(existingIndex);
                setLastAddedLineId(existing.id);
                return updated;
            }

            const calc = calculateLineAmounts(quantity, item.unit_price, 0, item.tax_percent);
            const lineId = crypto.randomUUID();
            const newLine: PosCartLine = {
                id: lineId,
                item,
                quantity,
                unit_price: item.unit_price,
                discount_percent: 0,
                tax_id: item.tax_id,
                tax_name: item.tax_name,
                tax_percent: item.tax_percent,
                ...calc,
            };

            const updated = [...prev, newLine];
            setSelectedLineIndex(updated.length - 1);
            setLastAddedLineId(lineId);
            return updated;
        });
    };

    const updateQuantity = (index: number, quantity: number) => {
        if (quantity <= 0) {
            removeLine(index);
            return;
        }

        setLines((prev) => {
            if (!prev[index]) return prev;
            const line = prev[index];
            const calc = calculateLineAmounts(
                quantity,
                line.unit_price,
                line.discount_percent,
                line.tax_percent
            );

            const updated = [...prev];
            updated[index] = {
                ...line,
                quantity,
                ...calc,
            };
            return updated;
        });
    };

    const incrementQuantity = (index: number) => {
        if (lines[index]) {
            updateQuantity(index, lines[index].quantity + 1);
        }
    };

    const decrementQuantity = (index: number) => {
        if (lines[index]) {
            updateQuantity(index, lines[index].quantity - 1);
        }
    };

    const updatePrice = (index: number, unitPrice: number) => {
        if (unitPrice < 0) return;
        setLines((prev) => {
            if (!prev[index]) return prev;
            const line = prev[index];
            const calc = calculateLineAmounts(
                line.quantity,
                unitPrice,
                line.discount_percent,
                line.tax_percent
            );

            const updated = [...prev];
            updated[index] = {
                ...line,
                unit_price: unitPrice,
                ...calc,
            };
            return updated;
        });
    };

    const updateDiscount = (index: number, discountPercent: number) => {
        const clamped = Math.max(0, Math.min(100, discountPercent));
        setLines((prev) => {
            if (!prev[index]) return prev;
            const line = prev[index];
            const calc = calculateLineAmounts(
                line.quantity,
                line.unit_price,
                clamped,
                line.tax_percent
            );

            const updated = [...prev];
            updated[index] = {
                ...line,
                discount_percent: clamped,
                ...calc,
            };
            return updated;
        });
    };

    const removeLine = (index: number) => {
        setLines((prev) => {
            const updated = prev.filter((_, i) => i !== index);
            if (selectedLineIndex >= updated.length) {
                setSelectedLineIndex(Math.max(0, updated.length - 1));
            }
            return updated;
        });
    };

    const handleSetGlobalDiscountPercent = (percent: number) => {
        const clamped = Math.max(0, Math.min(100, percent));
        setGlobalDiscountPercent(clamped);
        setLines((prev) =>
            prev.map((line) => {
                const calc = calculateLineAmounts(
                    line.quantity,
                    line.unit_price,
                    clamped,
                    line.tax_percent
                );
                return {
                    ...line,
                    discount_percent: clamped,
                    ...calc,
                };
            })
        );
    };

    const clearCart = () => {
        setLines([]);
        setSelectedLineIndex(0);
        setGlobalDiscountPercent(0);
        setClient(defaultClient);
        setLastAddedLineId(null);
    };

    const loadLines = (newLines: PosCartLine[], newClient?: PosClient | null) => {
        setLines(newLines);
        if (newClient) {
            setClient(newClient);
        }
        setSelectedLineIndex(0);
        setLastAddedLineId(newLines[newLines.length - 1]?.id ?? null);
    };

    const subtotal = useMemo(() => {
        return Number(lines.reduce((sum, line) => sum + line.quantity * line.unit_price, 0).toFixed(2));
    }, [lines]);

    const discountAmount = useMemo(() => {
        return Number(lines.reduce((sum, line) => sum + line.discount_amount, 0).toFixed(2));
    }, [lines]);

    const taxAmount = useMemo(() => {
        return Number(lines.reduce((sum, line) => sum + line.tax_amount, 0).toFixed(2));
    }, [lines]);

    const total = useMemo(() => {
        const net = subtotal - discountAmount + taxAmount;
        return Number(Math.max(0, net).toFixed(2));
    }, [subtotal, discountAmount, taxAmount]);

    const totalVes = useMemo(() => {
        return Number((total * rate).toFixed(2));
    }, [total, rate]);

    const itemsCount = useMemo(() => {
        return lines.reduce((sum, line) => sum + line.quantity, 0);
    }, [lines]);

    return (
        <PosCartContext.Provider
            value={{
                lines,
                selectedLineIndex,
                setSelectedLineIndex,
                lastAddedLineId,
                client,
                setClient,
                globalDiscountPercent,
                setGlobalDiscountPercent: handleSetGlobalDiscountPercent,
                addItem,
                updateQuantity,
                incrementQuantity,
                decrementQuantity,
                updatePrice,
                updateDiscount,
                removeLine,
                clearCart,
                loadLines,
                subtotal,
                discountAmount,
                taxAmount,
                total,
                totalVes,
                itemsCount,
            }}
        >
            {children}
        </PosCartContext.Provider>
    );
}

export function usePosCart(): PosCartContextType {
    const context = useContext(PosCartContext);
    if (!context) {
        throw new Error('usePosCart must be used within a PosCartProvider');
    }
    return context;
}
