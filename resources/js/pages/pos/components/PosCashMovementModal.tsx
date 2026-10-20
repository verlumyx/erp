import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { usePosSession } from '../context/PosSessionContext';
import { ArrowDownCircle, ArrowUpCircle } from 'lucide-react';
import { posApi } from '../utils/posApi';

interface PosCashMovementModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess?: () => void;
}

export function PosCashMovementModal({ isOpen, onClose, onSuccess }: PosCashMovementModalProps) {
    const { session, companyId } = usePosSession();

    const [movementType, setMovementType] = useState<'in' | 'out'>('out');
    const [concept, setConcept] = useState<string>('cash_drop');
    const [currency, setCurrency] = useState<'USD' | 'VES'>('USD');
    const [amount, setAmount] = useState<string>('');
    const [reason, setReason] = useState<string>('');
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        const val = parseFloat(amount);
        if (isNaN(val) || val <= 0) {
            setErrorMessage('Ingresa un monto válido mayor a cero.');
            return;
        }

        if (!reason.trim()) {
            setErrorMessage('Debes indicar el motivo del movimiento.');
            return;
        }

        setIsSubmitting(true);
        setErrorMessage(null);

        try {
            await posApi(`/${companyId}/pos-sessions/${session.id}/cash-movements`, {
                method: 'POST',
                body: {
                    id: crypto.randomUUID(),
                    pos_session_id: session.id,
                    movement_type: movementType,
                    concept,
                    currency,
                    amount: val,
                    reason: reason.trim(),
                },
            });

            onClose();
            if (onSuccess) onSuccess();
        } catch (err: any) {
            setErrorMessage(err.response?.data?.message || 'Error al registrar el movimiento.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-md p-6 rounded-2xl">
                <DialogHeader className="border-b pb-3">
                    <DialogTitle className="text-lg font-bold flex items-center gap-2">
                        <span>Movimiento Manual de Efectivo [F10]</span>
                    </DialogTitle>
                </DialogHeader>

                {errorMessage && (
                    <div className="rounded-lg bg-destructive/15 p-3 text-xs text-destructive font-medium">
                        {errorMessage}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4 py-1">
                    {/* In / Out toggle */}
                    <div className="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            onClick={() => {
                                setMovementType('in');
                                setConcept('change_replenishment');
                            }}
                            className={`flex items-center justify-center gap-2 p-2.5 rounded-xl border text-xs font-bold transition-all ${
                                movementType === 'in'
                                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                                    : 'bg-card text-muted-foreground hover:bg-muted'
                            }`}
                        >
                            <ArrowDownCircle className="h-4 w-4" />
                            <span>Entrada de dinero</span>
                        </button>

                        <button
                            type="button"
                            onClick={() => {
                                setMovementType('out');
                                setConcept('cash_drop');
                            }}
                            className={`flex items-center justify-center gap-2 p-2.5 rounded-xl border text-xs font-bold transition-all ${
                                movementType === 'out'
                                    ? 'bg-amber-600 text-white border-amber-600 shadow-xs'
                                    : 'bg-card text-muted-foreground hover:bg-muted'
                            }`}
                        >
                            <ArrowUpCircle className="h-4 w-4" />
                            <span>Retiro / Sangría</span>
                        </button>
                    </div>

                    {/* Concept select */}
                    <div>
                        <label className="text-xs font-bold text-muted-foreground block mb-1">Concepto:</label>
                        <select
                            value={concept}
                            onChange={(e) => setConcept(e.target.value)}
                            className="w-full h-10 px-3 rounded-lg border bg-background text-xs font-medium focus:ring-2 focus:ring-primary"
                        >
                            {movementType === 'in' ? (
                                <>
                                    <option value="change_replenishment">Recarga de cambio (sencillo)</option>
                                    <option value="other">Otro ingreso</option>
                                </>
                            ) : (
                                <>
                                    <option value="cash_drop">Sangría / Retiro de seguridad a caja fuerte</option>
                                    <option value="expense">Gasto menor de caja chica</option>
                                    <option value="other">Otro retiro</option>
                                </>
                            )}
                        </select>
                    </div>

                    {/* Currency and Amount */}
                    <div className="grid grid-cols-3 gap-2">
                        <div>
                            <label className="text-xs font-bold text-muted-foreground block mb-1">Moneda:</label>
                            <select
                                value={currency}
                                onChange={(e) => setCurrency(e.target.value as 'USD' | 'VES')}
                                className="w-full h-10 px-3 rounded-lg border bg-background text-xs font-bold font-mono"
                            >
                                <option value="USD">USD ($)</option>
                                <option value="VES">VES (Bs.)</option>
                            </select>
                        </div>

                        <div className="col-span-2">
                            <label className="text-xs font-bold text-muted-foreground block mb-1">Monto:</label>
                            <Input
                                type="number"
                                step="any"
                                min="0.01"
                                value={amount}
                                onChange={(e) => setAmount(e.target.value)}
                                placeholder="0.00"
                                className="h-10 text-base font-mono font-bold"
                                required
                            />
                        </div>
                    </div>

                    {/* Reason */}
                    <div>
                        <label className="text-xs font-bold text-muted-foreground block mb-1">
                            Motivo detallado:
                        </label>
                        <Input
                            type="text"
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            placeholder="Ej. Retiro de $300 a bóveda / Compra de cinta térmica"
                            className="h-10 text-xs"
                            required
                        />
                    </div>

                    <DialogFooter className="pt-2">
                        <Button type="button" variant="ghost" onClick={onClose} disabled={isSubmitting}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={isSubmitting} className="font-bold">
                            {isSubmitting ? 'Guardando...' : 'Confirmar Movimiento'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
