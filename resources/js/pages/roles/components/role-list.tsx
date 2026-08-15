import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Role, RoleSearchFilters } from '@/types/role';
import { Eye, Edit, Search, Plus, ChevronDown, Power } from 'lucide-react';
import roles from '@/routes/roles';

interface RoleListProps {
  roles: Role[];
  loading?: boolean;
  error?: string | null;
  onSearch: (filters: RoleSearchFilters) => void;
}

interface PageProps {
  currentCompany?: { id: string; name: string } | null;
  [key: string]: unknown;
}

export const RoleList: React.FC<RoleListProps> = ({
  roles: items,
  loading = false,
  error,
  onSearch
}) => {
  const { currentCompany } = usePage<PageProps>().props;
  const companyId = currentCompany!.id;

  const [filters, setFilters] = useState<RoleSearchFilters>({
    name: '',
    status: undefined,
    description: ''
  });

  const handleFilterChange = (field: keyof RoleSearchFilters, value: string | undefined) => {
    const newFilters = {
      ...filters,
      [field]: value || undefined
    };
    setFilters(newFilters);
  };

  const handleSearch = () => {
    onSearch(filters);
  };

  const handleClearFilters = () => {
    const clearedFilters = {
      name: '',
      status: undefined,
      description: ''
    };
    setFilters(clearedFilters);
    onSearch(clearedFilters);
  };

  const handleToggleStatus = (role: Role) => {
    router.put(roles.updateStatus({ company: companyId, id: role.id }).url, {
      status: role.status === 'active' ? 'inactive' : 'active',
    });
  };

  const getStatusBadge = (status: string) => {
    return status === 'active' ? (
      <Badge variant="default" className="bg-green-100 text-green-800">
        Activo
      </Badge>
    ) : (
      <Badge variant="secondary" className="bg-red-100 text-red-800">
        Inactivo
      </Badge>
    );
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold">Gestión de Roles</h1>
          <p className="text-muted-foreground">Gestiona los roles del sistema</p>
        </div>
        <Button onClick={() => router.visit(roles.create(companyId).url)}>
          <Plus className="w-4 h-4 mr-2" />
          Nuevo Rol
        </Button>
      </div>

      <div className="bg-white rounded-lg border border-gray-200 p-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label htmlFor="search-name">Nombre</Label>
            <Input
              id="search-name"
              placeholder="Buscar por nombre..."
              value={filters.name}
              onChange={(e) => handleFilterChange('name', e.target.value)}
              disabled={loading}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="status-filter">Estado</Label>
            <Select
              value={filters.status || 'all'}
              onValueChange={(value) => handleFilterChange('status', value === 'all' ? undefined : value)}
              disabled={loading}
            >
              <SelectTrigger id="status-filter">
                <SelectValue placeholder="Todos" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Todos</SelectItem>
                <SelectItem value="active">Activo</SelectItem>
                <SelectItem value="inactive">Inactivo</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="search-description">Descripción</Label>
            <Input
              id="search-description"
              placeholder="Buscar en descripción..."
              value={filters.description}
              onChange={(e) => handleFilterChange('description', e.target.value)}
              disabled={loading}
            />
          </div>
        </div>
        <div className="flex justify-end gap-2 mt-4">
          <Button onClick={handleSearch} disabled={loading} variant="default">
            <Search className="w-4 h-4 mr-2" />
            Buscar
          </Button>
          <Button variant="outline" onClick={handleClearFilters} disabled={loading}>
            Limpiar
          </Button>
        </div>
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      {loading && (
        <div className="text-center py-8">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
          <p className="mt-2 text-gray-600">Cargando roles...</p>
        </div>
      )}

      {!loading && (
        <Card>
          <CardContent className="p-0">
            {items.length === 0 ? (
              <div className="text-center py-8">
                <p className="text-gray-500 mb-4">No se encontraron roles.</p>
                <Button onClick={() => router.visit(roles.create(companyId).url)}>
                  <Plus className="w-4 h-4 mr-2" />
                  Crear Primer Rol
                </Button>
              </div>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Nombre</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead>Descripción</TableHead>
                    <TableHead>Fecha de Creación</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((role) => (
                    <TableRow key={role.id}>
                      <TableCell className="font-medium">{role.name}</TableCell>
                      <TableCell>{getStatusBadge(role.status)}</TableCell>
                      <TableCell className="max-w-xs">
                        <div className="truncate" title={role.description || ''}>
                          {role.description || '-'}
                        </div>
                      </TableCell>
                      <TableCell>{formatDate(role.created_at)}</TableCell>
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
                                router.visit(roles.show({ company: companyId, id: role.id }).url)
                              }
                            >
                              <Eye className="mr-2 h-4 w-4" />
                              Ver
                            </DropdownMenuItem>
                            {role.name !== 'Administrador' && (
                              <>
                                <DropdownMenuItem
                                  onClick={() =>
                                    router.visit(roles.edit({ company: companyId, id: role.id }).url)
                                  }
                                >
                                  <Edit className="mr-2 h-4 w-4" />
                                  Editar
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem onClick={() => handleToggleStatus(role)}>
                                  <Power className="mr-2 h-4 w-4" />
                                  {role.status === 'active' ? 'Inactivar' : 'Activar'}
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
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
};
