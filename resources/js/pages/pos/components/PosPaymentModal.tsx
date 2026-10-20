import { useState, useEffect, useMemo, useRef } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { usePosCart } from '../context/PosCartContext';
import { usePosSession } from '../context/PosSessionContext';
import type { PosPaymentInput, PosSale } from '../types/Pos';
import { Banknote, CreditCard, Smartphone, Building, Plus, Trash2, CheckCircle2, DollarSign } from 'lucide-react';
import { posApi } from '../utils/posApi';

interface PosPaymentModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSaleCompleted: (sale: PosSale) => void;
}

type MethodKey = 'cash_usd' | 'cash_ves' | 'card_debit' | 'mobile_payment' | 'transfer';

export function PosPaymentModal({ isOpen, onClose, onSaleCompleted }: PosPaymentModalProps) {
    const { total, totalVes, lines, client, discountAmount, subtotal, taxAmount, clearCart } = usePosCart();
    const { session, rate, companyId } = usePosSession();

    const [activeMethod, setActiveMethod] = useState<MethodKey>('cash_usd');
    const [tenderedInput, setTenderedInput] = useState<string>('');
    const [referenceInput, setReferenceInput] = useState<string>('');
    const [cardDigitsInput, setCardDigitsInput] = useState<string>('');
    const [payments, setPayments] = useState<PosPaymentInput[]>([]);
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const inputRef = useRef<HTMLInputElement>(null);

    // Total already applied in document currency (USD)
    const totalAppliedUsd = useMemo(() => {
        return Number(payments.reduce((sum, p) => sum + p.amount_in_document_currency, 0).toFixed(2));
    }, [payments]);

    // Remaining to pay in USD
    const remainingUsd = useMemo(() => {
        return Number(Math.max(0, total - totalAppliedUsd).toFixed(2));
    }, [total, totalAppliedUsd]);

    // Remaining in VES
    const remainingVes = useMemo(() => {
        return Number((remainingUsd * rate).toFixed(2));
    }, [remainingUsd, rate]);

    // When modal opens or remaining changes, set default tendered input
    useEffect(() => {
        if (isOpen) {
            setPayments([]);
            setErrorMessage(null);
            setReferenceInput('');
            setCardDigitsInput('');
            setActiveMethod('cash_usd');
            setTenderedInput(remainingUsd.toString());
            setTimeout(() => inputRef.current?.focus(), 100);
        }
    }, [isOpen]);

    // Update tendered input when method changes
    const handleSelectMethod = (method: MethodKey) => {
        setActiveMethod(method);
        setReferenceInput('');
        setCardDigitsInput('');

        if (method === 'cash_usd') {
            setTenderedInput(remainingUsd.toString());
        } else if (method === 'cash_ves' || method === 'card_debit' || method === 'mobile_payment') {
            setTenderedInput(remainingVes.toString());
        } else {
            setTenderedInput(remainingUsd.toString());
        }

        setTimeout(() => inputRef.current?.focus(), 50);
    };

    // Calculate change given current payments + current tendered if paying full
    const currentTenderedNum = parseFloat(tenderedInput) || 0;

    const changeUsd = useMemo(() => {
        if (activeMethod === 'cash_usd') {
            const extra = currentTenderedNum - remainingUsd;
            return extra > 0 ? Number(extra.toFixed(2)) : 0;
        }
        return 0;
    }, [activeMethod, currentTenderedNum, remainingUsd]);

    const changeVes = useMemo(() => {
        if (activeMethod === 'cash_ves') {
            const extra = currentTenderedNum - remainingVes;
            return extra > 0 ? Number(extra.toFixed(2)) : 0;
        }
        if (changeUsd > 0) {
            return Number((changeUsd * rate).toFixed(2));
        }
        return 0;
    }, [activeMethod, currentTenderedNum, remainingVes, changeUsd, rate]);

    const handleAddPayment = () => {
        const val = parseFloat(tenderedInput);
        if (isNaN(val) || val <= 0) return;

        let paymentMethod: PosPaymentInput['payment_method'] = 'cash';
        let currency: 'USD' | 'VES' = 'USD';
        let amountApplied = val;
        let amountInDoc = val;

        if (activeMethod === 'cash_usd') {
            paymentMethod = 'cash';
            currency = 'USD';
            amountApplied = Math.min(val, remainingUsd);
            amountInDoc = amountApplied;
        } else if (activeMethod === 'cash_ves') {
            paymentMethod = 'cash';
            currency = 'VES';
            amountApplied = Math.min(val, remainingVes);
            amountInDoc = rate > 0 ? Number((amountApplied / rate).toFixed(2)) : amountApplied;
        } else if (activeMethod === 'card_debit') {
            paymentMethod = 'card_debit';
            currency = 'VES';
            amountApplied = val;
            amountInDoc = rate > 0 ? Number((amountApplied / rate).toFixed(2)) : amountApplied;
        } else if (activeMethod === 'mobile_payment') {
            paymentMethod = 'mobile_payment';
            currency = 'VES';
            amountApplied = val;
            amountInDoc = rate > 0 ? Number((amountApplied / rate).toFixed(2)) : amountApplied;
        } else if (activeMethod === 'transfer') {
            paymentMethod = 'transfer';
            currency = 'USD';
            amountApplied = val;
            amountInDoc = val;
        }

        const newPayment: PosPaymentInput = {
            payment_method: paymentMethod,
            currency,
            amount_tendered: val,
            amount_applied: amountApplied,
            amount_in_document_currency: amountInDoc,
            exchange_rate: currency === 'VES' ? rate : 1.0,
            reference: referenceInput || undefined,
            card_last_digits: cardDigitsInput || undefined,
        };

        const updated = [...payments, newPayment];
        setPayments(updated);
        setReferenceInput('');
        setCardDigitsInput('');

        // Recalculate remaining
        const newTotalApplied = updated.reduce((s, p) => s + p.amount_in_document_currency, 0);
        const newRemUsd = Math.max(0, total - newTotalApplied);

        if (newRemUsd > 0) {
            setTenderedInput(newRemUsd.toFixed(2));
        } else {
            setTenderedInput('0');
        }
    };

    const handleRemovePayment = (index: number) => {
        setPayments((prev) => prev.filter((_, i) => i !== index));
    };

    // Confirm checkout
    const handleConfirmCheckout = async () => {
        // If no split payments yet, add current input as payment
        let finalPayments = [...payments];

        if (finalPayments.length === 0 && currentTenderedNum > 0) {
            let pMethod: PosPaymentInput['payment_method'] = 'cash';
            let pCurr: 'USD' | 'VES' = 'USD';
            let pApplied = currentTenderedNum;
            let pDoc = currentTenderedNum;

            if (activeMethod === 'cash_usd') {
                pMethod = 'cash';
                pCurr = 'USD';
                pApplied = Math.min(currentTenderedNum, remainingUsd);
                pDoc = pApplied;
            } else if (activeMethod === 'cash_ves') {
                pMethod = 'cash';
                pCurr = 'VES';
                pApplied = Math.min(currentTenderedNum, remainingVes);
                pDoc = rate > 0 ? Number((pApplied / rate).toFixed(2)) : pApplied;
            } else if (activeMethod === 'card_debit') {
                pMethod = 'card_debit';
                pCurr = 'VES';
                pApplied = currentTenderedNum;
                pDoc = rate > 0 ? Number((pApplied / rate).toFixed(2)) : pApplied;
            } else if (activeMethod === 'mobile_payment') {
                pMethod = 'mobile_payment';
                pCurr = 'VES';
                pApplied = currentTenderedNum;
                pDoc = rate > 0 ? Number((pApplied / rate).toFixed(2)) : pApplied;
            } else {
                pMethod = 'transfer';
                pCurr = 'USD';
                pApplied = currentTenderedNum;
                pDoc = currentTenderedNum;
            }

            finalPayments.push({
                payment_method: pMethod,
                currency: pCurr,
                amount_tendered: currentTenderedNum,
                amount_applied: pApplied,
                amount_in_document_currency: pDoc,
                exchange_rate: pCurr === 'VES' ? rate : 1.0,
                reference: referenceInput || undefined,
                card_last_digits: cardDigitsInput || undefined,
            });
        }

        const paidSumUsd = finalPayments.reduce((s, p) => s + p.amount_in_document_currency, 0);

        if (paidSumUsd < total - 0.01) {
            setErrorMessage(`Monto insuficiente. Falta por pagar $ ${(total - paidSumUsd).toFixed(2)} USD.`);
            return;
        }

        setIsSubmitting(true);
        setErrorMessage(null);

        try {
            const payload = {
                id: crypto.randomUUID(),
                pos_session_id: session.id,
                client_id: client ? client.id : session.default_client_id,
                currency: 'USD',
                exchange_rate: rate,
                subtotal,
                discount_amount: discountAmount,
                tax_amount: taxAmount,
                total,
                paid_amount: paidSumUsd,
                change_amount_usd: changeUsd,
                change_amount_ves: changeVes,
                lines: lines.map((l) => ({
                    item_id: l.item.id,
                    measurement_unit_id: l.item.measurement_unit_id,
                    quantity: l.quantity,
                    unit_price: l.unit_price,
                    discount_percent: l.discount_percent,
                    discount_amount: l.discount_amount,
                    tax_id: l.tax_id,
                    tax_percent: l.tax_percent,
                    tax_amount: l.tax_amount,
                    subtotal: l.subtotal,
                    total: l.total,
                })),
                payments: finalPayments,
            };

            const response = await posApi<{ sale: PosSale }>(`/${companyId}/pos/checkout`, {
                method: 'POST',
                body: payload,
            });
            const createdSale = response.sale;

            clearCart();
            onClose();
            onSaleCompleted(createdSale);
        } catch (err: any) {
            const msg = err.response?.data?.message || 'Error al procesar la venta en caja.';
            setErrorMessage(msg);
        } finally {
            setIsSubmitting(false);
        }
    };

    const isFullyPaid = (totalAppliedUsd >= total - 0.01) || (payments.length === 0 && currentTenderedNum >= (activeMethod === 'cash_usd' ? remainingUsd : remainingVes));

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-2xl p-6 rounded-2xl">
                <DialogHeader className="border-b pb-3">
                    <DialogTitle className="text-xl font-bold flex items-center justify-between">
                        <span>COBRAR VENTA</span>
                        <span className="font-mono text-xs text-muted-foreground font-normal">
                            Tasa: {rate.toFixed(2)} VES/USD
                        </span>
                    </DialogTitle>
                </DialogHeader>

                {errorMessage && (
                    <div className="rounded-lg bg-destructive/15 p-3 text-xs text-destructive font-medium">
                        {errorMessage}
                    </div>
                )}

                {/* Totals Header Banner */}
                <div className="grid grid-cols-2 gap-3 rounded-xl bg-muted/50 p-4 border border-border">
                    <div>
                        <span className="text-[11px] uppercase font-bold text-muted-foreground block">
                            TOTAL A PAGAR:
                        </span>
                        <div className="flex items-baseline gap-2 mt-0.5">
                            <span className="font-mono text-2xl font-black text-foreground">
                                $ {total.toFixed(2)}
                            </span>
                            <span className="font-mono text-xs font-semibold text-muted-foreground">
                                / Bs. {totalVes.toFixed(2)}
                            </span>
                        </div>
                    </div>

                    <div className="text-right">
                        <span className="text-[11px] uppercase font-bold text-muted-foreground block">
                            RESTANTE:
                        </span>
                        <div className="flex items-baseline justify-end gap-2 mt-0.5">
                            <span className={`font-mono text-2xl font-black ${remainingUsd > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600'}`}>
                                $ {remainingUsd.toFixed(2)}
                            </span>
                            <span className="font-mono text-xs font-semibold text-muted-foreground">
                                / Bs. {remainingVes.toFixed(2)}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Payment Methods Tabs */}
                <div className="space-y-4">
                    <div>
                        <span className="text-xs font-bold uppercase text-muted-foreground block mb-2">
                            Forma de Pago:
                        </span>
                        <div className="grid grid-cols-2 sm:grid-cols-5 gap-2">
                            <button
                                type="button"
                                onClick={() => handleSelectMethod('cash_usd')}
                                className={`flex flex-col items-center justify-center p-2.5 rounded-xl border text-xs font-bold transition-all ${
                                    activeMethod === 'cash_usd'
                                        ? 'border-primary bg-primary text-primary-foreground shadow-xs'
                                        : 'bg-card hover:bg-muted text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                <DollarSign className="h-5 w-5 mb-1" />
                                <span>[1] Efectivo USD</span>
                            </button>

                            <button
                                type="button"
                                onClick={() => handleSelectMethod('cash_ves')}
                                className={`flex flex-col items-center justify-center p-2.5 rounded-xl border text-xs font-bold transition-all ${
                                    activeMethod === 'cash_ves'
                                        ? 'border-primary bg-primary text-primary-foreground shadow-xs'
                                        : 'bg-card hover:bg-muted text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                <Banknote className="h-5 w-5 mb-1" />
                                <span>[2] Efectivo VES</span>
                            </button>

                            <button
                                type="button"
                                onClick={() => handleSelectMethod('card_debit')}
                                className={`flex flex-col items-center justify-center p-2.5 rounded-xl border text-xs font-bold transition-all ${
                                    activeMethod === 'card_debit'
                                        ? 'border-primary bg-primary text-primary-foreground shadow-xs'
                                        : 'bg-card hover:bg-muted text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                <CreditCard className="h-5 w-5 mb-1" />
                                <span>[3] Punto Débito</span>
                            </button>

                            <button
                                type="button"
                                onClick={() => handleSelectMethod('mobile_payment')}
                                className={`flex flex-col items-center justify-center p-2.5 rounded-xl border text-xs font-bold transition-all ${
                                    activeMethod === 'mobile_payment'
                                        ? 'border-primary bg-primary text-primary-foreground shadow-xs'
                                        : 'bg-card hover:bg-muted text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                <Smartphone className="h-5 w-5 mb-1" />
                                <span>[4] Pago Móvil</span>
                            </button>

                            <button
                                type="button"
                                onClick={() => handleSelectMethod('transfer')}
                                className={`flex flex-col items-center justify-center p-2.5 rounded-xl border text-xs font-bold transition-all ${
                                    activeMethod === 'transfer'
                                        ? 'border-primary bg-primary text-primary-foreground shadow-xs'
                                        : 'bg-card hover:bg-muted text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                <Building className="h-5 w-5 mb-1" />
                                <span>[5] Transferencia</span>
                            </button>
                        </div>
                    </div>

                    {/* Tendered Input & Quick buttons */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <label className="text-xs font-bold text-muted-foreground uppercase">
                                Monto recibido ({activeMethod === 'cash_usd' || activeMethod === 'transfer' ? 'USD' : 'VES'}):
                            </label>

                            {/* Quick exact/preset buttons */}
                            <div className="flex items-center gap-1.5">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setTenderedInput(activeMethod === 'cash_usd' ? remainingUsd.toString() : remainingVes.toString())}
                                    className="h-6 text-[10px] px-2 font-mono"
                                >
                                    Exacto
                                </Button>
                                {activeMethod === 'cash_usd' && (
                                    <>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setTenderedInput('20')}
                                            className="h-6 text-[10px] px-2 font-mono"
                                        >
                                            $20
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setTenderedInput('50')}
                                            className="h-6 text-[10px] px-2 font-mono"
                                        >
                                            $50
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setTenderedInput('100')}
                                            className="h-6 text-[10px] px-2 font-mono"
                                        >
                                            $100
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>

                        <div className="flex gap-2">
                            <Input
                                ref={inputRef}
                                type="number"
                                step="any"
                                value={tenderedInput}
                                onChange={(e) => setTenderedInput(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        e.preventDefault();
                                        if (isFullyPaid) {
                                            handleConfirmCheckout();
                                        } else {
                                            handleAddPayment();
                                        }
                                    }
                                }}
                                className="h-12 text-xl font-mono font-bold"
                                placeholder="0.00"
                            />

                            {remainingUsd > 0 && payments.length > 0 && (
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={handleAddPayment}
                                    className="h-12 px-4 font-bold flex items-center gap-1"
                                >
                                    <Plus className="h-4 w-4" />
                                    <span>Agregar</span>
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Reference / card details if electronic */}
                    {(activeMethod === 'card_debit' || activeMethod === 'mobile_payment' || activeMethod === 'transfer') && (
                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <label className="text-[11px] text-muted-foreground block mb-1">
                                    Nro. Referencia / Teléfono:
                                </label>
                                <Input
                                    value={referenceInput}
                                    onChange={(e) => setReferenceInput(e.target.value)}
                                    placeholder="Ej. 123456"
                                    className="h-9 text-xs font-mono"
                                />
                            </div>
                            {activeMethod === 'card_debit' && (
                                <div>
                                    <label className="text-[11px] text-muted-foreground block mb-1">
                                        Últimos 4 dígitos:
                                    </label>
                                    <Input
                                        maxLength={4}
                                        value={cardDigitsInput}
                                        onChange={(e) => setCardDigitsInput(e.target.value)}
                                        placeholder="4321"
                                        className="h-9 text-xs font-mono"
                                    />
                                </div>
                            )}
                        </div>
                    )}

                    {/* Split payments list if any */}
                    {payments.length > 0 && (
                        <div className="border rounded-xl p-3 bg-muted/30 space-y-2">
                            <span className="text-[11px] font-bold uppercase text-muted-foreground">
                                Pagos agregados:
                            </span>
                            <div className="space-y-1.5 max-h-32 overflow-y-auto">
                                {payments.map((p, idx) => (
                                    <div
                                        key={idx}
                                        className="flex items-center justify-between text-xs bg-background p-2 rounded-lg border font-mono"
                                    >
                                        <div className="flex items-center gap-2">
                                            <span className="font-bold text-foreground capitalize">
                                                {p.payment_method.replace('_', ' ')}:
                                            </span>
                                            <span>
                                                {p.currency === 'USD' ? '$' : 'Bs.'} {p.amount_tendered.toFixed(2)}
                                            </span>
                                            {p.currency !== 'USD' && (
                                                <span className="text-[10px] text-muted-foreground">
                                                    (Eq. $ {p.amount_in_document_currency.toFixed(2)})
                                                </span>
                                            )}
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => handleRemovePayment(idx)}
                                            className="text-muted-foreground hover:text-destructive"
                                        >
                                            <Trash2 className="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Change result banner */}
                    <div className="rounded-xl border bg-emerald-500/10 border-emerald-500/20 p-3.5 flex items-center justify-between">
                        <div>
                            <span className="text-xs font-bold text-emerald-700 dark:text-emerald-400 block uppercase">
                                VUELTO A ENTREGAR:
                            </span>
                            <span className="text-xs text-muted-foreground">
                                Equivalente en bolívares calculado al cambio
                            </span>
                        </div>
                        <div className="text-right">
                            <div className="font-mono text-xl font-black text-emerald-700 dark:text-emerald-400">
                                $ {changeUsd.toFixed(2)} USD
                            </div>
                            <div className="font-mono text-xs font-semibold text-muted-foreground">
                                ó Bs. {changeVes.toFixed(2)} VES
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter className="border-t pt-4 flex sm:justify-between items-center gap-3">
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={onClose}
                        disabled={isSubmitting}
                        className="h-11"
                    >
                        [Esc] Volver
                    </Button>

                    <Button
                        type="button"
                        disabled={!isFullyPaid || isSubmitting}
                        onClick={handleConfirmCheckout}
                        className="h-12 px-6 font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-md cursor-pointer flex items-center gap-2"
                    >
                        <CheckCircle2 className="h-5 w-5" />
                        <span>{isSubmitting ? 'Procesando...' : '[Enter] Confirmar e Imprimir'}</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
