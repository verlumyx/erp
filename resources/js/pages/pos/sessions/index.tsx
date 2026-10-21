import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Pagination } from '@/components/pagination';
import {
    Plus,
    Search,
    Eye,
    Monitor,
    User,
    Calendar,
    ArrowRight,
    CheckCircle2,
    Clock,
    FileSpreadsheet,
} from 'lucide-react';
import type { BreadcrumbItem } from '@/types';
import type { PosRegister, PosSession } from '../types/Pos';

interface SessionsIndexProps {
    sessions: {
        data: PosSession[];
        meta?: {
            current_page: number;
            last_page: number;
            total: number;
            per_page: number;
        };
        links?: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search?: string;
        status?: string;
        user_id?: string;
        pos_register_id?: string;
        from_date?: string;
        to_date?: string;
        page?: number;
    };
    registers: PosRegister[];
    users: Array<{ id: string; name: string }>;
}

export default function PosSessionsIndexPage({
    sessions: sessionsProp,
    filters: initialFilters,
    registers,
    users,
}: SessionsIndexProps) {
    const { currentCompany, auth } = usePage<{
        currentCompany?: { id: string; name: string };
        auth?: { user?: { id: string; name: string } };
    }>().props;
    const companyId = currentCompany?.id || '';
    const currentUserId = auth?.user?.id;

    const sessions = sessionsProp.data || [];
    const meta = sessionsProp.meta;

    const [filters, setFilters] = useState(initialFilters);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Punto de Venta', href: `/${companyId}/pos` },
        { title: 'Sesiones y Cortes de Caja', href: `/${companyId}/pos-sessions` },
    ];

    const paginationMeta = {
        total: meta?.total ?? sessions.length,
        limit: meta?.per_page ?? 10,
        offset: meta ? (meta.current_page - 1) * (meta.per_page ?? 10) : 0,
    };

    const applyFilters = (newFilters: typeof filters) => {
        setFilters(newFilters);
        router.get(
            `/${companyId}/pos-sessions`,
            { ...newFilters },
            { preserveState: true, replace: true }
        );
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters({ ...filters, page: 1 });
    };

    const handlePageChange = (offset: number) => {
        const page = Math.floor(offset / paginationMeta.limit) + 1;
        applyFilters({ ...filters, page });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'confirmed':
            case 'open':
                return (
                    <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                        <Clock className="h-3 w-3" /> Abierta
                    </span>
                );
            case 'completed':
            case 'closed':
                return (
                    <span className="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground">
                        <CheckCircle2 className="h-3 w-3" /> Cerrada
                    </span>
                );
            case 'cancelled':
                return (
                    <span className="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                        Anulada
                    </span>
                );
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Sesiones y Cortes de Caja" />

            <div className="mx-auto w-full max-w-7xl p-6 space-y-6">
                {/* Header title and open session button */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Sesiones y Cortes de Caja
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Control de turnos, arqueos ciegos y reportes Z por cajero y terminal
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link href={`/${companyId}/pos`}>
                            <Button variant="outline" className="gap-2">
                                <Monitor className="h-4 w-4" /> Ir a Terminal POS
                            </Button>
                        </Link>
                        <Link href={`/${companyId}/pos-sessions/open`}>
                            <Button className="gap-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold">
                                <Plus className="h-4 w-4" /> Abrir Nuevo Turno
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Filter bar */}
                <Card className="p-4 shadow-xs border-border">
                    <form onSubmit={handleSearch} className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Buscar por código (COR...)"
                                value={filters.search || ''}
                                onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                                className="pl-9 h-10 text-sm"
                            />
                        </div>

                        <div>
                            <select
                                value={filters.status || ''}
                                onChange={(e) => applyFilters({ ...filters, status: e.target.value || undefined, page: 1 })}
                                className="h-10 w-full rounded-md border border-input bg-card px-3 text-sm font-medium focus:border-primary focus:outline-hidden"
                            >
                                <option value="">Todos los Estados</option>
                                <option value="confirmed">Abierta / En Operación</option>
                                <option value="completed">Cerrada (Corte Z)</option>
                                <option value="cancelled">Anulada</option>
                            </select>
                        </div>

                        <div>
                            <select
                                value={filters.pos_register_id || ''}
                                onChange={(e) => applyFilters({ ...filters, pos_register_id: e.target.value || undefined, page: 1 })}
                                className="h-10 w-full rounded-md border border-input bg-card px-3 text-sm font-medium focus:border-primary focus:outline-hidden"
                            >
                                <option value="">Todas las Cajas</option>
                                {registers.map((r) => (
                                    <option key={r.id} value={r.id}>
                                        {r.name} ({r.code})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <select
                                value={filters.user_id || ''}
                                onChange={(e) => applyFilters({ ...filters, user_id: e.target.value || undefined, page: 1 })}
                                className="h-10 w-full rounded-md border border-input bg-card px-3 text-sm font-medium focus:border-primary focus:outline-hidden"
                            >
                                <option value="">Todos los Cajeros</option>
                                {users.map((u) => (
                                    <option key={u.id} value={u.id}>
                                        {u.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </form>
                </Card>

                {/* Sessions Table */}
                <Card className="overflow-hidden shadow-xs border-border">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50 border-b border-border text-xs uppercase text-muted-foreground font-semibold">
                                <tr>
                                    <th className="p-3.5">Código</th>
                                    <th className="p-3.5">Caja / Terminal</th>
                                    <th className="p-3.5">Cajero</th>
                                    <th className="p-3.5">Apertura</th>
                                    <th className="p-3.5">Cierre</th>
                                    <th className="p-3.5 text-center">Ventas</th>
                                    <th className="p-3.5 text-right">Total Facturado</th>
                                    <th className="p-3.5 text-center">Estado</th>
                                    <th className="p-3.5 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {sessions.length === 0 ? (
                                    <tr>
                                        <td colSpan={9} className="p-8 text-center text-muted-foreground">
                                            No se encontraron sesiones de caja registradas
                                        </td>
                                    </tr>
                                ) : (
                                    sessions.map((session) => (
                                        <tr key={session.id} className="hover:bg-muted/30 transition-colors">
                                            <td className="p-3.5 font-mono font-bold text-primary">
                                                {session.code}
                                            </td>
                                            <td className="p-3.5 font-medium text-foreground">
                                                {session.pos_register_name || 'N/A'}
                                            </td>
                                            <td className="p-3.5 text-foreground">
                                                {session.user_name || 'N/A'}
                                            </td>
                                            <td className="p-3.5 text-xs text-muted-foreground font-mono">
                                                {new Date(session.opened_at).toLocaleString()}
                                            </td>
                                            <td className="p-3.5 text-xs text-muted-foreground font-mono">
                                                {session.closed_at
                                                    ? new Date(session.closed_at).toLocaleString()
                                                    : '—'}
                                            </td>
                                            <td className="p-3.5 text-center font-mono font-semibold">
                                                {session.sales_count ?? 0}
                                            </td>
                                            <td className="p-3.5 text-right font-mono font-bold text-foreground">
                                                $ {Number(session.total_sales_amount || 0).toFixed(2)}
                                            </td>
                                            <td className="p-3.5 text-center">
                                                {getStatusBadge(session.status)}
                                            </td>
                                            <td className="p-3.5 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    {session.status === 'confirmed' &&
                                                    session.user_id === currentUserId ? (
                                                        <Link href={`/${companyId}/pos`}>
                                                            <Button size="sm" variant="outline" className="h-8 gap-1 text-emerald-600 border-emerald-300 hover:bg-emerald-50">
                                                                <span>Operar</span>
                                                                <ArrowRight className="h-3.5 w-3.5" />
                                                            </Button>
                                                        </Link>
                                                    ) : null}

                                                    <Link href={`/${companyId}/pos-sessions/${session.id}`}>
                                                        <Button size="sm" variant="ghost" className="h-8 gap-1">
                                                            <Eye className="h-3.5 w-3.5" />
                                                            <span className="hidden sm:inline">Reporte</span>
                                                        </Button>
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    <Pagination
                        meta={paginationMeta}
                        onPageChange={handlePageChange}
                        label="sesión"
                        labelPlural="sesiones"
                    />
                </Card>
            </div>
        </AppLayout>
    );
}
