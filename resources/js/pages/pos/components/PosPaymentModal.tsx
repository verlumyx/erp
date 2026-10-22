import { useState, useEffect, useMemo, useRef } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { CurrencyInput } from '@/components/ui/currency-input';
import { usePosCart } from '../context/PosCartContext';
import { usePosSession } from '../context/PosSessionContext';
import type { PosPaymentInput, PosSale } from '../types/Pos';
import {
    Banknote,
    CreditCard,
    Smartphone,
    Building,
    Plus,
    Trash2,
    CheckCircle2,
    DollarSign,
    Fingerprint,
    Receipt,
    WalletCards,
    RotateCcw,
    AlertCircle,
    Coins,
} from 'lucide-react';
import { posApi } from '../utils/posApi';

interface PosPaymentModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSaleCompleted: (sale: PosSale) => void;
}

export type MethodKey = 'card_debit' | 'biopago' | 'mobile_payment' | 'cash_ves' | 'cash_usd' | 'transfer';

interface MethodConfig {
    key: MethodKey;
    shortcut: string;
    label: string;
    currency: 'USD' | 'VES';
    icon: typeof DollarSign;
    badgeColor: string;
    description: string;
}

const METHODS: MethodConfig[] = [
    {
        key: 'card_debit',
        shortcut: '[1]',
        label: 'Punto Débito',
        currency: 'VES',
        icon: CreditCard,
        badgeColor: 'border-indigo-500/30 text-indigo-600 dark:text-indigo-400 bg-indigo-500/10',
        description: 'Punto de venta / tarjeta',
    },
    {
        key: 'biopago',
        shortcut: '[2]',
        label: 'Biopago',
        currency: 'VES',
        icon: Fingerprint,
        badgeColor: 'border-rose-500/30 text-rose-600 dark:text-rose-400 bg-rose-500/10',
        description: 'Biopago BDV huella',
    },
    {
        key: 'mobile_payment',
        shortcut: '[3]',
        label: 'Pago Móvil',
        currency: 'VES',
        icon: Smartphone,
        badgeColor: 'border-amber-500/30 text-amber-600 dark:text-amber-400 bg-amber-500/10',
        description: 'Pago móvil interbancario',
    },
    {
        key: 'cash_ves',
        shortcut: '[4]',
        label: 'Efectivo VES',
        currency: 'VES',
        icon: Banknote,
        badgeColor: 'border-blue-500/30 text-blue-600 dark:text-blue-400 bg-blue-500/10',
        description: 'Bolívares en efectivo',
    },
    {
        key: 'cash_usd',
        shortcut: '[5]',
        label: 'Efectivo USD',
        currency: 'USD',
        icon: DollarSign,
        badgeColor: 'border-emerald-500/30 text-emerald-600 dark:text-emerald-400 bg-emerald-500/10',
        description: 'Dólares en efectivo',
    },
    {
        key: 'transfer',
        shortcut: '[6]',
        label: 'Transferencia',
        currency: 'USD',
        icon: Building,
        badgeColor: 'border-cyan-500/30 text-cyan-600 dark:text-cyan-400 bg-cyan-500/10',
        description: 'Transferencia / Zelle',
    },
];

