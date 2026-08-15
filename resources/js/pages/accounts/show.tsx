import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarClock,
    DollarSign,
    Edit,
    Hash,
    KeyRound,
    Layers,
    Mail,
    Tv,
    Users,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { clp } from '@/lib/crm-demo';
import AppLayout from '@/layouts/app-layout';
import accounts from '@/routes/accounts';
import type { BreadcrumbItem } from '@/types';
import { AccountCredentials } from './components/AccountCredentials';
import { AccountRenewals } from './components/AccountRenewals';
import {
    ACCOUNT_STATUS_LABELS,
    accountStatusPill,
    PROFILE_STATUS_LABELS,
    profileStatusPill,
    type Account,
} from './types/Account';

interface Props {
    account: Account;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function MiniStat({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: string | number;
    icon: typeof Layers;
}) {
    return (
        <Card className="gap-1 rounded-2xl px-[18px] py-4">
            <span className="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-muted-foreground">
                <Icon className="size-3.5" />
                {label}
            </span>
            <span className="text-[21px] font-extrabold tracking-tight tabular-nums">
                {value}
            </span>
        </Card>
    );
}

export default function AccountsShow({ account }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const resumen = account.profiles_summary ?? {
        total: 0,
        available: 0,
        occupied: 0,
        maintenance: 0,
    };

    const profiles = (account.profiles ?? [])
        .slice()
        .sort((a, b) => a.number - b.number);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Cuentas', href: accounts.index(companyId).url },
        {
            title: account.code,
            href: accounts.show({ company: companyId, id: account.id }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={account.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={accounts.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Cuentas
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <span className="grid size-16 shrink-0 place-items-center rounded-[16px] border bg-muted text-muted-foreground">
                            <KeyRound className="size-7" />
                        </span>
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {account.email}
                                </h1>
                                <StatusPill
                                    kind={accountStatusPill(account.status)}
                                >
                                    {ACCOUNT_STATUS_LABELS[account.status]}
                                </StatusPill>
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {account.code}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Tv className="size-3.5 opacity-80" />
                                    {account.service?.name ?? '—'}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Mail className="size-3.5 opacity-80" />
                                    {account.email}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        <Link
                            href={
                                accounts.edit({
                                    company: companyId,
                                    id: account.id,
                                }).url
                            }
                        >
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            >
                                <Edit />
                                Editar
                            </Button>
                        </Link>
                    </div>
                </Card>

                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <MiniStat
                        label="Costo"
                        value={clp(Number(account.cost))}
                        icon={DollarSign}
                    />
                    <MiniStat
                        label="Fecha de compra"
                        value={account.purchase_date}
                        icon={CalendarClock}
                    />
                    <MiniStat
                        label="Próxima renovación"
                        value={account.next_renewal}
                        icon={CalendarClock}
                    />
                    <MiniStat
                        label="Perfiles libres"
                        value={`${resumen.available}/${resumen.total}`}
                        icon={Users}
                    />
                </div>

                <AccountCredentials
                    companyId={companyId}
                    accountId={account.id}
                />

                {/* Profiles */}
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b p-5">
                        <div className="inline-flex items-center gap-2 text-base font-bold tracking-tight">
                            <Layers className="size-4 text-muted-foreground" />
                            Perfiles
                        </div>
                        <div className="flex flex-wrap gap-2 text-[12.5px] font-semibold text-muted-foreground">
                            <span>Total {resumen.total}</span>
                            <span>· Disponibles {resumen.available}</span>
                            <span>· Ocupados {resumen.occupied}</span>
                            <span>· Mantenimiento {resumen.maintenance}</span>
                        </div>
                    </div>
                    <div className="hidden h-11 items-center gap-3 border-b bg-muted px-5 text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase lg:grid lg:grid-cols-[60px_1fr_1fr_2fr]">
                        <div>#</div>
                        <div>PIN</div>
                        <div>Estado</div>
                        <div>Notas</div>
                    </div>
                    <div className="flex flex-col">
                        {profiles.map((profile) => (
                            <div
                                key={profile.id}
                                className="grid grid-cols-1 items-center gap-3 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[60px_1fr_1fr_2fr]"
                            >
                                <div className="font-bold text-muted-foreground tabular-nums">
                                    #{profile.number}
                                </div>
                                <div className="font-mono tabular-nums">
                                    {profile.pin ? profile.pin : '—'}
                                </div>
                                <div>
                                    <StatusPill
                                        kind={profileStatusPill(profile.status)}
                                    >
                                        {PROFILE_STATUS_LABELS[profile.status]}
                                    </StatusPill>
                                </div>
                                <div className="text-[13.5px] text-muted-foreground">
                                    {profile.notes ?? '—'}
                                </div>
                            </div>
                        ))}
                        {profiles.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                Esta cuenta no tiene perfiles.
                            </div>
                        )}
                    </div>
                </Card>

                {/* Renovaciones */}
                <AccountRenewals
                    companyId={companyId}
                    account={account}
                    renewals={account.renewals ?? []}
                />

                {account.notes && (
                    <Card className="gap-2 rounded-2xl p-5">
                        <div className="text-base font-bold tracking-tight">
                            Notas
                        </div>
                        <p className="text-[13.5px] whitespace-pre-line text-muted-foreground">
                            {account.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
