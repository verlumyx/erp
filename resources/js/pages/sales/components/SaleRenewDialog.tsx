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
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Textarea } from '@/components/ui/textarea';
import { generateUUID } from '@/lib/utils';
import sales from '@/routes/sales';
import type { Sale } from '../types/Sale';

interface SaleRenewDialogProps {
    companyId: string;
    /** Venta a renovar; el diálogo se abre cuando no es null. */
    sale: Sale | null;
    onClose: () => void;
}

interface RenewFormData {
    id: string;
    duration_days: number;
    price: number;
    notes: string;
}

/**
 * Modal para renovar una venta activa o en periodo de gracia. Postea al endpoint
 * dedicado (POST /sales/{id}/renew), que registra la renovación, avanza el
 * vencimiento y crea la transacción de ingreso. Duración y precio por defecto
 * provienen del plan vigente asociado a la venta (con fallback al snapshot de la
 * venta si el plan ya no está disponible).
 */
export function SaleRenewDialog({
    companyId,
    sale,
    onClose,
}: SaleRenewDialogProps) {
    const form = useForm<RenewFormData>({
        id: generateUUID(),
        duration_days: 0,
        price: 0,
        notes: '',
    });

    const { data, setData, post, processing, errors, reset, clearErrors } =
        form;

    useEffect(() => {
        if (sale === null) {
            return;
        }
        clearErrors();
        setData({
            id: generateUUID(),
            duration_days: sale.plan?.duration_days ?? sale.duration_days,
            price: Number(sale.plan?.sale_price ?? sale.price),
            notes: '',
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
        post(sales.renew({ company: companyId, id: sale.id }).url, {
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
                            <RefreshCw className="size-4 text-muted-foreground" />
                            Renovar venta
                        </DialogTitle>
                        <DialogDescription>
                            {sale.code} · vencimiento actual: {sale.end_date}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex flex-col gap-4 py-4">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="renew_duration">
                                Duración (días)
                            </Label>
                            <NumberInput
                                id="renew_duration"
                                min={1}
                                decimals={0}
                                value={data.duration_days}
                                onValueChange={(value) =>
                                    setData('duration_days', value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.duration_days ? 'border-bad' : ''}`}
                            />
                            {errors.duration_days && (
                                <p className="text-sm text-bad">
                                    {errors.duration_days}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="renew_price">Precio</Label>
                            <CurrencyInput
                                id="renew_price"
                                min={0}
                                decimals={2}
                                value={data.price}
                                onValueChange={(value) =>
                                    setData('price', value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.price ? 'border-bad' : ''}`}
                            />
                            {errors.price && (
                                <p className="text-sm text-bad">
                                    {errors.price}
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
                            Renovar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
