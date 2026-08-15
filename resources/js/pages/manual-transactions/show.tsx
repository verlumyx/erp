import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Check, NotebookPen, X } from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import {
    categoryLabel,
    typeLabel,
    useTransactionCatalog,
} from '@/lib/transaction-catalog';
import manualTransactions from '@/routes/manual-transactions';
import type { BreadcrumbItem } from '@/types';
import {
    MANUAL_TRANSACTION_STATUS_LABELS,
    manualTransactionStatusPill,
    type ManualTransaction,
} from './types/ManualTransaction';

interface Props {
    manualTransaction: ManualTransaction;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    auth?: { permissions?: string[] };
    [key: string]: unknown;
}

function formatAmount(amount: string, currency: string): string {
    const value = Number(amount);
    if (Number.isNaN(value)) {
        return `${amount} ${currency}`;
    }
    return `${value.toLocaleString('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} ${currency}`;
}

export default function ManualTransactionsShow({ manualTransaction }: Props) {
    const { currentCompany, auth } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const catalog = useTransactionCatalog();
    const can = (p: string) => auth?.permissions?.includes(p) ?? false;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Transacciones manuales',
            href: manualTransactions.index(companyId).url,
        },
        { title: manualTransaction.code, href: '#' },
    ];

    const resolve = (action: 'approve' | 'cancel') => {
        const url =
            action === 'approve'
                ? manualTransactions.approve({
                      company: companyId,
                      id: manualTransaction.id,
                  }).url
                : manualTransactions.cancel({
                      company: companyId,
                      id: manualTransaction.id,
                  }).url;
        router.post(url, {}, { preserveScroll: true });
    };

    const details: { label: string; value: string }[] = [
        { label: 'Fecha', value: manualTransaction.date ?? '—' },
        {
            label: 'Registrado por',
            value: manualTransaction.recorded_by_user?.name ?? '—',
        },
        { label: 'Descripción', value: manualTransaction.description ?? '—' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Transacción ${manualTransaction.code}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={manualTransactions.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Transacciones manuales
                </Link>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <span className="grid size-12 shrink-0 place-items-center rounded-[14px] border bg-muted text-muted-foreground">
                            <NotebookPen className="size-6" />
                        </span>
                        <div>
                            <div className="flex items-center gap-2.5">
                                <h1 className="text-[27px] font-extrabold tracking-tight tabular-nums">
                                    {manualTransaction.code}
                                </h1>
                                <StatusPill
                                    kind={manualTransactionStatusPill(
                                        manualTransaction.status,
                                    )}
                                >
                                    {
                                        MANUAL_TRANSACTION_STATUS_LABELS[
                                            manualTransaction.status
                                        ]
                                    }
                                </StatusPill>
                            </div>
                            <p className="text-[14.5px] text-muted-foreground">
                                Total{' '}
                                {formatAmount(
                                    manualTransaction.total,
                                    manualTransaction.currency,
                                )}
                            </p>
                        </div>
                    </div>

                    {manualTransaction.is_pending && (
                        <div className="flex gap-2.5">
                            {manualTransaction.can_be_approved &&
                                can('manual-transactions.approve') && (
                                    <Button
                                        className="rounded-[10px] font-semibold"
                                        onClick={() => resolve('approve')}
                                    >
                                        <Check className="mr-1.5 size-4" />
                                        Aprobar
                                    </Button>
                                )}
                            {manualTransaction.can_be_cancelled &&
                                can('manual-transactions.cancel') && (
                                    <Button
                                        variant="outline"
                                        className="rounded-[10px] bg-card font-semibold"
                                        onClick={() => resolve('cancel')}
                                    >
                                        <X className="mr-1.5 size-4" />
                                        Cancelar
                                    </Button>
                                )}
                        </div>
                    )}
                </div>

                <Card className="rounded-2xl p-6">
                    <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {details.map((detail) => (
                            <div key={detail.label} className="flex flex-col">
                                <dt className="text-[12.5px] font-semibold text-muted-foreground">
                                    {detail.label}
                                </dt>
                                <dd className="font-semibold">
                                    {detail.value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </Card>

                <Card className="overflow-hidden rounded-2xl py-0">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted hover:bg-muted">
                                <TableHead>Tipo</TableHead>
                                <TableHead>Categoría</TableHead>
                                <TableHead>Descripción</TableHead>
                                <TableHead className="text-right">
                                    Monto
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {(manualTransaction.lines ?? []).map((line) => (
                                <TableRow key={line.id}>
                                    <TableCell>
                                        <span
                                            className={`inline-flex rounded-full px-2 py-0.5 text-xs font-bold ${
                                                line.type === 'income'
                                                    ? 'bg-ok-soft text-ok'
                                                    : 'bg-bad-soft text-bad'
                                            }`}
                                        >
                                            {typeLabel(catalog, line.type)}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        {categoryLabel(catalog, line.category)}
                                    </TableCell>
                                    <TableCell className="max-w-xs truncate text-muted-foreground">
                                        {line.description ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right font-bold tabular-nums">
                                        {formatAmount(
                                            line.amount,
                                            manualTransaction.currency,
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </Card>

                {manualTransaction.notes && (
                    <Card className="rounded-2xl p-6">
                        <h2 className="text-[12.5px] font-semibold text-muted-foreground">
                            Notas
                        </h2>
                        <p className="mt-1 text-sm">
                            {manualTransaction.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
