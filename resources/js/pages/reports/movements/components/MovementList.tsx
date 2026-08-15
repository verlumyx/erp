import { router, usePage } from '@inertiajs/react';
import { Loader2, Search } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    categoryLabel,
    typeLabel,
    useTransactionCatalog,
} from '@/lib/transaction-catalog';
import reports from '@/routes/reports';
import type { Movement, MovementFilters, MovementMeta } from '../types/Movement';

interface MovementListProps {
    movements: Movement[];
    meta: MovementMeta;
    filters: MovementFilters;
    searched: boolean;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
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

export function MovementList({
    movements,
    meta,
    filters: initialFilters,
    searched,
}: MovementListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const catalog = useTransactionCatalog();

    const [filters, setFilters] = useState<MovementFilters>(initialFilters);
    const [processing, setProcessing] = useState(false);

    const handleSearch = () => {
        router.get(
            reports.movements.index(companyId).url,
            { ...filters, searched: '1' } as unknown as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(reports.movements.index(companyId).url, undefined, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <div className="flex flex-col gap-5">
            <div>
                <h1 className="text-[27px] font-extrabold tracking-tight">Movimientos</h1>
                <p className="mt-1 text-[14.5px] text-muted-foreground">
                    Reporte de ingresos y egresos registrados
                </p>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="filter-date-from">Desde</Label>
                        <Input
                            id="filter-date-from"
                            type="date"
                            value={filters.date_from ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, date_from: e.target.value })
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-date-to">Hasta</Label>
                        <Input
                            id="filter-date-to"
                            type="date"
                            value={filters.date_to ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, date_to: e.target.value })
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-type">Tipo</Label>
                        <Select
                            value={filters.type ?? 'todos'}
                            onValueChange={(value) =>
                                setFilters({
                                    ...filters,
                                    type: value === 'todos' ? undefined : value,
                                })
                            }
                        >
                            <SelectTrigger id="filter-type" className="w-full">
                                <SelectValue placeholder="Tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos</SelectItem>
                                {catalog.types.map((t) => (
                                    <SelectItem key={t.value} value={t.value}>
                                        {t.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-category">Categoría</Label>
                        <Select
                            value={filters.category ?? 'todas'}
                            onValueChange={(value) =>
                                setFilters({
                                    ...filters,
                                    category: value === 'todas' ? undefined : value,
                                })
                            }
                        >
                            <SelectTrigger id="filter-category" className="w-full">
                                <SelectValue placeholder="Categoría" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todas">Todas</SelectItem>
                                {catalog.categories.map((c) => (
                                    <SelectItem key={c.value} value={c.value}>
                                        {c.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button onClick={handleSearch} variant="default" disabled={processing}>
                        {processing ? (
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        ) : (
                            <Search className="mr-2 h-4 w-4" />
                        )}
                        {processing ? 'Buscando…' : 'Buscar'}
                    </Button>
                    <Button onClick={handleClear} variant="outline" disabled={processing}>
                        Limpiar
                    </Button>
                </div>
            </div>

            <Card className="relative overflow-hidden rounded-2xl py-0">
                {processing && (
                    <div className="absolute inset-0 z-10 flex items-center justify-center bg-background/60 backdrop-blur-[1px]">
                        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                    </div>
                )}
                <Table>
                    <TableHeader>
                        <TableRow className="bg-muted hover:bg-muted">
                            <TableHead className="whitespace-nowrap">Fecha</TableHead>
                            <TableHead>Tipo</TableHead>
                            <TableHead>Categoría</TableHead>
                            <TableHead>Descripción</TableHead>
                            <TableHead>Método de pago</TableHead>
                            <TableHead>Referencia</TableHead>
                            <TableHead className="text-right">Monto</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {movements.map((movement) => (
                            <TableRow key={movement.id}>
                                <TableCell className="font-semibold whitespace-nowrap tabular-nums">
                                    {movement.date ?? '—'}
                                </TableCell>
                                <TableCell>
                                    <span
                                        className={`inline-flex rounded-full px-2 py-0.5 text-xs font-bold ${
                                            movement.type === 'income'
                                                ? 'bg-ok-soft text-ok'
                                                : 'bg-bad-soft text-bad'
                                        }`}
                                    >
                                        {typeLabel(catalog, movement.type)}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    {categoryLabel(catalog, movement.category)}
                                </TableCell>
                                <TableCell className="max-w-xs truncate text-muted-foreground">
                                    {movement.description ?? '—'}
                                </TableCell>
                                <TableCell>{movement.payment_method ?? '—'}</TableCell>
                                <TableCell className="text-muted-foreground">
                                    {movement.reference ?? '—'}
                                </TableCell>
                                <TableCell className="text-right font-bold whitespace-nowrap tabular-nums">
                                    {formatAmount(movement.amount, movement.currency)}
                                </TableCell>
                            </TableRow>
                        ))}
                        {movements.length === 0 && (
                            <TableRow className="hover:bg-transparent">
                                <TableCell
                                    colSpan={7}
                                    className="p-12 text-center text-sm text-muted-foreground"
                                >
                                    {searched
                                        ? 'Sin movimientos para tu búsqueda.'
                                        : 'Aplica los filtros y presiona Buscar para ver los movimientos.'}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
                {searched && movements.length > 0 && (
                    <div className="border-t px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                        {movements.length} de {meta.total} movimiento
                        {meta.total !== 1 ? 's' : ''}
                    </div>
                )}
            </Card>
        </div>
    );
}
