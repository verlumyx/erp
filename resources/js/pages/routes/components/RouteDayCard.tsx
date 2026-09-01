import { router, useForm, usePage } from '@inertiajs/react';
import { CalendarRange, Wand2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import routeRoutes from '@/routes/routes';
import type { Route } from '../types/Route';

interface Props {
    route: Route;
    stopDate: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/**
 * El día que se está mirando y el botón que lo planifica.
 *
 * Planificar es idempotente: vuelve a armar el recorrido con la plantilla y los
 * despachos pendientes, respetando las paradas que el conductor ya cerró. Por
 * eso se puede pulsar tantas veces como haga falta durante el día.
 */
export function RouteDayCard({ route, stopDate }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, processing, errors } = useForm({
        stop_date: stopDate,
    });

    const showUrl = routeRoutes.show({ company: companyId, id: route.id }).url;

    const plan = (e: React.FormEvent) => {
        e.preventDefault();

        post(routeRoutes.plan({ company: companyId, id: route.id }).url, {
            preserveScroll: true,
        });
    };

    /** Cambiar de día solo cambia lo que se mira: no planifica nada. */
    const look = (date: string) => {
        setData('stop_date', date);
        router.get(
            showUrl,
            { stop_date: date },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <Card className="flex-row flex-wrap items-end justify-between gap-4 rounded-2xl p-5">
            <div className="flex flex-wrap items-end gap-4">
                <div className="flex flex-col gap-1.5">
                    <Label
                        htmlFor="stop_date"
                        className="inline-flex items-center gap-2 text-[13px] font-bold text-muted-foreground"
                    >
                        <CalendarRange className="size-[15px]" />
                        Día del recorrido
                    </Label>
                    <Input
                        id="stop_date"
                        type="date"
                        value={data.stop_date}
                        onChange={(e) => look(e.target.value)}
                        className={`h-[42px] w-[190px] rounded-[10px] ${errors.stop_date ? 'border-bad' : ''}`}
                    />
                </div>
                <p className="max-w-[420px] pb-2.5 text-[12.5px] leading-relaxed text-muted-foreground">
                    Las paradas del día salen de los clientes fijos de la ruta
                    más los despachos que ya tenga asignados. Lo que el
                    conductor ya cerró no se toca al volver a planificar.
                </p>
            </div>

            <form onSubmit={plan} className="pb-0.5">
                <Button
                    type="submit"
                    disabled={processing || route.status !== 'active'}
                    className="h-10 rounded-[11px] px-4 font-semibold"
                >
                    <Wand2 />
                    {processing ? 'Planificando…' : 'Planificar el día'}
                </Button>
            </form>

            {errors.stop_date && (
                <p className="w-full text-sm text-bad">{errors.stop_date}</p>
            )}
        </Card>
    );
}
