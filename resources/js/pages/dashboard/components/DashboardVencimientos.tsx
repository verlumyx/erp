import { router } from '@inertiajs/react';
import { ServiceBadge } from '@/components/service-badge';
import { StatusPill } from '@/components/status-pill';
import { Card } from '@/components/ui/card';
import { WhatsAppButton } from '@/components/whatsapp-button';
import clients from '@/routes/clients';
import type { DashboardExpiration } from '../types';

interface Props {
    companyId: string;
    expirations: DashboardExpiration[];
}

function label(item: DashboardExpiration): string {
    if (item.status_key === 'vencido') {
        return `Venció hace ${Math.abs(item.days)}d`;
    }
    if (item.days === 0) {
        return 'Vence hoy';
    }
    return `${item.days}d`;
}

/** Próximos vencimientos de ventas con recordatorio por WhatsApp. */
export function DashboardVencimientos({ companyId, expirations }: Props) {
    return (
        <Card className="gap-0 rounded-2xl py-0">
            <div className="flex items-start justify-between gap-3 p-5 pb-0">
                <div>
                    <div className="text-base font-bold tracking-tight">
                        Próximos vencimientos
                    </div>
                    <div className="mt-0.5 text-[13px] text-muted-foreground">
                        Renueva o envía recordatorio
                    </div>
                </div>
                <span className="rounded-full bg-warn-soft px-2.5 py-0.5 text-[13px] font-bold text-warn">
                    {expirations.length}
                </span>
            </div>
            {expirations.length === 0 ? (
                <div className="p-5 pt-2.5 text-[13px] text-muted-foreground">
                    No hay vencimientos próximos.
                </div>
            ) : (
                <div className="flex flex-col p-3 pt-2.5 pb-5">
                    {expirations.map((item) => (
                        <div
                            key={item.id}
                            className="flex items-center gap-3 rounded-xl p-2.5 transition-colors hover:bg-muted"
                        >
                            <ServiceBadge name={item.service_name} size={32} />
                            <div className="flex min-w-0 flex-1 flex-col">
                                <button
                                    type="button"
                                    className="w-fit text-left text-sm font-bold hover:text-primary"
                                    onClick={() =>
                                        router.visit(
                                            clients.index(companyId).url,
                                        )
                                    }
                                >
                                    {item.client_name}
                                </button>
                                <span className="truncate text-xs text-muted-foreground">
                                    {item.service_name} · {item.code}
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <StatusPill kind={item.status_key}>
                                    {label(item)}
                                </StatusPill>
                                {item.client_phone && (
                                    <WhatsAppButton
                                        tel={item.client_phone}
                                        label="Recordatorio por WhatsApp"
                                        size="sm"
                                    />
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </Card>
    );
}
