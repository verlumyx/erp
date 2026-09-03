import { useForm, usePage } from '@inertiajs/react';
import { Ban } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
import storeOrders from '@/routes/store-orders';
import type { StoreOrder } from '../types/Store';

interface StoreOrderRejectDialogProps {
    order: StoreOrder;
    open: boolean;
    onClose: () => void;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** Rechazar exige motivo: el comprador lo ve en el estado de su pedido. */
export function StoreOrderRejectDialog({
    order,
    open,
    onClose,
}: StoreOrderRejectDialogProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, put, processing, errors, reset } = useForm({
        rejection_reason: '',
    });

    const submit = () => {
        put(storeOrders.reject({ company: companyId, id: order.id }).url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={(value) => !value && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Ban className="size-4 text-bad" />
                        Rechazar {order.code}
                    </DialogTitle>
                    <DialogDescription>
                        El pedido no se convierte en orden de venta. Si el
                        comprador se equivocó, puede hacer otro.
                    </DialogDescription>
                </DialogHeader>
                <div className="flex flex-col gap-1.5">
                    <Label
                        htmlFor="rejection_reason"
                        className="text-[13px] font-semibold"
                    >
                        Motivo *
                    </Label>
                    <Textarea
                        id="rejection_reason"
                        value={data.rejection_reason}
                        rows={3}
                        maxLength={500}
                        onChange={(e) =>
                            setData('rejection_reason', e.target.value)
                        }
                        className={`rounded-[10px] ${errors.rejection_reason ? 'border-bad' : ''}`}
                    />
                    {errors.rejection_reason && (
                        <p className="text-sm text-bad">
                            {errors.rejection_reason}
                        </p>
                    )}
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" onClick={onClose}>
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        onClick={submit}
                        disabled={
                            processing || data.rejection_reason.trim() === ''
                        }
                    >
                        {processing ? 'Rechazando…' : 'Rechazar pedido'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