export function PosPaymentModal({ isOpen, onClose, onSaleCompleted }: PosPaymentModalProps) {
    const { total, totalVes, lines, client, discountAmount, subtotal, taxAmount, clearCart } = usePosCart();
    const { session, rate, companyId } = usePosSession();

    const [activeMethod, setActiveMethod] = useState<MethodKey>('card_debit');
    const [tenderedInput, setTenderedInput] = useState<string>('');
    const [referenceInput, setReferenceInput] = useState<string>('');
    const [cardDigitsInput, setCardDigitsInput] = useState<string>('');
    const [payments, setPayments] = useState<PosPaymentInput[]>([]);
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const inputRef = useRef<HTMLInputElement>(null);

    // Sum of all payments already added in document currency (USD)
    const totalAppliedUsd = useMemo(() => {
        return Number(payments.reduce((sum, p) => sum + p.amount_in_document_currency, 0).toFixed(2));
    }, [payments]);

    // Remaining balance to cover in USD
    const remainingUsd = useMemo(() => {
        return Number(Math.max(0, total - totalAppliedUsd).toFixed(2));
    }, [total, totalAppliedUsd]);

    // Remaining balance in VES
    const remainingVes = useMemo(() => {
        return Number((remainingUsd * rate).toFixed(2));
    }, [remainingUsd, rate]);

    const activeConfig = useMemo(() => {
        return METHODS.find((m) => m.key === activeMethod) || METHODS[0];
    }, [activeMethod]);

    const currentTenderedNum = parseFloat(tenderedInput) || 0;

    // Default amount for the current method based on remaining balance
    const getRemainingForMethod = (method: MethodKey, remUsd: number) => {
        if (method === 'cash_usd' || method === 'transfer') {
            return remUsd > 0 ? remUsd.toString() : '';
        }
        const remVes = Number((remUsd * rate).toFixed(2));
        return remVes > 0 ? remVes.toString() : '';
    };

    // When modal opens, reset state and focus input
    useEffect(() => {
        if (isOpen) {
            setPayments([]);
            setErrorMessage(null);
            setReferenceInput('');
            setCardDigitsInput('');
            setActiveMethod('card_debit');
            setTenderedInput(getRemainingForMethod('card_debit', total));
            setTimeout(() => {
                inputRef.current?.focus();
                inputRef.current?.select();
            }, 100);
        }
    }, [isOpen, total, rate]);

    // Switch payment method
    const handleSelectMethod = (method: MethodKey) => {
        setActiveMethod(method);
        setReferenceInput('');
        setCardDigitsInput('');
        setTenderedInput(getRemainingForMethod(method, remainingUsd));
        setTimeout(() => {
            inputRef.current?.focus();
            inputRef.current?.select();
        }, 50);
    };

    // Change calculation (Vuelto)
    const changeUsd = useMemo(() => {
        let extraUsd = payments.reduce((sum, p) => {
            if (p.currency === 'USD' && p.payment_method === 'cash') {
                return sum + Math.max(0, p.amount_tendered - p.amount_applied);
            }
            return sum;
        }, 0);

        // If paying single cash USD exceeding remainder
        if (payments.length === 0 && activeMethod === 'cash_usd' && currentTenderedNum > remainingUsd) {
            extraUsd += currentTenderedNum - remainingUsd;
        }

        return Number(extraUsd.toFixed(2));
    }, [payments, currentTenderedNum, remainingUsd, activeMethod]);

    const changeVes = useMemo(() => {
        let extraVes = payments.reduce((sum, p) => {
            if (p.currency === 'VES' && p.payment_method === 'cash') {
                return sum + Math.max(0, p.amount_tendered - p.amount_applied);
            }
            return sum;
        }, 0);

        // If paying single cash VES exceeding remainder
        if (payments.length === 0 && activeMethod === 'cash_ves' && currentTenderedNum > remainingVes) {
            extraVes += currentTenderedNum - remainingVes;
        }

        if (changeUsd > 0 && extraVes === 0) {
            return Number((changeUsd * rate).toFixed(2));
        }

        return Number(extraVes.toFixed(2));
    }, [payments, currentTenderedNum, remainingVes, activeMethod, changeUsd, rate]);

    // Add a payment method to the list
    const handleAddPayment = () => {
        const val = parseFloat(tenderedInput);
        if (isNaN(val) || val <= 0) {
            setErrorMessage('Por favor ingresa un monto válido mayor a cero.');
            return;
        }

        setErrorMessage(null);

        let pMethod: PosPaymentInput['payment_method'] = 'cash';
        let currency: 'USD' | 'VES' = 'USD';
        let amountTendered = val;
        let amountApplied = val;
        let amountInDoc = val;

        if (activeMethod === 'cash_usd') {
            pMethod = 'cash';
            currency = 'USD';
            amountTendered = val;
            amountApplied = Math.min(val, remainingUsd);
            amountInDoc = amountApplied;
        } else if (activeMethod === 'cash_ves') {
            pMethod = 'cash';
            currency = 'VES';
            amountTendered = val;
            amountApplied = Math.min(val, remainingVes);
            amountInDoc = rate > 0 ? Number((amountApplied / rate).toFixed(2)) : amountApplied;
        } else if (activeMethod === 'card_debit') {
            pMethod = 'card_debit';
            currency = 'VES';
            amountApplied = Math.min(val, remainingVes);
            amountTendered = amountApplied;
            amountInDoc = rate > 0 ? Number((amountApplied / rate).toFixed(2)) : amountApplied;
        } else if (activeMethod === 'mobile_payment') {
            pMethod = 'mobile_payment';
            currency = 'VES';
            amountApplied = Math.min(val, remainingVes);
            amountTendered = amountApplied;
            amountInDoc = rate > 0 ? Number((amountApplied / rate).toFixed(2)) : amountApplied;
        } else if (activeMethod === 'biopago') {
            pMethod = 'biopago';
            currency = 'VES';
            amountApplied = Math.min(val, remainingVes);
            amountTendered = amountApplied;
            amountInDoc = rate > 0 ? Number((amountApplied / rate).toFixed(2)) : amountApplied;
        } else if (activeMethod === 'transfer') {
            pMethod = 'transfer';
            currency = 'USD';
            amountApplied = Math.min(val, remainingUsd);
            amountTendered = amountApplied;
            amountInDoc = amountApplied;
        }

        const newPayment: PosPaymentInput = {
            payment_method: pMethod,
            currency,
            amount_tendered: amountTendered,
            amount_applied: amountApplied,
            amount_in_document_currency: amountInDoc,
            exchange_rate: currency === 'VES' ? rate : 1.0,
            reference: referenceInput.trim() || undefined,
            card_last_digits: cardDigitsInput.trim() || undefined,
        };

        const updated = [...payments, newPayment];
        setPayments(updated);
        setReferenceInput('');
        setCardDigitsInput('');

        // Recalculate remaining
        const newTotalApplied = updated.reduce((s, p) => s + p.amount_in_document_currency, 0);
        const newRemUsd = Math.max(0, Number((total - newTotalApplied).toFixed(2)));

        setTenderedInput(getRemainingForMethod(activeMethod, newRemUsd));
        setTimeout(() => {
            inputRef.current?.focus();
            inputRef.current?.select();
        }, 50);
    };

    // Remove a payment from the list
    const handleRemovePayment = (index: number) => {
        const updated = payments.filter((_, i) => i !== index);
        setPayments(updated);
        const newTotalApplied = updated.reduce((s, p) => s + p.amount_in_document_currency, 0);
        const newRemUsd = Math.max(0, Number((total - newTotalApplied).toFixed(2)));
        setTenderedInput(getRemainingForMethod(activeMethod, newRemUsd));
    };

    // Check if the sale is fully paid
    const isPendingFullyPaid = useMemo(() => {
        // If payments list already satisfies total
        if (totalAppliedUsd >= total - 0.009) return true;

        // If no added payments, check if single input satisfies total
        if (payments.length === 0 && currentTenderedNum > 0) {
            if (activeMethod === 'cash_usd' || activeMethod === 'transfer') {
                return currentTenderedNum >= total - 0.009;
            }
            return currentTenderedNum >= totalVes - 0.05;
        }

        // If payments exist and current input covers the exact remaining balance
        if (payments.length > 0 && currentTenderedNum > 0) {
            if (activeMethod === 'cash_usd' || activeMethod === 'transfer') {
                return currentTenderedNum >= remainingUsd - 0.009;
            }
            return currentTenderedNum >= remainingVes - 0.05;
        }

        return false;
    }, [totalAppliedUsd, total, payments.length, currentTenderedNum, activeMethod, totalVes, remainingUsd, remainingVes]);

    // Confirm checkout and finalize sale
    const handleConfirmCheckout = async () => {
        let finalPayments = [...payments];

        // If there is still balance remaining and current input covers it, automatically add it
        const pendingRemUsd = Math.max(0, Number((total - finalPayments.reduce((s, p) => s + p.amount_in_document_currency, 0)).toFixed(2)));

        if (pendingRemUsd > 0.009 && currentTenderedNum > 0) {
            let pMethod: PosPaymentInput['payment_method'] = 'cash';
            let pCurr: 'USD' | 'VES' = 'USD';
            let pTendered = currentTenderedNum;
            let pApplied = currentTenderedNum;
            let pDoc = currentTenderedNum;

            if (activeMethod === 'cash_usd') {
                pMethod = 'cash';
                pCurr = 'USD';
                pApplied = Math.min(currentTenderedNum, pendingRemUsd);
                pDoc = pApplied;
            } else if (activeMethod === 'cash_ves') {
                pMethod = 'cash';
                pCurr = 'VES';
                pApplied = Math.min(currentTenderedNum, Number((pendingRemUsd * rate).toFixed(2)));
                pDoc = rate > 0 ? Number((pApplied / rate).toFixed(2)) : pApplied;
            } else if (activeMethod === 'card_debit') {
                pMethod = 'card_debit';
                pCurr = 'VES';
                pApplied = Math.min(currentTenderedNum, Number((pendingRemUsd * rate).toFixed(2)));
                pTendered = pApplied;
                pDoc = rate > 0 ? Number((pApplied / rate).toFixed(2)) : pApplied;
            } else if (activeMethod === 'mobile_payment') {
                pMethod = 'mobile_payment';
                pCurr = 'VES';
                pApplied = Math.min(currentTenderedNum, Number((pendingRemUsd * rate).toFixed(2)));
                pTendered = pApplied;
                pDoc = rate > 0 ? Number((pApplied / rate).toFixed(2)) : pApplied;
            } else if (activeMethod === 'biopago') {
                pMethod = 'biopago';
                pCurr = 'VES';
                pApplied = Math.min(currentTenderedNum, Number((pendingRemUsd * rate).toFixed(2)));
                pTendered = pApplied;
                pDoc = rate > 0 ? Number((pApplied / rate).toFixed(2)) : pApplied;
            } else {
                pMethod = 'transfer';
                pCurr = 'USD';
                pApplied = Math.min(currentTenderedNum, pendingRemUsd);
                pTendered = pApplied;
                pDoc = pApplied;
            }

            finalPayments.push({
                payment_method: pMethod,
                currency: pCurr,
                amount_tendered: pTendered,
                amount_applied: pApplied,
                amount_in_document_currency: pDoc,
                exchange_rate: pCurr === 'VES' ? rate : 1.0,
                reference: referenceInput.trim() || undefined,
                card_last_digits: cardDigitsInput.trim() || undefined,
            });
        }

        const paidSumUsd = finalPayments.reduce((s, p) => s + p.amount_in_document_currency, 0);

        if (paidSumUsd < total - 0.01) {
            setErrorMessage(`Monto insuficiente. Falta por registrar $ ${(total - paidSumUsd).toFixed(2)} USD (Bs. ${((total - paidSumUsd) * rate).toFixed(2)}).`);
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

            clearCart();
            onClose();
            onSaleCompleted(response.sale);
        } catch (err: any) {
            const msg = err.response?.data?.message || err.message || 'Error al procesar el cobro en caja.';
            setErrorMessage(msg);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleDetailKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (isPendingFullyPaid) {
                handleConfirmCheckout();
            } else {
                handleAddPayment();
            }
        }
    };

    // Keyboard shortcuts (1-6 for quick method switch, Enter for confirming checkout)
    useEffect(() => {
        if (!isOpen) return;

        const handleKeyDown = (e: KeyboardEvent) => {
            const target = e.target as HTMLElement | null;
            const isTyping = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA');

            if (!isTyping) {
                if (e.key === '1') {
                    e.preventDefault();
                    handleSelectMethod('card_debit');
                } else if (e.key === '2') {
                    e.preventDefault();
                    handleSelectMethod('biopago');
                } else if (e.key === '3') {
                    e.preventDefault();
                    handleSelectMethod('mobile_payment');
                } else if (e.key === '4') {
                    e.preventDefault();
                    handleSelectMethod('cash_ves');
                } else if (e.key === '5') {
                    e.preventDefault();
                    handleSelectMethod('cash_usd');
                } else if (e.key === '6') {
                    e.preventDefault();
                    handleSelectMethod('transfer');
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (isPendingFullyPaid && !isSubmitting) {
                        handleConfirmCheckout();
                    }
                }
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isOpen, remainingUsd, rate, isPendingFullyPaid, isSubmitting, handleConfirmCheckout]);

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-4xl sm:max-w-5xl w-[96vw] max-h-[92vh] p-0 gap-0 overflow-hidden rounded-2xl flex flex-col bg-background shadow-2xl border">
                {/* Header */}
                <DialogHeader className="px-6 py-4 border-b bg-card/60 flex flex-row items-center justify-between shrink-0">
                    <div className="flex items-center gap-3">
                        <div className="p-2 rounded-xl bg-primary/10 text-primary">
                            <Receipt className="h-5 w-5" />
                        </div>
                        <div>
                            <DialogTitle className="text-xl font-black tracking-tight text-foreground">
                                COBRAR VENTA
                            </DialogTitle>
                            <p className="text-xs text-muted-foreground">
                                Caja: <span className="font-semibold text-foreground">{session.pos_register_name || session.code}</span> • Turno: <span className="font-semibold text-foreground">{session.code}</span>
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <div className="px-3 py-1.5 rounded-xl border bg-muted/50 font-mono text-xs flex items-center gap-2">
                            <span className="text-[10px] uppercase font-bold text-muted-foreground">Tasa Oficial:</span>
                            <span className="font-black text-foreground">{rate.toFixed(2)} VES/USD</span>
                        </div>
                    </div>
                </DialogHeader>

                {errorMessage && (
                    <div className="mx-6 mt-4 rounded-xl bg-destructive/15 border border-destructive/20 p-3.5 text-xs text-destructive font-medium flex items-center gap-2">
                        <AlertCircle className="h-4 w-4 shrink-0" />
                        <span>{errorMessage}</span>
                    </div>
                )}

                {/* 2-Column Main Content */}
                <div className="flex-1 min-h-0 overflow-hidden grid grid-cols-1 lg:grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-border">
                    {/* LEFT COLUMN: Payment Method & Input Form (Col span 7) */}
                    <div className="lg:col-span-7 p-6 overflow-y-auto space-y-5">
                        {/* 1. Forma de Pago Selector */}
                        <div>
                            <div className="flex items-center justify-between mb-2.5">
                                <span className="text-xs font-black uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                    <WalletCards className="h-3.5 w-3.5" />
                                    <span>1. Forma de Pago:</span>
                                </span>
                                <span className="text-[11px] text-muted-foreground">
                                    Atajos de teclado: <kbd className="px-1.5 py-0.5 rounded bg-muted font-mono font-bold text-[10px]">1</kbd> - <kbd className="px-1.5 py-0.5 rounded bg-muted font-mono font-bold text-[10px]">6</kbd>
                                </span>
                            </div>

                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                {METHODS.map((method) => {
                                    const IconComponent = method.icon;
                                    const isSelected = activeMethod === method.key;
                                    return (
                                        <button
                                            key={method.key}
                                            type="button"
                                            onClick={() => handleSelectMethod(method.key)}
                                            className={`relative flex flex-col items-start p-3 rounded-xl border text-left transition-all cursor-pointer ${
                                                isSelected
                                                    ? 'border-primary bg-primary text-primary-foreground shadow-sm ring-2 ring-primary/20'
                                                    : 'bg-card hover:bg-muted/70 text-card-foreground hover:border-border'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between w-full mb-1.5">
                                                <IconComponent className={`h-5 w-5 ${isSelected ? 'text-primary-foreground' : method.badgeColor.split(' ')[1]}`} />
                                                <span className={`text-[10px] font-mono font-bold px-1.5 py-0.5 rounded ${isSelected ? 'bg-primary-foreground/20 text-primary-foreground' : 'bg-muted text-muted-foreground'}`}>
                                                    {method.currency}
                                                </span>
                                            </div>
                                            <span className="font-bold text-xs block leading-tight">
                                                {method.shortcut} {method.label}
                                            </span>
                                            <span className={`text-[10px] truncate max-w-full block mt-0.5 ${isSelected ? 'text-primary-foreground/80' : 'text-muted-foreground'}`}>
                                                {method.description}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* 2. Amount Input & Quick Presets */}
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <label className="text-xs font-black uppercase tracking-wider text-muted-foreground">
                                    2. Monto a Registrar ({activeConfig.currency}):
                                </label>

                                <div className="flex items-center gap-1.5">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setTenderedInput(getRemainingForMethod(activeMethod, remainingUsd))}
                                        className="h-7 text-[11px] px-2.5 font-mono font-bold text-primary hover:bg-primary/10"
                                    >
                                        <RotateCcw className="h-3 w-3 mr-1" />
                                        Monto Restante
                                    </Button>

                                    {activeConfig.currency === 'USD' && (
                                        <>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setTenderedInput('10')}
                                                className="h-7 text-[11px] px-2 font-mono font-bold"
                                            >
                                                $10
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setTenderedInput('20')}
                                                className="h-7 text-[11px] px-2 font-mono font-bold"
                                            >
                                                $20
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setTenderedInput('50')}
                                                className="h-7 text-[11px] px-2 font-mono font-bold"
                                            >
                                                $50
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setTenderedInput('100')}
                                                className="h-7 text-[11px] px-2 font-mono font-bold"
                                            >
                                                $100
                                            </Button>
                                        </>
                                    )}
                                </div>
                            </div>

                            <CurrencyInput
                                ref={inputRef}
                                min={0}
                                decimals={2}
                                currencySymbol={activeConfig.currency === 'USD' ? '$' : 'Bs.'}
                                symbolClassName="text-xl font-mono font-black pl-1"
                                value={tenderedInput === '' ? null : Number(tenderedInput)}
                                onValueChange={(val) => setTenderedInput(val > 0 ? val.toString() : '')}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        e.preventDefault();
                                        if (isPendingFullyPaid) {
                                            handleConfirmCheckout();
                                        } else {
                                            handleAddPayment();
                                        }
                                    }
                                }}
                                className="h-13 pl-14 text-2xl font-mono font-black border-2 focus-visible:ring-primary"
                                placeholder="0.00"
                            />
                        </div>

                        {/* 3. Transaction Details (Biopago, Punto, Pago Móvil, Transferencia) */}
                        {activeMethod === 'biopago' && (
                            <div className="p-3.5 rounded-xl border bg-rose-500/5 border-rose-500/20 space-y-2">
                                <div className="flex items-center gap-2 text-rose-700 dark:text-rose-400 font-bold text-xs">
                                    <Fingerprint className="h-4 w-4" />
                                    <span>Cobro Biopago BDV (Biométrico)</span>
                                </div>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div>
                                        <label className="text-[11px] font-semibold text-muted-foreground block mb-1">
                                            Nro. de Aprobación / Referencia:
                                        </label>
                                        <Input
                                            value={referenceInput}
                                            onChange={(e) => setReferenceInput(e.target.value)}
                                            onKeyDown={handleDetailKeyDown}
                                            placeholder="Ej. 082914"
                                            className="h-9 text-xs font-mono font-semibold"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-[11px] font-semibold text-muted-foreground block mb-1">
                                            Cédula / Documento (Opcional):
                                        </label>
                                        <Input
                                            value={cardDigitsInput}
                                            onChange={(e) => setCardDigitsInput(e.target.value)}
                                            onKeyDown={handleDetailKeyDown}
                                            placeholder="Ej. V-18234567"
                                            className="h-9 text-xs font-mono"
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

                        {activeMethod === 'card_debit' && (
                            <div className="p-3.5 rounded-xl border bg-indigo-500/5 border-indigo-500/20 space-y-2">
                                <div className="flex items-center gap-2 text-indigo-700 dark:text-indigo-400 font-bold text-xs">
                                    <CreditCard className="h-4 w-4" />
                                    <span>Punto de Venta / Tarjeta Débito</span>
                                </div>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div>
                                        <label className="text-[11px] font-semibold text-muted-foreground block mb-1">
                                            Nro. Referencia / Aprobación:
                                        </label>
                                        <Input
                                            value={referenceInput}
                                            onChange={(e) => setReferenceInput(e.target.value)}
                                            onKeyDown={handleDetailKeyDown}
                                            placeholder="Ej. 123456"
                                            className="h-9 text-xs font-mono font-semibold"
                                        />
                                    </div>
                                    <div>
                                        <label className="text-[11px] font-semibold text-muted-foreground block mb-1">
                                            Últimos 4 dígitos tarjeta:
                                        </label>
                                        <Input
                                            maxLength={4}
                                            value={cardDigitsInput}
                                            onChange={(e) => setCardDigitsInput(e.target.value)}
                                            onKeyDown={handleDetailKeyDown}
                                            placeholder="Ej. 4321"
                                            className="h-9 text-xs font-mono font-semibold"
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

                        {activeMethod === 'mobile_payment' && (
                            <div className="p-3.5 rounded-xl border bg-amber-500/5 border-amber-500/20 space-y-2">
                                <div className="flex items-center gap-2 text-amber-700 dark:text-amber-400 font-bold text-xs">
                                    <Smartphone className="h-4 w-4" />
                                    <span>Pago Móvil Interbancario</span>
                                </div>
                                <div>
                                    <label className="text-[11px] font-semibold text-muted-foreground block mb-1">
                                        Nro. Referencia / Teléfono Emisor:
                                    </label>
                                    <Input
                                        value={referenceInput}
                                        onChange={(e) => setReferenceInput(e.target.value)}
                                        onKeyDown={handleDetailKeyDown}
                                        placeholder="Ej. 748392 ó 0414-1234567"
                                        className="h-9 text-xs font-mono font-semibold"
                                    />
                                </div>
                            </div>
                        )}

                        {activeMethod === 'transfer' && (
                            <div className="p-3.5 rounded-xl border bg-cyan-500/5 border-cyan-500/20 space-y-2">
                                <div className="flex items-center gap-2 text-cyan-700 dark:text-cyan-400 font-bold text-xs">
                                    <Building className="h-4 w-4" />
                                    <span>Transferencia Bancaria / Zelle</span>
                                </div>
                                <div>
                                    <label className="text-[11px] font-semibold text-muted-foreground block mb-1">
                                        Nro. Comprobante / Referencia:
                                    </label>
                                    <Input
                                        value={referenceInput}
                                        onChange={(e) => setReferenceInput(e.target.value)}
                                        onKeyDown={handleDetailKeyDown}
                                        placeholder="Ej. ZLL-994821"
                                        className="h-9 text-xs font-mono font-semibold"
                                    />
                                </div>
                            </div>
                        )}

                        {/* 4. Action: Add Payment Button */}
                        <div className="pt-1">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={handleAddPayment}
                                className="w-full h-11 text-xs font-bold gap-2 cursor-pointer border border-border shadow-2xs hover:bg-muted"
                            >
                                <Plus className="h-4 w-4" />
                                <span>Agregar este cobro ({activeConfig.label}) a la cuenta</span>
                            </Button>
                            <p className="text-[11px] text-muted-foreground text-center mt-1.5">
                                Puedes agregar varios pagos (efectivo, punto, biopago, etc.) o presionar Enter para confirmar directamente.
                            </p>
                        </div>
                    </div>

                    {/* RIGHT COLUMN: Sale Balance & Split Payments Summary (Col span 5) */}
                    <div className="lg:col-span-5 p-6 bg-muted/20 flex flex-col justify-between overflow-y-auto space-y-4">
                        <div className="space-y-4">
                            {/* Totals Banner */}
                            <div className="rounded-xl border bg-card p-4 space-y-3 shadow-xs">
                                <div>
                                    <span className="text-[10px] uppercase font-bold tracking-wider text-muted-foreground block">
                                        TOTAL A COBRAR
                                    </span>
                                    <div className="flex items-baseline justify-between mt-0.5">
                                        <span className="font-mono text-3xl font-black text-foreground">
                                            $ {total.toFixed(2)}
                                        </span>
                                        <span className="font-mono text-sm font-bold text-muted-foreground">
                                            Bs. {totalVes.toFixed(2)}
                                        </span>
                                    </div>
                                </div>

                                {/* Status: Remaining or Fully Covered */}
                                <div className="border-t pt-2.5">
                                    {remainingUsd > 0.009 ? (
                                        <div className="rounded-lg bg-amber-500/10 border border-amber-500/20 p-2.5 flex items-center justify-between">
                                            <div>
                                                <span className="text-[10px] uppercase font-bold text-amber-700 dark:text-amber-400 block">
                                                    FALTA POR COBRAR:
                                                </span>
                                                <span className="font-mono text-base font-black text-amber-800 dark:text-amber-300">
                                                    $ {remainingUsd.toFixed(2)} USD
                                                </span>
                                            </div>
                                            <div className="text-right">
                                                <span className="font-mono text-xs font-bold text-amber-700 dark:text-amber-400 block">
                                                    Bs. {remainingVes.toFixed(2)}
                                                </span>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-2.5 flex items-center gap-2 text-emerald-700 dark:text-emerald-400">
                                            <CheckCircle2 className="h-4 w-4 shrink-0" />
                                            <span className="text-xs font-black uppercase tracking-wider">
                                                ¡MONTO CUBIERTO AL 100%!
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Applied Payments List (Pagos Múltiples) */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                        PAGOS REGISTRADOS ({payments.length}):
                                    </span>
                                    {payments.length > 0 && (
                                        <span className="font-mono text-xs font-bold text-primary">
                                            Abonado: $ {totalAppliedUsd.toFixed(2)}
                                        </span>
                                    )}
                                </div>

                                {payments.length === 0 ? (
                                    <div className="rounded-xl border border-dashed p-4 text-center text-xs text-muted-foreground bg-muted/30">
                                        <Coins className="h-6 w-6 mx-auto mb-1.5 opacity-40" />
                                        <p className="font-medium">No has registrado formas de pago aún.</p>
                                        <p className="text-[11px] opacity-75 mt-0.5">
                                            Puedes combinar pagos (ej. 20$ en efectivo + resto en Biopago) o cobrar todo con una sola forma.
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-2 max-h-52 overflow-y-auto pr-1">
                                        {payments.map((p, idx) => {
                                            const methodMeta = METHODS.find((m) => {
                                                if (m.key === 'cash_usd') return p.payment_method === 'cash' && p.currency === 'USD';
                                                if (m.key === 'cash_ves') return p.payment_method === 'cash' && p.currency === 'VES';
                                                return m.key === p.payment_method;
                                            }) || METHODS[0];
                                            const Icon = methodMeta.icon;

                                            return (
                                                <div
                                                    key={idx}
                                                    className="flex items-center justify-between p-2.5 rounded-xl border bg-card text-xs shadow-2xs font-mono"
                                                >
                                                    <div className="flex items-center gap-2.5 truncate mr-2">
                                                        <div className="p-1.5 rounded-lg bg-muted text-foreground">
                                                            <Icon className="h-4 w-4" />
                                                        </div>
                                                        <div className="truncate font-sans">
                                                            <div className="flex items-center gap-1.5">
                                                                <span className="font-bold text-foreground">
                                                                    {methodMeta.label}
                                                                </span>
                                                                {p.reference && (
                                                                    <span className="text-[10px] px-1.5 py-0.2 rounded bg-muted font-mono font-medium text-muted-foreground">
                                                                        Ref: {p.reference}
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <div className="font-mono text-xs font-bold text-foreground mt-0.5">
                                                                {p.currency === 'USD' ? '$' : 'Bs.'} {p.amount_tendered.toFixed(2)}
                                                                {p.currency !== 'USD' && (
                                                                    <span className="font-sans text-[10px] text-muted-foreground font-normal ml-1">
                                                                        (Eq. ${p.amount_in_document_currency.toFixed(2)})
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        onClick={() => handleRemovePayment(idx)}
                                                        className="p-1 rounded-lg hover:bg-destructive/10 text-muted-foreground hover:text-destructive transition-colors shrink-0 cursor-pointer"
                                                        title="Eliminar forma de pago"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Vuelto / Cambio Banner */}
                        <div className={`rounded-xl border p-3 flex items-center justify-between transition-colors ${
                            changeUsd > 0 || changeVes > 0
                                ? 'bg-emerald-500/10 border-emerald-500/25'
                                : 'bg-muted/40 border-border text-muted-foreground'
                        }`}>
                            <div>
                                <span className={`text-[10px] font-black uppercase tracking-wider block ${
                                    changeUsd > 0 || changeVes > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-muted-foreground'
                                }`}>
                                    VUELTO / CAMBIO A ENTREGAR:
                                </span>
                                <span className="text-[11px] text-muted-foreground">
                                    Calculado al cambio oficial
                                </span>
                            </div>

                            <div className="text-right font-mono">
                                <div className={`text-lg font-black ${
                                    changeUsd > 0 || changeVes > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-foreground'
                                }`}>
                                    $ {changeUsd.toFixed(2)} USD
                                </div>
                                <div className="text-[11px] font-semibold text-muted-foreground">
                                    ó Bs. {changeVes.toFixed(2)} VES
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Footer Controls */}
                <DialogFooter className="px-6 py-4 border-t bg-card/60 flex sm:justify-between items-center gap-3 shrink-0">
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={onClose}
                        disabled={isSubmitting}
                        className="h-11 cursor-pointer font-bold"
                    >
                        [Esc] Volver al Mostrador
                    </Button>

                    <Button
                        type="button"
                        disabled={!isPendingFullyPaid || isSubmitting}
                        onClick={handleConfirmCheckout}
                        className="h-12 px-8 font-black text-sm bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-md cursor-pointer flex items-center gap-2.5 transition-all"
                    >
                        <CheckCircle2 className="h-5 w-5" />
                        <span>{isSubmitting ? 'Procesando Venta...' : '[Enter] Confirmar Venta e Imprimir'}</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
