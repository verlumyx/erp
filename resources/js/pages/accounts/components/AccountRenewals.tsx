import { usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Plus, RefreshCw } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { clp } from '@/lib/crm-demo';
import {
    RENEWAL_TYPE_LABELS,
    type Account,
    type AccountRenewal,
} from '../types/Account';
import { AccountRenewDialog } from './AccountRenewDialog';

interface AccountRenewalsProps {
    companyId: string;
    /** Cuenta cuyo historial se muestra; se pasa al diálogo de renovación. */
    account: Account;
    renewals: AccountRenewal[];
}

interface PageProps {
    auth?: { permissions?: string[] };
    [key: string]: unknown;
}

const PER_PAGE = 10;

export function AccountRenewals({
    companyId,
    account,
    renewals,
}: AccountRenewalsProps) {
    const { auth } = usePage<PageProps>().props;
    const canRenew = auth?.permissions?.includes('accounts.renew') ?? false;
    const [renewing, setRenewing] = useState(false);
    const [page, setPage] = useState(1);

    const total = renewals.length;
    const pageCount = Math.max(1, Math.ceil(total / PER_PAGE));
    const safePage = Math.min(page, pageCount);
    const from = total === 0 ? 0 : (safePage - 1) * PER_PAGE + 1;
    const to = Math.min(safePage * PER_PAGE, total);

    const visible = useMemo(
        () => renewals.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE),
        [renewals, safePage],
    );

    return (
        <Card className="gap-0 overflow-hidden rounded-2xl py-0">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b p-5">
                <div className="inline-flex items-center gap-2 text-base font-bold tracking-tight">
                    <RefreshCw className="size-4 text-muted-foreground" />
                    Renovaciones
                </div>
                <Button
                    size="sm"
                    className="rounded-[10px] font-semibold"
                    disabled={!canRenew}
                    title={
                        canRenew
                            ? undefined
                            : 'No tienes permiso para registrar renovaciones'
                    }
                    onClick={() => setRenewing(true)}
                >
                    <Plus className="size-4" />
                    Registrar renovación
                </Button>
            </div>
            <div className="hidden h-11 items-center gap-3 border-b bg-muted px-5 text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase lg:grid lg:grid-cols-[1fr_1fr_1fr_1fr]">
                <div>Fecha de pago</div>
                <div>Tipo</div>
                <div>Monto</div>
                <div>Vencimiento</div>
            </div>
            <div className="flex flex-col">
                {visible.map((renewal) => (
                    <div
                        key={renewal.id}
                        className="grid grid-cols-1 items-center gap-3 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[1fr_1fr_1fr_1fr]"
                    >
                        <div className="font-medium tabular-nums">
                            {renewal.paid_at}
                        </div>
                        <div className="text-[13.5px] font-semibold text-muted-foreground">
                            {RENEWAL_TYPE_LABELS[renewal.type]}
                        </div>
                        <div className="font-bold tabular-nums">
                            {clp(Number(renewal.amount))}
                        </div>
                        <div className="tabular-nums">{renewal.period_end}</div>
                    </div>
                ))}
                {total === 0 && (
                    <div className="p-8 text-center text-sm text-muted-foreground">
                        Esta cuenta no tiene renovaciones registradas.
                    </div>
                )}
            </div>
            <div className="flex flex-wrap items-center justify-between gap-3 border-t px-5 py-3.5">
                <span className="text-[13px] font-semibold text-muted-foreground">
                    Mostrando {from}–{to} de {total} renovación
                    {total !== 1 ? 'es' : ''}
                </span>
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        className="rounded-[10px] bg-card font-semibold"
                        disabled={safePage <= 1}
                        onClick={() => setPage((p) => Math.max(1, p - 1))}
                    >
                        <ChevronLeft className="size-4" />
                        Anterior
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        className="rounded-[10px] bg-card font-semibold"
                        disabled={to >= total}
                        onClick={() => setPage((p) => Math.min(pageCount, p + 1))}
                    >
                        Siguiente
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            </div>

            <AccountRenewDialog
                companyId={companyId}
                account={renewing ? account : null}
                onClose={() => setRenewing(false)}
            />
        </Card>
    );
}
