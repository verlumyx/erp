import { router } from '@inertiajs/react';
import { InitialsAvatar } from '@/components/initials-avatar';
import { Card } from '@/components/ui/card';
import { clp, DEMO_CLIENTES, METRICAS as M } from '@/lib/crm-demo';
import clients from '@/routes/clients';

interface Props {
    companyId: string;
}

/** Cuentas por cobrar: clientes con deuda, ordenados de mayor a menor. */
export function DashboardDeuda({ companyId }: Props) {
    const morosos = DEMO_CLIENTES.filter((c) => c.deuda > 0).sort(
        (a, b) => b.deuda - a.deuda,
    );

    return (
        <Card className="gap-0 rounded-2xl py-0">
            <div className="p-5 pb-0">
                <div className="text-base font-bold tracking-tight">
                    Cuentas por cobrar
                </div>
                <div className="mt-0.5 text-[13px] text-muted-foreground">
                    {clp(M.deudaTotal)} en {morosos.length} clientes
                </div>
            </div>
            <div className="flex flex-col gap-0.5 p-3 pt-2.5 pb-5">
                {morosos.map((c) => (
                    <button
                        key={c.id}
                        type="button"
                        className="flex items-center gap-3 rounded-xl p-2.5 text-left transition-colors hover:bg-muted"
                        onClick={() =>
                            router.visit(clients.index(companyId).url)
                        }
                    >
                        <InitialsAvatar name={c.nombre} size={34} />
                        <div className="flex flex-1 flex-col">
                            <span className="text-sm font-bold">
                                {c.nombre}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                {c.ciudad}
                            </span>
                        </div>
                        <span className="text-[15px] font-extrabold text-bad tabular-nums">
                            {clp(c.deuda)}
                        </span>
                    </button>
                ))}
            </div>
        </Card>
    );
}
