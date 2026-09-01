import { useForm, usePage } from '@inertiajs/react';
import { PackageCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import dispatches from '@/routes/dispatches';
import {
    DELIVERY_STATUS_LABELS,
    type DeliveryStatus,
    type Dispatch,
} from '../types/Dispatch';

interface Props {
    dispatch: Dispatch;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

interface DeliveryLineRow {
    id: string;
    delivered_quantity: number;
}

interface DeliveryFormData {
    delivery_status: DeliveryStatus;
    delivery_date: string;
    received_by_name: string;
    received_by_document: string;
    rejection_reason: string;
    lines: DeliveryLineRow[];
}

/**
 * Cuando el cliente no se queda con nada, quién decidió el regreso cambia el
 * nombre: `rejected` lo rechazó él, `returned` volvió por nuestra cuenta.
 */
const REFUSED_OPTIONS: OptionType[] = (
    ['rejected', 'returned'] as DeliveryStatus[]
).map((value) => ({ value, label: DELIVERY_STATUS_LABELS[value] }));

/**
 * El final del viaje. Solo aparece sobre un despacho confirmado cuya entrega
 * todavía no se registró: en borrador la mercancía no ha salido y, registrada,
 * el reingreso ya está escrito en el kardex.
 *
 * El resultado no se elige a mano cuando las cantidades ya lo dicen: entregar
 * todo es «entregado» y entregar parte es «entregado a medias». Solo cuando el
 * cliente no se queda con nada hay algo que decidir.
 */
export function DispatchDeliveryCard({ dispatch }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const lines = (dispatch.lines ?? []).filter(
        (line) => line.status === 'active',
    );

    const { data, setData, put, transform, processing, errors } =
        useForm<DeliveryFormData>({
            delivery_status: 'delivered',
            delivery_date: new Date().toISOString().slice(0, 10),
            received_by_name: '',
            received_by_document: '',
            rejection_reason: '',
            lines: lines.map((line) => ({
                id: line.id,
                delivered_quantity: Number(line.quantity),
            })),
        });

    const shipped = lines.reduce((sum, line) => sum + Number(line.quantity), 0);
    const delivered = data.lines.reduce(
        (sum, line) => sum + line.delivered_quantity,
        0,
    );

    /** El resultado sale de las cantidades; el rechazo es el único que se elige. */
    const refused = delivered <= 0;
    const derived: DeliveryStatus = refused
        ? data.delivery_status === 'returned'
            ? 'returned'
            : 'rejected'
        : delivered >= shipped
          ? 'delivered'
          : 'partial_delivered';

    const setDelivered = (index: number, value: number) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? { ...line, delivered_quantity: value } : line,
            ),
        );

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `lines.${index}.${field}`
        ];

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        /**
         * El resultado viaja en el `transform` y no en el estado: se deriva de
         * las cantidades en el mismo render en que se envía, y `setData` no
         * habría llegado a tiempo.
         */
        transform((payload) => ({ ...payload, delivery_status: derived }));

        put(dispatches.delivery({ company: companyId, id: dispatch.id }).url, {
            preserveScroll: true,
        });
    };

    return (
        <Card className="gap-4 rounded-2xl p-5">
            <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                <PackageCheck className="size-[15px]" />
                Registrar la entrega
            </div>

            <p className="text-[13px] leading-relaxed text-muted-foreground">
                Anota cuánto recibió el cliente. Lo que no se quede reingresa a
                la bodega al costo con el que salió, y el pedido recupera ese
                cupo.
            </p>

            {errors.delivery_status && (
                <p className="text-sm text-bad">{errors.delivery_status}</p>
            )}

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
                                    {line.quantity}
                                </b>
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Recibió
                                </Label>
                                <NumberInput
                                    value={
                                        data.lines[index]?.delivered_quantity ??
                                        0
                                    }
                                    onValueChange={(value) =>
                                        setDelivered(index, value)
                                    }
                                    min={0}
                                    max={Number(line.quantity)}
                                    decimals={4}
                                    className={`h-[42px] rounded-[10px] ${
                                        fieldError(index, 'delivered_quantity')
                                            ? 'border-bad'
                                            : ''
                                    }`}
                                />
                                {fieldError(index, 'delivered_quantity') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(
                                            index,
                                            'delivered_quantity',
                                        )}
                                    </p>
                                )}
                            </div>
                        </div>
                    ))}
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="delivery_date"
                            className="text-[13px] font-semibold"
                        >
                            Fecha de entrega
                        </Label>
                        <Input
                            id="delivery_date"
                            type="date"
                            value={data.delivery_date}
                            onChange={(e) =>
                                setData('delivery_date', e.target.value)
                            }
                            className="h-[42px] rounded-[10px]"
                        />
                        {errors.delivery_date && (
                            <p className="text-sm text-bad">
                                {errors.delivery_date}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Resultado
                        </Label>
                        {refused ? (
                            <Select2
                                inputId="delivery_status"
                                options={REFUSED_OPTIONS}
                                value={
                                    REFUSED_OPTIONS.find(
                                        (option) => option.value === derived,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'delivery_status',
                                        (option?.value ??
                                            'rejected') as DeliveryStatus,
                                    )
                                }
                                size="md"
                                placeholder="Quién decidió el regreso"
                            />
                        ) : (
                            <div className="flex h-[42px] items-center rounded-[10px] border px-3 text-[13.5px] font-semibold">
                                {DELIVERY_STATUS_LABELS[derived]}
                            </div>
                        )}
                        <span className="text-[12px] text-muted-foreground">
                            Sale de lo que recibió el cliente
                        </span>
                    </div>

                    {!refused && (
                        <>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="received_by_name"
                                    className="text-[13px] font-semibold"
                                >
                                    Recibido por
                                </Label>
                                <Input
                                    id="received_by_name"
                                    value={data.received_by_name}
                                    onChange={(e) =>
                                        setData(
                                            'received_by_name',
                                            e.target.value,
                                        )
                                    }
                                    maxLength={150}
                                    className="h-[42px] rounded-[10px]"
                                />
                                {errors.received_by_name && (
                                    <p className="text-sm text-bad">
                                        {errors.received_by_name}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="received_by_document"
                                    className="text-[13px] font-semibold"
                                >
                                    Cédula de quien recibe
                                </Label>
                                <Input
                                    id="received_by_document"
                                    value={data.received_by_document}
                                    onChange={(e) =>
                                        setData(
                                            'received_by_document',
                                            e.target.value,
                                        )
                                    }
                                    maxLength={30}
                                    className="h-[42px] rounded-[10px]"
                                />
                                {errors.received_by_document && (
                                    <p className="text-sm text-bad">
                                        {errors.received_by_document}
                                    </p>
                                )}
                            </div>
                        </>
                    )}

                    {derived === 'rejected' && (
                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label
                                htmlFor="rejection_reason"
                                className="text-[13px] font-semibold"
                            >
                                Motivo del rechazo *
                            </Label>
                            <Textarea
                                id="rejection_reason"
                                value={data.rejection_reason}
                                onChange={(e) =>
                                    setData('rejection_reason', e.target.value)
                                }
                                rows={2}
                                maxLength={500}
                                className="rounded-[10px]"
                            />
                            {errors.rejection_reason && (
                                <p className="text-sm text-bad">
                                    {errors.rejection_reason}
                                </p>
                            )}
                        </div>
                    )}
                </div>

                <Button
                    type="submit"
                    disabled={processing}
                    className="h-10 w-max rounded-[11px] px-4 font-semibold"
                >
                    <PackageCheck />
                    {processing ? 'Guardando…' : 'Registrar la entrega'}
                </Button>
            </form>
        </Card>
    );
}
