import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { usePosCart } from '../context/PosCartContext';
import { usePosSession } from '../context/PosSessionContext';
import { Percent } from 'lucide-react';

interface PosDiscountModalProps {
    isOpen: boolean;
    onClose: () => void;
}

export function PosDiscountModal({ isOpen, onClose }: PosDiscountModalProps) {
    const { lines, selectedLineIndex, updateDiscount, setGlobalDiscountPercent, globalDiscountPercent } = usePosCart();
    const { session } = usePosSession();

    const selectedLine = lines[selectedLineIndex] ?? null;
    const [mode, setMode] = useState<'line' | 'global'>(selectedLine ? 'line' : 'global');
    const [discountValue, setDiscountValue] = useState<string>(
        selectedLine ? selectedLine.discount_percent.toString() : globalDiscountPercent.toString()
    );

    const maxAllowed = session.max_discount_percent ?? 100;

    const handleApply = (e: React.FormEvent) => {
        e.preventDefault();
        const val = parseFloat(discountValue) || 0;
        const clamped = Math.max(0, Math.min(maxAllowed > 0 ? maxAllowed : 100, val));

        if (mode === 'line' && selectedLine) {
            updateDiscount(selectedLineIndex, clamped);
        } else {
            setGlobalDiscountPercent(clamped);
        }

        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-xs p-6 rounded-2xl">
                <DialogHeader className="border-b pb-3">
                    <DialogTitle className="text-base font-bold flex items-center gap-2">
                        <Percent className="h-4 w-4 text-amber-500" />
                        <span>Aplicar Descuento [F3]</span>
                    </DialogTitle>
                </DialogHeader>

                <form onSubmit={handleApply} className="space-y-4 py-2">
                    {selectedLine && (
                        <div className="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                onClick={() => {
                                    setMode('line');
                                    setDiscountValue(selectedLine.discount_percent.toString());
                                }}
                                className={`p-2 rounded-lg border text-xs font-bold transition-all ${
                                    mode === 'line'
                                        ? 'bg-primary text-primary-foreground border-primary shadow-2xs'
                                        : 'bg-card text-muted-foreground hover:bg-muted'
                                }`}
                            >
                                Línea ({selectedLineIndex + 1})
                            </button>

                            <button
                                type="button"
                                onClick={() => {
                                    setMode('global');
                                    setDiscountValue(globalDiscountPercent.toString());
                                }}
                                className={`p-2 rounded-lg border text-xs font-bold transition-all ${
                                    mode === 'global'
                                        ? 'bg-primary text-primary-foreground border-primary shadow-2xs'
                                        : 'bg-card text-muted-foreground hover:bg-muted'
                                }`}
                            >
                                Venta Global
                            </button>
                        </div>
                    )}

                    <div>
                        <div className="flex items-center justify-between mb-1">
                            <label className="text-xs font-bold text-muted-foreground">Porcentaje (%):</label>
                            {maxAllowed > 0 && maxAllowed < 100 && (
                                <span className="text-[10px] text-muted-foreground">Máximo: {maxAllowed}%</span>
                            )}
                        </div>

                        <div className="relative">
                            <Input
                                autoFocus
                                type="number"
                                min="0"
                                max={maxAllowed > 0 ? maxAllowed : 100}
                                step="any"
                                value={discountValue}
                                onChange={(e) => setDiscountValue(e.target.value)}
                                className="h-11 text-lg font-mono font-bold pr-8"
                                placeholder="0"
                            />
                            <span className="absolute right-3 top-1/2 -translate-y-1/2 font-mono text-muted-foreground font-bold">
                                %
                            </span>
                        </div>
                    </div>

                    <div className="grid grid-cols-4 gap-1.5">
                        {[5, 10, 15, 20].map((preset) => (
                            <Button
                                key={preset}
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setDiscountValue(preset.toString())}
                                className="h-7 text-xs font-mono"
                            >
                                {preset}%
                            </Button>
                        ))}
                    </div>

                    <DialogFooter className="pt-2">
                        <Button type="button" variant="ghost" size="sm" onClick={onClose}>
                            Cancelar
                        </Button>
                        <Button type="submit" size="sm" className="font-bold">
                            Aplicar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
