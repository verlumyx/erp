import { useForm } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { generateUUID } from '@/lib/utils';
import accounts from '@/routes/accounts';
import type { Account } from '../types/Account';

interface AccountRenewDialogProps {
    companyId: string;
    /** Cuenta a renovar; el diálogo se abre cuando no es null. */
    account: Account | null;
    onClose: () => void;
}

interface RenewFormData {
    id: string;
    amount: number;
    next_renewal: string;
    notes: string;
}

/** Suma `days` a una fecha 'YYYY-MM-DD' y devuelve el mismo formato. */
function addDays(date: string, days: number): string {
    const d = new Date(`${date}T00:00:00`);
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}

/**
 * Modal para registrar una renovación de cuenta. Reutilizable desde el listado
 * y desde el detalle: recibe la cuenta a renovar y postea al endpoint dedicado
 * (POST /accounts/{id}/renew), que crea el movimiento y avanza el vencimiento.
 */
export function AccountRenewDialog({
    companyId,
    account,
    onClose,
}: AccountRenewDialogProps) {
    const form = useForm<RenewFormData>({
        id: generateUUID(),
        amount: 0,
        next_renewal: '',
        notes: '',
    });

    const { data, setData, post, processing, errors, reset, clearErrors } = form;

    useEffect(() => {
        if (account === null) {
            return;
        }

        clearErrors();
        setData({
            id: generateUUID(),
            amount: 0,
            next_renewal: addDays(account.next_renewal, 30),
            notes: '',
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [account]);

    if (account === null) {
        return null;
    }

    const minDate = addDays(account.next_renewal, 1);

    const close = () => {
        reset();
        clearErrors();
        onClose();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(accounts.renew({ company: companyId, id: account.id }).url, {
            preserveScroll: true,
            onSuccess: () => close(),
        });
    };

    return (
        <Dialog
            open={account !== null}
            onOpenChange={(value) => (value ? undefined : close())}
        >
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle className="inline-flex items-center gap-2">
                            <RefreshCw className="size-4 text-muted-foreground" />
                            Registrar renovación
                        </DialogTitle>
                        <DialogDescription>
                            {account.code} · vencimiento actual:{' '}
                            {account.next_renewal}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex flex-col gap-4 py-4">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="renew_amount">Monto *</Label>
                            <CurrencyInput
                                id="renew_amount"
                                min={0}
                                decimals={2}
                                value={data.amount}
                                onValueChange={(value) =>
                                    setData('amount', value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.amount ? 'border-bad' : ''}`}
                                required
                            />
                            {errors.amount && (
                                <p className="text-sm text-bad">
                                    {errors.amount}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="renew_next_renewal">
                                Nueva fecha de vencimiento *
                            </Label>
                            <Input
                                id="renew_next_renewal"
                                type="date"
                                min={minDate}
                                value={data.next_renewal}
                                onChange={(e) =>
                                    setData('next_renewal', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.next_renewal ? 'border-bad' : ''}`}
                                required
                            />
                            {errors.next_renewal && (
                                <p className="text-sm text-bad">
                                    {errors.next_renewal}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="renew_notes">Notas</Label>
                            <Textarea
                                id="renew_notes"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                                rows={3}
                                className="rounded-[10px]"
                            />
                            {errors.notes && (
                                <p className="text-sm text-bad">
                                    {errors.notes}
                                </p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            className="rounded-[10px] bg-card font-semibold"
                            onClick={close}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            className="rounded-[10px] font-semibold"
                            disabled={processing}
                        >
                            Registrar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
