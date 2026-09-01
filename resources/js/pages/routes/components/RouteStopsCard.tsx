import { useForm, usePage } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { useState } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import routeRoutes from '@/routes/routes';
import {
    isClosed,
    needsSkipReason,
    STOP_PILL_KIND,
    STOP_STATUS_LABELS,
    type Route,
    type RouteStop,
    type StopStatus,
} from '../types/Route';

interface Props {
    route: Route;
    stopDate: string;
    stops: RouteStop[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** `pending` no se registra: es de donde parte la parada. */
const RESULT_OPTIONS: OptionType[] = (
    ['arrived', 'completed', 'skipped', 'failed'] as StopStatus[]
).map((value) => ({ value, label: STOP_STATUS_LABELS[value] }));

function localNow(): string {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60000;

    return new Date(now.getTime() - offset).toISOString().slice(0, 16);
}

/**
 * Las paradas del día y el registro de cada visita.
 *
 * Registrar una visita no mueve inventario: la entrega de la mercancía la
 * anota el despacho. Esto es la bitácora del recorrido, y por eso una parada ya
 * cerrada no se vuelve a escribir.
 */
export function RouteStopsCard({ route, stopDate, stops }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [openStopId, setOpenStopId] = useState<string | null>(null);

    const { data, setData, put, processing, errors, reset } = useForm({
        stop_status: 'completed' as StopStatus,
        actual_arrival: '',
        actual_departure: '',
        skip_reason: '',
        latitude: '',
        longitude: '',
    });

    const openFor = (stop: RouteStop) => {
        reset();
        setData({
            stop_status: 'completed',
            actual_arrival: stop.actual_arrival ?? localNow(),
            actual_departure: '',
            skip_reason: '',
            latitude: '',
            longitude: '',
        });
        setOpenStopId(stop.id);
    };

    const submit = (e: React.FormEvent, stop: RouteStop) => {
        e.preventDefault();

        put(
            routeRoutes.stops.visit({
                company: companyId,
                id: route.id,
                stop: stop.id,
            }).url,
            {
                preserveScroll: true,
                onSuccess: () => setOpenStopId(null),
            },
        );
    };

    return (
        <Card className="gap-0 overflow-hidden rounded-2xl py-0">
            <div className="flex items-center gap-2 border-b p-5 text-[13px] font-bold text-muted-foreground">
                <MapPin className="size-[15px]" />
                Paradas del {stopDate}
            </div>

            <div className="flex flex-col">
                {stops.map((stop) => (
                    <div key={stop.id} className="border-b last:border-b-0">
                        <div className="grid grid-cols-[auto_1fr_auto] items-center gap-3.5 px-5 py-3 lg:grid-cols-[auto_2.4fr_1.6fr_1fr_auto]">
                            <span className="grid size-[30px] shrink-0 place-items-center rounded-[9px] bg-primary-soft text-sm font-extrabold text-primary tabular-nums">
                                {stop.sequence}
                            </span>

                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {stop.client_name ?? '—'}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {stop.client_code}
                                    {stop.client_address_name
                                        ? ` · ${stop.client_address_name}`
                                        : ' · Dirección por defecto'}
                                </span>
                            </div>

                            <div className="hidden min-w-0 flex-col text-[12.5px] text-muted-foreground lg:flex">
                                {stop.actual_arrival && (
                                    <span className="truncate">
                                        Llegó {stop.actual_arrival}
                                    </span>
                                )}
                                {stop.skip_reason && (
                                    <span className="truncate text-bad">
                                        {stop.skip_reason}
                                    </span>
                                )}
                            </div>

                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={STOP_PILL_KIND[stop.stop_status]}
                                >
                                    {STOP_STATUS_LABELS[stop.stop_status]}
                                </StatusPill>
                            </div>

                            <div className="flex justify-end">
                                {!isClosed(stop.stop_status) && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="h-9 rounded-[10px] bg-card px-3 text-[13px] font-semibold"
                                        onClick={() =>
                                            openStopId === stop.id
                                                ? setOpenStopId(null)
                                                : openFor(stop)
                                        }
                                    >
                                        {openStopId === stop.id
                                            ? 'Cerrar'
                                            : 'Registrar visita'}
                                    </Button>
                                )}
                            </div>
                        </div>

                        {openStopId === stop.id && (
                            <form
                                onSubmit={(e) => submit(e, stop)}
                                className="grid grid-cols-1 gap-4 border-t bg-muted/40 p-5 sm:grid-cols-2"
                            >
                                <div className="flex flex-col gap-1.5">
                                    <Label className="text-[13px] font-semibold">
                                        Resultado *
                                    </Label>
                                    <Select2
                                        options={RESULT_OPTIONS}
                                        value={
                                            RESULT_OPTIONS.find(
                                                (option) =>
                                                    option.value ===
                                                    data.stop_status,
                                            ) ?? null
                                        }
                                        onChange={(option) =>
                                            setData(
                                                'stop_status',
                                                (option?.value ??
                                                    'completed') as StopStatus,
                                            )
                                        }
                                        error={!!errors.stop_status}
                                        size="md"
                                        placeholder="Cómo terminó la visita"
                                    />
                                    {errors.stop_status && (
                                        <p className="text-sm text-bad">
                                            {errors.stop_status}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label
                                        htmlFor={`arrival-${stop.id}`}
                                        className="text-[13px] font-semibold"
                                    >
                                        Hora de llegada
                                    </Label>
                                    <Input
                                        id={`arrival-${stop.id}`}
                                        type="datetime-local"
                                        value={data.actual_arrival}
                                        onChange={(e) =>
                                            setData(
                                                'actual_arrival',
                                                e.target.value,
                                            )
                                        }
                                        className={`h-[42px] rounded-[10px] ${errors.actual_arrival ? 'border-bad' : ''}`}
                                    />
                                    {errors.actual_arrival && (
                                        <p className="text-sm text-bad">
                                            {errors.actual_arrival}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label
                                        htmlFor={`departure-${stop.id}`}
                                        className="text-[13px] font-semibold"
                                    >
                                        Hora de salida
                                    </Label>
                                    <Input
                                        id={`departure-${stop.id}`}
                                        type="datetime-local"
                                        value={data.actual_departure}
                                        onChange={(e) =>
                                            setData(
                                                'actual_departure',
                                                e.target.value,
                                            )
                                        }
                                        className={`h-[42px] rounded-[10px] ${errors.actual_departure ? 'border-bad' : ''}`}
                                    />
                                    {errors.actual_departure && (
                                        <p className="text-sm text-bad">
                                            {errors.actual_departure}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label
                                        htmlFor={`reason-${stop.id}`}
                                        className="text-[13px] font-semibold"
                                    >
                                        Motivo
                                        {needsSkipReason(data.stop_status)
                                            ? ' *'
                                            : ''}
                                    </Label>
                                    <Input
                                        id={`reason-${stop.id}`}
                                        value={data.skip_reason}
                                        onChange={(e) =>
                                            setData(
                                                'skip_reason',
                                                e.target.value,
                                            )
                                        }
                                        maxLength={500}
                                        disabled={
                                            !needsSkipReason(data.stop_status)
                                        }
                                        className={`h-[42px] rounded-[10px] ${errors.skip_reason ? 'border-bad' : ''}`}
                                        placeholder="Cerrado, no recibió, dirección errada…"
                                    />
                                    {errors.skip_reason && (
                                        <p className="text-sm text-bad">
                                            {errors.skip_reason}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label
                                        htmlFor={`latitude-${stop.id}`}
                                        className="text-[13px] font-semibold"
                                    >
                                        Latitud
                                    </Label>
                                    <Input
                                        id={`latitude-${stop.id}`}
                                        value={data.latitude}
                                        onChange={(e) =>
                                            setData('latitude', e.target.value)
                                        }
                                        className={`h-[42px] rounded-[10px] ${errors.latitude ? 'border-bad' : ''}`}
                                        placeholder="10.4806"
                                    />
                                    {errors.latitude && (
                                        <p className="text-sm text-bad">
                                            {errors.latitude}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label
                                        htmlFor={`longitude-${stop.id}`}
                                        className="text-[13px] font-semibold"
                                    >
                                        Longitud
                                    </Label>
                                    <Input
                                        id={`longitude-${stop.id}`}
                                        value={data.longitude}
                                        onChange={(e) =>
                                            setData('longitude', e.target.value)
                                        }
                                        className={`h-[42px] rounded-[10px] ${errors.longitude ? 'border-bad' : ''}`}
                                        placeholder="-66.9036"
                                    />
                                    {errors.longitude && (
                                        <p className="text-sm text-bad">
                                            {errors.longitude}
                                        </p>
                                    )}
                                </div>

                                <div className="sm:col-span-2">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="h-10 w-max rounded-[11px] px-4 font-semibold"
                                    >
                                        <MapPin />
                                        {processing
                                            ? 'Guardando…'
                                            : 'Registrar la visita'}
                                    </Button>
                                </div>
                            </form>
                        )}
                    </div>
                ))}

                {stops.length === 0 && (
                    <div className="p-12 text-center text-sm text-muted-foreground">
                        Todavía no hay paradas para ese día. Planifícalo para
                        generarlas.
                    </div>
                )}
            </div>
        </Card>
    );
}
