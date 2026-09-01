import { useForm, usePage } from '@inertiajs/react';
import { PackageCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Textarea } from '@/components/ui/textarea';
import transfers from '@/routes/transfers';
import type { Transfer } from '../types/Transfer';

interface Props {
    transfer: Transfer;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

interface ReceiptLineRow {
    id: string;
    received_quantity: number;
}

interface ReceiptFormData {
    received_date: string;
    notes: string;
    lines: ReceiptLineRow[];
}

/**
 * La llegada de la mercancía al destino. Solo aparece sobre un traslado en dos
 * pasos ya confirmado cuya recepción todavía no se registró: en borrador la
 * mercancía no ha salido y, registrada, la entrada ya está escrita en el kardex.
 *
 * Lo que falte no se pierde: se queda en la bodega de tránsito, y hasta que un
 * ajuste lo justifique el traslado no se cierra.
 */
export function TransferReceiptCard({ transfer }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const lines = (transfer.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    const { data, setData, put, processing, errors } = useForm<ReceiptFormData>(
        {
            received_date: new Date().toISOString().slice(0, 10),
            notes: '',
            lines: lines.map((line) => ({
                id: line.id,
                received_quantity: Number(line.sent_quantity),
            })),
        },
    );

    const sent = lines.reduce(
        (sum, line) => sum + Number(line.sent_quantity),
        0,
    );
    const received = data.lines.reduce(
        (sum, line) => sum + line.received_quantity,
        0,
    );
    const difference = Math.round((sent - received) * 10000) / 10000;

    const setReceived = (index: number, value: number) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? { ...line, received_quantity: value } : line,
            ),
        );

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `lines.${index}.${field}`
        ];

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        put(transfers.receipt({ company: companyId, id: transfer.id }).url, {
            preserveScroll: true,
        });
    };

    return (
        <Card className="gap-4 rounded-2xl p-5">
            <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                <PackageCheck className="size-[15px]" />
                Registrar la recepción
            </div>

            <p className="text-[13px] leading-relaxed text-muted-foreground">
                Anota cuánto llegó a la bodega de destino. Entra con el costo
                con el que salió del origen, y lo que falte se queda en tránsito
                hasta que un ajuste lo justifique.
            </p>

            {errors.lines && <p className="text-sm text-bad">{errors.lines}</p>}

            <form onSubmit={submit} className="flex flex-col gap-4">
                <div className="flex flex-col gap-3">
                    {lines.map((line, index) => (
                        <div
                            key={line.id}
                            className="grid grid-cols-1 items-end gap-3 rounded-[12px] border p-4 sm:grid-cols-[2fr_1fr_1fr]"
                        >
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {line.item_name ?? '—'}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {line.item_code}
                                    {line.measurement_unit_name
                                        ? ` · ${line.measurement_unit_name}`
                                        : ''}
                                </span>
                            </div>
                            <div className="text-[13.5px] text-muted-foreground">
                                Salieron{' '}
                                <b className="font-bold text-foreground tabular-nums">
                                    {line.sent_quantity}
                                </b>
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Llegó
                                </Label>
                                <NumberInput
                                    value={
                                        data.lines[index]?.received_quantity ??
                                        0
                                    }
                                    onValueChange={(value) =>
                                        setReceived(index, value)
                                    }
                                    min={0}
                                    max={Number(line.sent_quantity)}
                                    decimals={4}
                                    className={`h-[42px] rounded-[10px] ${
                                        fieldError(index, 'received_quantity')
                                            ? 'border-bad'
                                            : ''
                                    }`}
                                />
                                {fieldError(index, 'received_quantity') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(index, 'received_quantity')}
                                    </p>
                                )}
                            </div>
                        </div>
                    ))}
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="received_date"
                            className="text-[13px] font-semibold"
                        >
                            Fecha de llegada
                        </Label>
                        <Input
                            id="received_date"
                            type="date"
                            value={data.received_date}
                            onChange={(e) =>
                                setData('received_date', e.target.value)
                            }
                            className="h-[42px] rounded-[10px]"
                        />
                        {errors.received_date && (
                            <p className="text-sm text-bad">
                                {errors.received_date}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Faltante
                        </Label>
                        <div
                            className={`flex h-[42px] items-center rounded-[10px] border px-3 text-[13.5px] font-semibold tabular-nums ${
                                difference > 0 ? 'text-bad' : ''
                            }`}
                        >
                            {difference}
                        </div>
                        <span className="text-[12px] text-muted-foreground">
                            Sale de la resta: no se captura
                        </span>
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                        <Label
                            htmlFor="receipt_notes"
                            className="text-[13px] font-semibold"
                        >
                            Notas de la recepción
                        </Label>
                        <Textarea
                            id="receipt_notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            rows={2}
                            className="rounded-[10px]"
                            placeholder={
                                difference > 0
                                    ? 'Explica qué pasó con lo que no llegó'
                                    : ''
                            }
                        />
                        {errors.notes && (
                            <p className="text-sm text-bad">{errors.notes}</p>
                        )}
                    </div>
                </div>

                <Button
                    type="submit"
                    disabled={processing}
                    className="h-10 w-max rounded-[11px] px-4 font-semibold"
                >
                    <PackageCheck />
                    {processing ? 'Guardando…' : 'Registrar la recepción'}
                </Button>
            </form>
        </Card>
    );
}
