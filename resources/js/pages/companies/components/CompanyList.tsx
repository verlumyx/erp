import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Eye, Edit, Plus, Search, ChevronDown, Power } from 'lucide-react';
import { Company, CompanyFilters, CompanyMeta } from '../types/Company';
import companies from '@/routes/companies';

interface CompanyListProps {
    companies: Company[];
    meta: CompanyMeta;
    filters: CompanyFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    auth: { user: { is_system_owner: boolean } };
    [key: string]: unknown;
}

export function CompanyList({ companies: items, meta, filters: initialFilters }: CompanyListProps) {
    const { currentCompany, auth } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const isSystemOwner = auth.user.is_system_owner;

    const [filters, setFilters] = useState<CompanyFilters>(initialFilters);

    const handleSearch = () => {
        router.get(companies.index(companyId).url, filters as Record<string, string>, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClear = () => {
        const cleared: CompanyFilters = {};
        setFilters(cleared);
        router.get(companies.index(companyId).url);
    };

    const handleToggleStatus = (company: Company) => {
        router.put(companies.updateStatus({ company: companyId, id: company.id }).url, {
            status: company.status === 'active' ? 'inactive' : 'active',
        });
    };

    const statusBadge = (status: string) =>
        status === 'active' ? (
            <Badge className="bg-green-100 text-green-800 hover:bg-green-100">Activo</Badge>
        ) : (
            <Badge variant="secondary" className="bg-red-100 text-red-800 hover:bg-red-100">Inactivo</Badge>
        );

    const formatDate = (date: string) =>
        new Date(date).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold">Gestión de Empresas</h1>
                    <p className="text-muted-foreground">Gestiona las empresas del sistema</p>
                </div>
                <Button onClick={() => router.visit(companies.create(companyId).url)}>
                    <Plus className="w-4 h-4 mr-2" />
                    Nueva Empresa
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="name">Nombre</Label>
                        <Input
                            id="name"
                            placeholder="Buscar por nombre..."
                            value={filters.name ?? ''}
                            onChange={(e) => setFilters({ ...filters, name: e.target.value })}
                            onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="status">Estado</Label>
                        <Select
                            value={filters.status ?? 'all'}
                            onValueChange={(v) => setFilters({ ...filters, status: v === 'all' ? undefined : v })}
                        >
                            <SelectTrigger id="status">
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="active">Activo</SelectItem>
                                <SelectItem value="inactive">Inactivo</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div className="flex justify-end gap-2 mt-4">
                    <Button onClick={handleSearch}>
                        <Search className="w-4 h-4 mr-2" />
                        Buscar
                    </Button>
                    <Button variant="outline" onClick={handleClear}>
                        Limpiar
                    </Button>
                </div>
            </div>

            <Card>
                <CardContent className="p-0">
                    {items.length === 0 ? (
                        <div className="text-center py-12">
                            <p className="text-muted-foreground mb-4">No se encontraron empresas.</p>
                            <Button onClick={() => router.visit(companies.create(companyId).url)}>
                                <Plus className="w-4 h-4 mr-2" />
                                Crear Primera Empresa
                            </Button>
                        </div>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead>Creada</TableHead>
                                        <TableHead className="text-right">Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.map((company) => (
                                        <TableRow key={company.id}>
                                            <TableCell className="font-medium">{company.name}</TableCell>
                                            <TableCell>{statusBadge(company.status)}</TableCell>
                                            <TableCell className="max-w-xs">
                                                <span className="truncate block" title={company.description ?? ''}>
                                                    {company.description ?? '-'}
                                                </span>
                                            </TableCell>
                                            <TableCell>{formatDate(company.created_at)}</TableCell>
                                            <TableCell className="text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="outline" size="sm">
                                                            Opciones <ChevronDown className="ml-1 h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                router.visit(
                                                                    companies.show({ company: companyId, id: company.id }).url,
                                                                )
                                                            }
                                                        >
                                                            <Eye className="mr-2 h-4 w-4" />
                                                            Ver
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                router.visit(
                                                                    companies.edit({ company: companyId, id: company.id }).url,
                                                                )
                                                            }
                                                        >
                                                            <Edit className="mr-2 h-4 w-4" />
                                                            Editar
                                                        </DropdownMenuItem>
                                                        {isSystemOwner && (
                                                            <>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem onClick={() => handleToggleStatus(company)}>
                                                                    <Power className="mr-2 h-4 w-4" />
                                                                    {company.status === 'active' ? 'Inactivar' : 'Activar'}
                                                                </DropdownMenuItem>
                                                            </>
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            <div className="px-4 py-3 border-t text-sm text-muted-foreground">
                                {meta.total} empresa{meta.total !== 1 ? 's' : ''} en total
                            </div>
                        </>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
