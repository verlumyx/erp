import { useState, useMemo } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NumberInput } from '@/components/ui/number-input';
import { CurrencyInput } from '@/components/ui/currency-input';
import { usePosSession } from '../context/PosSessionContext';
import { router } from '@inertiajs/react';
import { Calculator, ShieldAlert, DollarSign, Banknote, CreditCard, Smartphone } from 'lucide-react';

interface PosCloseSessionModalProps {
    isOpen: boolean;
    onClose: () => void;
}

const USD_DENOMINATIONS = [100, 50, 20, 10, 5, 1];
const VES_DENOMINATIONS = [500, 200, 100, 50, 20, 10, 5];

export function PosCloseSessionModal({ isOpen, onClose }: PosCloseSessionModalProps) {
    const { session, companyId } = usePosSession();

    // Denominations count state
    const [usdCounts, setUsdCounts] = useState<Record<number, number>>({
        100: 0,
        50: 0,
        20: 0,
        10: 0,
        5: 0,
        1: 0,
    });

    const [vesCounts, setVesCounts] = useState<Record<number, number>>({
        500: 0,
        200: 0,
        100: 0,
        50: 0,
        20: 0,
        10: 0,
        5: 0,
    });

    const [declaredCardVes, setDeclaredCardVes] = useState<string>('');
    const [declaredMobileVes, setDeclaredMobileVes] = useState<string>('');
    const [notes, setNotes] = useState<string>('');
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [activeTab, setActiveTab] = useState<'usd' | 'ves' | 'electronic'>('usd');

    // Totals calculated from counts
    const totalDeclaredUsd = useMemo(() => {
        return Object.entries(usdCounts).reduce((sum, [denom, count]) => {
            return sum + parseFloat(denom) * (count || 0);
        }, 0);
    }, [usdCounts]);

    const totalDeclaredVes = useMemo(() => {
        return Object.entries(vesCounts).reduce((sum, [denom, count]) => {
            return sum + parseFloat(denom) * (count || 0);
        }, 0);
    }, [vesCounts]);

    const handleUsdChange = (denom: number, countStr: string) => {
        const c = parseInt(countStr) || 0;
        setUsdCounts((prev) => ({ ...prev, [denom]: Math.max(0, c) }));
    };

    const handleVesChange = (denom: number, countStr: string) => {
        const c = parseInt(countStr) || 0;
        setVesCounts((prev) => ({ ...prev, [denom]: Math.max(0, c) }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!confirm('¿Estás seguro de finalizar el turno y realizar el corte de caja definitivo (Corte Z)? Esta acción congelará la sesión.')) {
            return;
        }

        setIsSubmitting(true);

        const denominationsList = [
            ...Object.entries(usdCounts)
                .filter(([_, count]) => count > 0)
                .map(([denom, count]) => ({
                    currency: 'USD',
                    denomination: parseFloat(denom),
                    count,
                    total_amount: parseFloat(denom) * count,
                })),
            ...Object.entries(vesCounts)
                .filter(([_, count]) => count > 0)
                .map(([denom, count]) => ({
                    currency: 'VES',
                    denomination: parseFloat(denom),
                    count,
                    total_amount: parseFloat(denom) * count,
                })),
        ];

        router.post(
            `/${companyId}/pos-sessions/${session.id}/close`,
            {
                declared_cash_usd: totalDeclaredUsd,
                declared_cash_ves: totalDeclaredVes,
                declared_card_ves: parseFloat(declaredCardVes) || 0,
                declared_mobile_ves: parseFloat(declaredMobileVes) || 0,
                closing_notes: notes,
                denominations: denominationsList,
            },
            {
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-3xl sm:max-w-3xl p-6 rounded-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader className="border-b pb-3">
                    <DialogTitle className="text-xl font-bold flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Calculator className="h-5 w-5 text-primary" />
                            <span>Corte de Caja y Arqueo Ciego (Corte Z)</span>
                        </div>
                        <span className="font-mono text-xs px-2 py-0.5 rounded bg-muted font-bold text-muted-foreground">
                            {session.code}
                        </span>
                    </DialogTitle>
                </DialogHeader>

                <div className="flex items-center gap-2 p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-800 dark:text-amber-300">
                    <ShieldAlert className="h-4 w-4 shrink-0" />
                    <span>
                        <strong>Arqueo Ciego:</strong> Cuenta el dinero físico real que tienes en la gaveta e introduce las cantidades. El sistema conciliará y calculará automáticamente las diferencias.
                    </span>
                </div>

                {/* Navigation tabs */}
                <div className="flex items-center gap-2 border-b pb-2">
                    <button
                        type="button"
                        onClick={() => setActiveTab('usd')}
                        className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            activeTab === 'usd'
                                ? 'bg-primary text-primary-foreground shadow-2xs'
                                : 'text-muted-foreground hover:bg-muted'
                        }`}
                    >
                        <DollarSign className="h-3.5 w-3.5" />
                        <span>Efectivo USD ($ {totalDeclaredUsd.toFixed(2)})</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => setActiveTab('ves')}
                        className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            activeTab === 'ves'
                                ? 'bg-primary text-primary-foreground shadow-2xs'
                                : 'text-muted-foreground hover:bg-muted'
                        }`}
                    >
                        <Banknote className="h-3.5 w-3.5" />
                        <span>Efectivo VES (Bs. {totalDeclaredVes.toFixed(2)})</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => setActiveTab('electronic')}
                        className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            activeTab === 'electronic'
                                ? 'bg-primary text-primary-foreground shadow-2xs'
                                : 'text-muted-foreground hover:bg-muted'
                        }`}
                    >
                        <CreditCard className="h-3.5 w-3.5" />
                        <span>Puntos / Vouchers</span>
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4 py-2">
                    {/* Tab: USD Conteo */}
                    {activeTab === 'usd' && (
                        <div className="space-y-3">
                            <span className="text-xs font-bold text-muted-foreground block uppercase">
                                Conteo de Billetes en Dólares (USD):
                            </span>
                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                {USD_DENOMINATIONS.map((denom) => (
                                    <div key={denom} className="flex items-center justify-between p-2.5 rounded-xl border bg-card">
                                        <div className="flex flex-col">
                                            <span className="font-mono text-sm font-bold text-foreground">
                                                $ {denom}
                                            </span>
                                            <span className="text-[10px] text-muted-foreground">
                                                Total: $ {(denom * (usdCounts[denom] || 0)).toFixed(2)}
                                            </span>
                                        </div>
                                        <NumberInput
                                            min={0}
                                            decimals={0}
                                            value={usdCounts[denom] ?? 0}
                                            onValueChange={(val) => handleUsdChange(denom, val > 0 ? val.toString() : '')}
                                            placeholder="0"
                                            className="w-16 h-8 text-center font-mono font-bold text-xs"
                                        />
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Tab: VES Conteo */}
                    {activeTab === 'ves' && (
                        <div className="space-y-3">
                            <span className="text-xs font-bold text-muted-foreground block uppercase">
                                Conteo de Billetes en Bolívares (VES):
                            </span>
                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                {VES_DENOMINATIONS.map((denom) => (
                                    <div key={denom} className="flex items-center justify-between p-2.5 rounded-xl border bg-card">
                                        <div className="flex flex-col">
                                            <span className="font-mono text-sm font-bold text-foreground">
                                                Bs. {denom}
                                            </span>
                                            <span className="text-[10px] text-muted-foreground">
                                                Total: Bs. {(denom * (vesCounts[denom] || 0)).toFixed(2)}
                                            </span>
                                        </div>
                                        <NumberInput
                                            min={0}
                                            decimals={0}
                                            value={vesCounts[denom] ?? 0}
                                            onValueChange={(val) => handleVesChange(denom, val > 0 ? val.toString() : '')}
                                            placeholder="0"
                                            className="w-16 h-8 text-center font-mono font-bold text-xs"
                                        />
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Tab: Electronic Declarations */}
                    {activeTab === 'electronic' && (
                        <div className="space-y-4">
                            <div>
                                <label className="text-xs font-bold text-muted-foreground flex items-center gap-1.5 mb-1.5">
                                    <CreditCard className="h-4 w-4" />
                                    <span>Puntos de Venta / Débito (Total según cierre de lote bancario en Bs):</span>
                                </label>
                                <CurrencyInput
                                    min={0}
                                    decimals={2}
                                    currencySymbol="Bs."
                                    value={declaredCardVes === '' ? null : Number(declaredCardVes)}
                                    onValueChange={(val) => setDeclaredCardVes(val > 0 ? val.toString() : '')}
                                    placeholder="0.00"
                                    className="h-10 font-mono font-bold text-sm"
                                />
                            </div>

                            <div>
                                <label className="text-xs font-bold text-muted-foreground flex items-center gap-1.5 mb-1.5">
                                    <Smartphone className="h-4 w-4" />
                                    <span>Pago Móvil (Total confirmado según comprobantes en Bs):</span>
                                </label>
                                <CurrencyInput
                                    min={0}
                                    decimals={2}
                                    currencySymbol="Bs."
                                    value={declaredMobileVes === '' ? null : Number(declaredMobileVes)}
                                    onValueChange={(val) => setDeclaredMobileVes(val > 0 ? val.toString() : '')}
                                    placeholder="0.00"
                                    className="h-10 font-mono font-bold text-sm"
                                />
                            </div>

                            <div>
                                <label className="text-xs font-bold text-muted-foreground block mb-1.5">
                                    Observaciones o Justificación de Novedades del Turno:
                                </label>
                                <Input
                                    type="text"
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                    placeholder="Ej. Diferencia de Bs. 50 por redondeo en cambio de la mañana"
                                    className="h-10 text-xs"
                                />
                            </div>
                        </div>
                    )}

                    {/* Summary badge */}
                    <div className="flex items-center justify-between p-3 rounded-xl border bg-muted/50 text-xs">
                        <div className="flex items-center gap-4">
                            <div>
                                <span className="text-muted-foreground block text-[10px] uppercase font-bold">Total Físico USD:</span>
                                <span className="font-mono text-base font-black text-foreground">$ {totalDeclaredUsd.toFixed(2)}</span>
                            </div>
                            <div>
                                <span className="text-muted-foreground block text-[10px] uppercase font-bold">Total Físico VES:</span>
                                <span className="font-mono text-base font-black text-foreground">Bs. {totalDeclaredVes.toFixed(2)}</span>
                            </div>
                        </div>
                        <span className="text-[11px] text-muted-foreground italic">
                            Los cálculos esperados se revelarán al emitir el Reporte Z
                        </span>
                    </div>

                    <DialogFooter className="pt-2">
                        <Button type="button" variant="ghost" onClick={onClose} disabled={isSubmitting}>
                            Volver al POS
                        </Button>
                        <Button type="submit" variant="destructive" disabled={isSubmitting} className="font-bold">
                            {isSubmitting ? 'Cerrando turno...' : 'Confirmar Cierre y Emitir Reporte Z'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
