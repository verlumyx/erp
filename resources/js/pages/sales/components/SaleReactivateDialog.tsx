import { router } from '@inertiajs/react';
import { AlertTriangle, RotateCcw } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
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
import type { AvailableProfile, Sale } from '../types/Sale';

interface SaleReactivateDialogProps {
    companyId: string;
    /** Venta a reactivar; el diálogo se abre cuando no es null. */
    sale: Sale | null;
    /** Profiles disponibles de la compañía (se filtran por el servicio de la venta). */
    availableProfiles: AvailableProfile[];
    onClose: () => void;
}

interface UnavailableProfile {
    id: string;
    label: string;
}

/**
 * Modal para reactivar una venta cancelada o expirada fuera de gracia. Primero
 * intenta reusar los profiles originales; si el backend responde 409 (ya ocupados),
 * muestra los profiles disponibles del servicio para que el agente elija reemplazos.
 */
export function SaleReactivateDialog({
    companyId,
    sale,
    availableProfiles,
    onClose,
}: SaleReactivateDialogProps) {
    const [durationDays, setDurationDays] = useState(0);
    const [price, setPrice] = useState(0);
    const [notes, setNotes] = useState('');
    const [selected, setSelected] = useState<string[]>([]);
    const [unavailable, setUnavailable] = useState<UnavailableProfile[]>([]);
    const [needsReplacement, setNeedsReplacement] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    useEffect(() => {
        if (sale === null) {
            return;
        }
        setDurationDays(sale.duration_days);
        setPrice(Number(sale.price));
        setNotes('');
        setSelected([]);
        setUnavailable([]);
        setNeedsReplacement(false);
        setErrorMessage(null);
    }, [sale]);

    const requiredCount = useMemo(() => {
        if (sale === null) {
            return 1;
        }
        return sale.capacity === 'full_account'
            ? (sale.sale_profiles?.length ?? 1)
            : 1;
    }, [sale]);

    const serviceProfiles = useMemo(() => {
        if (sale === null) {
            return [];
        }
        return availableProfiles.filter(
            (p) => p.service_id === sale.service_id,
        );
    }, [availableProfiles, sale]);

    if (sale === null) {
        return null;
    }

    const close = () => {
        onClose();
    };

    const toggleProfile = (id: string) => {
        setSelected((prev) =>
            prev.includes(id) ? prev.filter((p) => p !== id) : [...prev, id],
        );
    };

    const submit = async () => {
        setProcessing(true);
        setErrorMessage(null);

        const payload: Record<string, unknown> = {
            id: generateUUID(),
            duration_days: durationDays,
            price,
            notes,
        };

        if (needsReplacement || selected.length > 0) {
            payload.profile_ids = selected;
        }

        const xsrf = decodeURIComponent(
            document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
        );

        try {
            const response = await fetch(
                sales.reactivate({ company: companyId, id: sale.id }).url,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': xsrf,
                    },
                    body: JSON.stringify(payload),
                },
            );

            if (response.ok || response.redirected) {
                close();
                router.visit(
                    sales.show({ company: companyId, id: sale.id }).url,
                );
                return;
            }

            const data = await response.json().catch(() => ({}));

            if (response.status === 409) {
                setUnavailable(data.unavailable_profiles ?? []);
                setNeedsReplacement(true);
                setErrorMessage(data.message ?? null);
            } else if (response.status === 422) {
                const first = Object.values(data.errors ?? {})[0] as
                    | string[]
                    | undefined;
                setErrorMessage(first?.[0] ?? 'Datos inválidos.');
            } else {
                setErrorMessage('No se pudo reactivar la venta.');
            }
        } catch {
            setErrorMessage('No se pudo reactivar la venta.');
        } finally {
            setProcessing(false);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        void submit();
    };

    const canSubmit = needsReplacement
        ? selected.length === requiredCount
        : true;

    return (
        <Dialog
            open={sale !== null}
            onOpenChange={(value) => (value ? undefined : close())}
        >
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle className="inline-flex items-center gap-2">
                            <RotateCcw className="size-4 text-muted-foreground" />
                            Reactivar venta
                        </DialogTitle>
                        <DialogDescription>
                            {sale.code} · {sale.client?.name ?? '—'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex flex-col gap-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="react_duration">
                                    Duración (días)
                                </Label>
                                <NumberInput
                                    id="react_duration"
                                    min={1}
                                    decimals={0}
                                    value={durationDays}
                                    onValueChange={setDurationDays}
                                    className="h-[42px] rounded-[10px]"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="react_price">Precio</Label>
                                <CurrencyInput
                                    id="react_price"
                                    min={0}
                                    decimals={2}
                                    value={price}
                                    onValueChange={setPrice}
                                    className="h-[42px] rounded-[10px]"
                                />
                            </div>
                        </div>

                        {errorMessage && (
                            <div className="flex items-start gap-2 rounded-[10px] border border-bad/40 bg-bad-soft p-3 text-sm text-bad">
                                <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                                <span>{errorMessage}</span>
                            </div>
                        )}

                        {needsReplacement && (
                            <div className="flex flex-col gap-2">
                                {unavailable.length > 0 && (
                                    <p className="text-[13px] text-muted-foreground">
                                        No disponibles:{' '}
                                        {unavailable
                                            .map((u) => u.label)
                                            .join(', ')}
                                    </p>
                                )}
                                <Label>
                                    Elige {requiredCount} profile
                                    {requiredCount !== 1 ? 's' : ''} de
                                    reemplazo
                                </Label>
                                <div className="flex max-h-48 flex-col gap-1 overflow-auto rounded-[10px] border p-2">
                                    {serviceProfiles.length === 0 && (
                                        <p className="p-2 text-sm text-muted-foreground">
                                            No hay profiles disponibles para
                                            este servicio.
                                        </p>
                                    )}
                                    {serviceProfiles.map((p) => (
                                        <label
                                            key={p.id}
                                            className="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-muted"
                                        >
                                            <input
                                                type="checkbox"
                                                className="size-4"
                                                checked={selected.includes(
                                                    p.id,
                                                )}
                                                onChange={() =>
                                                    toggleProfile(p.id)
                                                }
                                            />
                                            <span className="font-medium">
                                                {p.account_email}
                                            </span>
                                            <span className="text-muted-foreground">
                                                · Perfil {p.number}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        )}

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="react_notes">Notas</Label>
                            <Textarea
                                id="react_notes"
                                value={notes}
                                onChange={(e) => setNotes(e.target.value)}
                                rows={2}
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
                            disabled={processing || !canSubmit}
                        >
                            Reactivar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
