import { router, usePage } from '@inertiajs/react';
import {
    Edit,
    Eye,
    KeyRound,
    MoreHorizontal,
    Plus,
    RefreshCw,
    Search,
} from 'lucide-react';
import { useState } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { clp } from '@/lib/crm-demo';
import accounts from '@/routes/accounts';
import {
    ACCOUNT_STATUS_LABELS,
    ACCOUNT_STATUSES,
    accountStatusPill,
    type Account,
    type AccountFilters,
    type AccountMeta,
    type AccountServiceOption,
} from '../types/Account';
import { AccountCredentialsDialog } from './AccountCredentialsDialog';
import { AccountRenewDialog } from './AccountRenewDialog';

interface AccountListProps {
    accounts: Account[];
    meta: AccountMeta;
    filters: AccountFilters;
    services: AccountServiceOption[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    auth?: { permissions?: string[] };
    [key: string]: unknown;
}

export function AccountList({
    accounts: items,
    meta,
    filters: initialFilters,
    services,
}: AccountListProps) {
    const { currentCompany, auth } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const canRenew = auth?.permissions?.includes('accounts.renew') ?? false;

    const [searchEmail, setSearchEmail] = useState(initialFilters.email ?? '');
    const [searchCode, setSearchCode] = useState(initialFilters.code ?? '');
    const [statusFilter, setStatusFilter] = useState(
        initialFilters.status ?? 'all',
    );
    const [serviceFilter, setServiceFilter] = useState(
        initialFilters.service_id ?? 'all',
    );
    const [credentialsAccount, setCredentialsAccount] = useState<Account | null>(
        null,
    );
    const [renewAccount, setRenewAccount] = useState<Account | null>(null);

    const handleSearch = () => {
        const filters: Record<string, string> = {};

        if (searchEmail.trim()) filters.email = searchEmail.trim();
        if (searchCode.trim()) filters.code = searchCode.trim();
        if (statusFilter !== 'all') filters.status = statusFilter;
        if (serviceFilter !== 'all') filters.service_id = serviceFilter;

        router.get(accounts.index(companyId).url, filters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClearSearch = () => {
        setSearchEmail('');
        setSearchCode('');
        setStatusFilter('all');
        setServiceFilter('all');
        router.get(accounts.index(companyId).url);
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Cuentas
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} cuenta{meta.total !== 1 ? 's' : ''} en el
                        inventario
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() => router.visit(accounts.create(companyId).url)}
                >
                    <Plus />
                    Nueva cuenta
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="search-email">Email</Label>
                        <Input
                            id="search-email"
                            type="text"
                            value={searchEmail}
                            onChange={(e) => setSearchEmail(e.target.value)}
                            onKeyDown={(e) =>
                                e.key === 'Enter' && handleSearch()
                            }
                            placeholder="Buscar por email..."
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="search-code">Código</Label>
                        <Input
                            id="search-code"
                            type="text"
                            value={searchCode}
                            onChange={(e) => setSearchCode(e.target.value)}
                            onKeyDown={(e) =>
                                e.key === 'Enter' && handleSearch()
                            }
                            placeholder="Buscar por código..."
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="status-filter">Estado</Label>
                        <Select
                            value={statusFilter}
                            onValueChange={(value) => setStatusFilter(value)}
                        >
                            <SelectTrigger id="status-filter">
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                {ACCOUNT_STATUSES.map((e) => (
                                    <SelectItem key={e} value={e}>
                                        {ACCOUNT_STATUS_LABELS[e]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="service-filter">Servicio</Label>
                        <Select
                            value={serviceFilter}
                            onValueChange={(value) => setServiceFilter(value)}
                        >
                            <SelectTrigger id="service-filter">
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                {services.map((s) => (
                                    <SelectItem key={s.id} value={s.id}>
                                        {s.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button onClick={handleSearch} variant="default">
                        <Search className="mr-2 h-4 w-4" />
                        Buscar
                    </Button>
                    <Button onClick={handleClearSearch} variant="outline">
                        Limpiar
                    </Button>
                </div>
            </div>

            <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.2fr_1.1fr_1fr_1.1fr_0.9fr_0.7fr]">
                    {[
                        'Código',
                        'Cuenta',
                        'Estado',
                        'Perfiles',
                        'Renovación',
                        'Costo',
                        'Acciones',
                    ].map((h, i) => (
                        <div
                            key={h}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                i === 6 ? 'text-right' : ''
                            }`}
                        >
                            {h}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {items.map((account) => (
                        <div
                            key={account.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.2fr_1.1fr_1fr_1.1fr_0.9fr_0.7fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    accounts.show({
                                        company: companyId,
                                        id: account.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold text-muted-foreground tabular-nums">
                                    {account.code}
                                </span>
                            </div>
                            <div className="flex items-center gap-3">
                                <span className="grid size-10 shrink-0 place-items-center rounded-[11px] border bg-muted text-muted-foreground">
                                    <KeyRound className="size-5" />
                                </span>
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {account.email}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {account.service?.name ?? '—'}
                                    </span>
                                </div>
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={accountStatusPill(account.status)}
                                >
                                    {ACCOUNT_STATUS_LABELS[account.status]}
                                </StatusPill>
                            </div>
                            <div className="hidden lg:block">
                                <span className="text-[13.5px] font-semibold tabular-nums">
                                    {account.profiles_summary
                                        ? `${account.profiles_summary.available}/${account.profiles_summary.total} libres`
                                        : '—'}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <span className="font-semibold tabular-nums">
                                    {account.next_renewal}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <span className="font-bold tabular-nums">
                                    {clp(Number(account.cost))}
                                </span>
                            </div>
                            <div
                                className="flex items-center justify-end gap-2"
                                onClick={(e) => e.stopPropagation()}
                            >
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            className="rounded-[10px] bg-card"
                                            aria-label="Opciones"
                                        >
                                            <MoreHorizontal className="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            onClick={() =>
                                                router.visit(
                                                    accounts.show({
                                                        company: companyId,
                                                        id: account.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            onClick={() =>
                                                router.visit(
                                                    accounts.edit({
                                                        company: companyId,
                                                        id: account.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Edit className="mr-2 h-4 w-4" />
                                            Editar
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            onClick={() =>
                                                setCredentialsAccount(account)
                                            }
                                        >
                                            <KeyRound className="mr-2 h-4 w-4" />
                                            Ver credenciales
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            disabled={!canRenew}
                                            onClick={() =>
                                                setRenewAccount(account)
                                            }
                                        >
                                            <RefreshCw className="mr-2 h-4 w-4" />
                                            Registrar renovación
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))}
                    {items.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {items.length} de {meta.total} cuenta
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>

            <AccountCredentialsDialog
                companyId={companyId}
                account={credentialsAccount}
                onClose={() => setCredentialsAccount(null)}
            />

            <AccountRenewDialog
                companyId={companyId}
                account={renewAccount}
                onClose={() => setRenewAccount(null)}
            />
        </div>
    );
}
