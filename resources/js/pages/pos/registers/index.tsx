import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Select2, type OptionType } from '@/components/ui/select2';
import {
    Plus,
    Search,
    Edit,
    Monitor,
    Warehouse,
    Tag,
    UserCheck,
    Power,
    CheckCircle,
    XCircle,
} from 'lucide-react';
import type { BreadcrumbItem } from '@/types';
import type { PosRegister } from '../types/Pos';

interface RegistersIndexProps {
    registers: {
        data: PosRegister[];
        meta?: {
            current_page: number;
            last_page: number;
            total: number;
            per_page: number;
        };
    };
    filters: {
        search?: string;
        status?: string;
        warehouse_id?: string;
    };
    warehouses: Array<{ id: string; name: string }>;
}

export default function PosRegistersIndexPage({
    registers: registersProp,
    filters: initialFilters,
    warehouses,
}: RegistersIndexProps) {
    const { currentCompany } = usePage<{ currentCompany?: { id: string; name: string } }>().props;
    const companyId = currentCompany?.id || '';

    const registers = registersProp.data || [];
    const [filters, setFilters] = useState(initialFilters);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Punto de Venta', href: `/${companyId}/pos` },
        { title: 'Cajas Registradoras', href: `/${companyId}/pos-registers` },
    ];

    const applyFilters = (newFilters: typeof filters) => {
        setFilters(newFilters);
        router.get(
            `/${companyId}/pos-registers`,
            { ...newFilters },
            { preserveState: true, replace: true }
        );
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters(filters);
    };

    const statusOptions: OptionType[] = [
        { value: '', label: 'Todos los Estados' },
        { value: 'active', label: 'Activas' },
        { value: 'inactive', label: 'Inactivas' },
    ];

    const warehouseOptions: OptionType[] = [
        { value: '', label: 'Todos los Almacenes' },
        ...warehouses.map((w) => ({ value: w.id, label: w.name })),
    ];

    const toggleStatus = (register: PosRegister) => {
        const nextStatus = register.status === 'active' ? 'inactive' : 'active';
        if (confirm(`¿Deseas cambiar el estado de la caja "${register.name}" a ${nextStatus === 'active' ? 'Activa' : 'Inactiva'}?`)) {
            router.put(`/${companyId}/pos-registers/${register.id}/status`, { status: nextStatus });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cajas Registradoras" />

            <div className="mx-auto w-full max-w-7xl p-6 space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Cajas Registradoras (POS)
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Gestión de terminales físicas, almacenes de despacho y listas de precio asignadas
                        </p>
                    </div>

                    <Link href={`/${companyId}/pos-registers/create`}>
                        <Button className="gap-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold">
                            <Plus className="h-4 w-4" /> Nueva Caja Registradora
                        </Button>
                    </Link>
                </div>

                {/* Filter bar */}
                <Card className="p-4 shadow-xs border-border">
                    <form onSubmit={handleSearch} className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Buscar por código o nombre..."
                                value={filters.search || ''}
                                onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                                className="pl-9 h-10 text-sm"
                            />
                        </div>

                        <div>
                            <Select2
                                options={statusOptions}
                                value={statusOptions.find((o) => o.value === (filters.status || '')) ?? statusOptions[0]}
                                onChange={(opt) => applyFilters({ ...filters, status: opt?.value || undefined })}
                                size="md"
                                placeholder="Estado"
                            />
                        </div>

                        <div>
                            <Select2
                                options={warehouseOptions}
                                value={warehouseOptions.find((o) => o.value === (filters.warehouse_id || '')) ?? warehouseOptions[0]}
                                onChange={(opt) => applyFilters({ ...filters, warehouse_id: opt?.value || undefined })}
                                size="md"
                                placeholder="Almacén"
                            />
                        </div>
                    </form>
                </Card>

                {/* Registers Table */}
                <Card className="overflow-hidden shadow-xs border-border">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50 border-b border-border text-xs uppercase text-muted-foreground font-semibold">
                                <tr>
                                    <th className="p-3.5">Código</th>
                                    <th className="p-3.5">Nombre</th>
                                    <th className="p-3.5">Almacén de Despacho</th>
                                    <th className="p-3.5">Lista de Precios</th>
                                    <th className="p-3.5">Cliente Mostrador</th>
                                    <th className="p-3.5 text-center">Permite Descuentos</th>
                                    <th className="p-3.5 text-center">Estado</th>
                                    <th className="p-3.5 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {registers.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="p-8 text-center text-muted-foreground">
                                            No hay cajas registradoras registradas
                                        </td>
                                    </tr>
                                ) : (
                                    registers.map((r) => (
                                        <tr key={r.id} className="hover:bg-muted/30 transition-colors">
                                            <td className="p-3.5 font-mono font-bold text-primary">
                                                {r.code}
                                            </td>
                                            <td className="p-3.5 font-bold text-foreground">
                                                <div className="flex items-center gap-2">
                                                    <Monitor className="h-4 w-4 text-muted-foreground" />
                                                    <span>{r.name}</span>
                                                </div>
                                            </td>
                                            <td className="p-3.5 text-foreground">
                                                <div className="flex items-center gap-1.5">
                                                    <Warehouse className="h-3.5 w-3.5 text-muted-foreground" />
                                                    <span>{r.warehouse_name || 'N/A'}</span>
                                                </div>
                                            </td>
                                            <td className="p-3.5 text-muted-foreground">
                                                {r.price_list_name ? (
                                                    <div className="flex items-center gap-1.5">
                                                        <Tag className="h-3.5 w-3.5" />
                                                        <span>{r.price_list_name}</span>
                                                    </div>
                                                ) : (
                                                    <span className="italic">Por defecto</span>
                                                )}
                                            </td>
                                            <td className="p-3.5 text-muted-foreground">
                                                <div className="flex items-center gap-1.5">
                                                    <UserCheck className="h-3.5 w-3.5" />
                                                    <span>{r.default_client_name || 'Consumidor Final'}</span>
                                                </div>
                                            </td>
                                            <td className="p-3.5 text-center">
                                                {r.allow_custom_discounts === 'yes' ? (
                                                    <Badge variant="outline" className="text-emerald-700 bg-emerald-50 border-emerald-300">
                                                        Sí (Máx {r.max_discount_percent}%)
                                                    </Badge>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">No</span>
                                                )}
                                            </td>
                                            <td className="p-3.5 text-center">
                                                {r.status === 'active' ? (
                                                    <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                                        <CheckCircle className="h-3 w-3" /> Activa
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-0.5 text-xs font-semibold text-muted-foreground">
                                                        <XCircle className="h-3 w-3" /> Inactiva
                                                    </span>
                                                )}
                                            </td>
                                            <td className="p-3.5 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link href={`/${companyId}/pos-registers/${r.id}/edit`}>
                                                        <Button size="sm" variant="ghost" className="h-8 w-8 p-0">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => toggleStatus(r)}
                                                        className="h-8 w-8 p-0 text-muted-foreground hover:text-foreground"
                                                        title={r.status === 'active' ? 'Desactivar' : 'Activar'}
                                                    >
                                                        <Power className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
