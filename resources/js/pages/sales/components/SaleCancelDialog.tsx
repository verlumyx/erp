import { useForm } from '@inertiajs/react';
import { Ban } from 'lucide-react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { CurrencyInput } from '@/components/ui/currency-input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import sales from '@/routes/sales';
import type { Sale } from '../types/Sale';

interface SaleCancelDialogProps {
    companyId: string;
    /** Venta a expulsar; el diálogo se abre cuando no es null. */
    sale: Sale | null;
    onClose: () => void;
}

interface CancelFormData {
    cancellation_reason: string;
    create_refund: boolean;
    refund_amount: number;
    refund_reason: string;
}

/**
 * Modal para expulsar (cancelar) una venta. Postea a POST /sales/{id}/cancel,
 * que marca la venta como cancelada y libera sus profiles. Opcionalmente, al
 * marcar "Crear reembolso" se genera un reembolso pendiente de aprobación
 * ligado a la venta; el egreso contable se registra solo al aprobarlo.
 */
export function SaleCancelDialog({
    companyId,
    sale,
    onClose,
}: SaleCancelDialogProps) {
    const form = useForm<CancelFormData>({
        cancellation_reason: '',
        create_refund: false,
        refund_amount: 0,
        refund_reason: '',
    });
    const { data, setData, post, processing, errors, reset, clearErrors } =
        form;

    useEffect(() => {
        if (sale === null) {
            return;
        }
        clearErrors();
        setData({
            cancellation_reason: '',
            create_refund: false,
            refund_amount: Number(sale.price),
            refund_reason: '',
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sale]);

    if (sale === null) {
        return null;
    }

    const close = () => {
        reset();
        clearErrors();
        onClose();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(sales.cancel({ company: companyId, id: sale.id }).url, {
            preserveScroll: true,
            onSuccess: () => close(),
        });
    };

    return (
        <Dialog
            open={sale !== null}
            onOpenChange={(value) => (value ? undefined : close())}
        >
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle className="inline-flex items-center gap-2">
                            <Ban className="size-4 text-muted-foreground" />
                            Expulsar venta
                        </DialogTitle>
                        <DialogDescription>
                            {sale.code} · {sale.client?.name ?? '—'}. Los
                            profiles quedarán disponibles.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex flex-col gap-4 py-4">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="cancel_reason">Motivo *</Label>
                            <Textarea
                                id="cancel_reason"
                                value={data.cancellation_reason}
                                onChange={(e) =>
                                    setData(
                                        'cancellation_reason',
                                        e.target.value,
                                    )
                                }
                                rows={3}
                                maxLength={255}
                                className={`rounded-[10px] ${errors.cancellation_reason ? 'border-bad' : ''}`}
                                required
                            />
                            {errors.cancellation_reason && (
                                <p className="text-sm text-bad">
                                    {errors.cancellation_reason}
                                </p>
                            )}
                        </div>

                        <label className="flex cursor-pointer items-center gap-2.5 rounded-[10px] border bg-card p-3">
                            <Checkbox
                                checked={data.create_refund}
                                onCheckedChange={(checked) =>
                                    setData('create_refund', checked === true)
                                }
                            />
                            <span className="text-sm font-semibold">
                                Crear reembolso
                            </span>
                        </label>

                        {data.create_refund && (
                            <div className="flex flex-col gap-4 rounded-[10px] border border-dashed p-3">
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="refund_amount">
                                        Monto a reembolsar *
                                    </Label>
                                    <CurrencyInput
                                        id="refund_amount"
                                        min={0}
                                        decimals={2}
                                        value={data.refund_amount}
                                        onValueChange={(value) =>
                                            setData('refund_amount', value)
                                        }
                                        className={`h-[42px] rounded-[10px] ${errors.refund_amount ? 'border-bad' : ''}`}
                                    />
                                    {errors.refund_amount && (
                                        <p className="text-sm text-bad">
                                            {errors.refund_amount}
                                        </p>
                                    )}
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="refund_reason">
                                        Razón del reembolso
                                    </Label>
                                    <Textarea
                                        id="refund_reason"
                                        value={data.refund_reason}
                                        onChange={(e) =>
                                            setData(
                                                'refund_reason',
                                                e.target.value,
                                            )
                                        }
                                        rows={2}
                                        maxLength={255}
                                        className="rounded-[10px]"
                                        placeholder="Por defecto se usa el motivo de la expulsión"
                                    />
                                </div>
                                <p className="text-[12.5px] text-muted-foreground">
                                    El reembolso quedará pendiente de
                                    aprobación. El egreso se registra al
                                    aprobarlo.
                                </p>
                            </div>
                        )}
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
                            variant="destructive"
                            className="rounded-[10px] font-semibold"
                            disabled={processing}
                        >
                            Expulsar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
