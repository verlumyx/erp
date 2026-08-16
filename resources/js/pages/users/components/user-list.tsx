import React, { useState } from 'react';
import { Eye, Edit, Search, Plus, Mail, MailCheck, ChevronDown, Power } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { router, usePage } from '@inertiajs/react';
import users from '@/routes/users';

interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    status: 'active' | 'inactive';
    created_at: string;
    role?: { id: string; name: string } | null;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

interface UserListProps {
    users: User[];
    loading: boolean;
    error: string | null;
    onCreate: () => void;
    onSearch: (filters: { name?: string; email?: string; email_verified?: boolean }) => void;
}

const EMAIL_VERIFIED_OPTIONS: OptionType[] = [
    { value: 'all', label: 'Todos' },
    { value: 'verified', label: 'Verificado' },
    { value: 'unverified', label: 'No Verificado' },
];

export const UserList: React.FC<UserListProps> = ({
    users: items,
    loading,
    error,
    onCreate,
    onSearch,
}) => {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [searchName, setSearchName] = useState('');
    const [searchEmail, setSearchEmail] = useState('');
    const [emailVerifiedFilter, setEmailVerifiedFilter] = useState<string>('all');

    const handleSearch = () => {
        const filters: { name?: string; email?: string; email_verified?: boolean } = {};

        if (searchName.trim()) filters.name = searchName.trim();
        if (searchEmail.trim()) filters.email = searchEmail.trim();
        if (emailVerifiedFilter !== 'all') {
            filters.email_verified = emailVerifiedFilter === 'verified';
        }

        onSearch(filters);
    };

    const handleClearSearch = () => {
        setSearchName('');
        setSearchEmail('');
        setEmailVerifiedFilter('all');
        onSearch({});
    };

    const handleToggleStatus = (user: User) => {
        router.put(users.updateStatus({ company: companyId, id: user.id }).url, {
            status: user.status === 'active' ? 'inactive' : 'active',
        });
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    if (error) {
        return (
            <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                <p className="text-red-800">Error: {error}</p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Usuarios</h1>
                    <p className="text-gray-600">Gestiona los usuarios del sistema</p>
                </div>
                <Button onClick={onCreate}>
                    <Plus className="w-4 h-4 mr-2" />
                    Nuevo Usuario
                </Button>
            </div>

            <div className="bg-white rounded-lg border border-gray-200 p-4">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="search-name">Nombre</Label>
                        <Input
                            id="search-name"
                            type="text"
                            value={searchName}
                            onChange={(e) => setSearchName(e.target.value)}
                            placeholder="Buscar por nombre..."
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="search-email">Email</Label>
                        <Input
                            id="search-email"
                            type="text"
                            value={searchEmail}
                            onChange={(e) => setSearchEmail(e.target.value)}
                            placeholder="Buscar por email..."
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="email-verified-filter">Estado Email</Label>
                        <Select2
                            inputId="email-verified-filter"
                            options={EMAIL_VERIFIED_OPTIONS}
                            value={EMAIL_VERIFIED_OPTIONS.find((option) => option.value === emailVerifiedFilter) ?? null}
                            onChange={(option) => setEmailVerifiedFilter(option?.value ?? 'all')}
                            placeholder="Todos"
                            isSearchable={false}
                        />
                    </div>
                </div>
                <div className="flex justify-end gap-2 mt-4">
                    <Button onClick={handleSearch} disabled={loading} variant="default">
                        <Search className="w-4 h-4 mr-2" />
                        Buscar
                    </Button>
                    <Button onClick={handleClearSearch} disabled={loading} variant="outline">
                        Limpiar
                    </Button>
                </div>
            </div>

            <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
                {loading ? (
                    <div className="p-8 text-center">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                        <p className="mt-2 text-muted-foreground">Cargando usuarios...</p>
                    </div>
                ) : items.length === 0 ? (
                    <div className="p-8 text-center">
                        <p className="text-muted-foreground">No se encontraron usuarios</p>
                    </div>
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Usuario</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Verificación</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Rol</TableHead>
                                <TableHead>Creado</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {items.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell>
                                        <div className="flex items-center">
                                            <div className="flex-shrink-0 h-10 w-10">
                                                <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                                                    <span className="text-sm font-medium text-primary">
                                                        {user.name.charAt(0).toUpperCase()}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="ml-4">
                                                <div className="text-sm font-medium">{user.name}</div>
                                                <div className="text-sm text-muted-foreground">
                                                    ID: {user.id.substring(0, 8)}...
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="text-sm">{user.email}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center">
                                            {user.email_verified_at ? (
                                                <>
                                                    <MailCheck className="w-4 h-4 text-green-500 mr-2" />
                                                    <Badge variant="default" className="bg-green-100 text-green-800">
                                                        Verificado
                                                    </Badge>
                                                </>
                                            ) : (
                                                <>
                                                    <Mail className="w-4 h-4 text-yellow-500 mr-2" />
                                                    <Badge variant="secondary" className="bg-yellow-100 text-yellow-800">
                                                        Pendiente
                                                    </Badge>
                                                </>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {user.status === 'active' ? (
                                            <Badge className="bg-green-100 text-green-800 hover:bg-green-100">
                                                Activo
                                            </Badge>
                                        ) : (
                                            <Badge variant="secondary" className="bg-red-100 text-red-800 hover:bg-red-100">
                                                Inactivo
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <div className="text-sm">
                                            {user.role ? (
                                                <Badge
                                                    variant="default"
                                                    className={
                                                        user.role.name === 'Administrador'
                                                            ? 'bg-purple-100 text-purple-800'
                                                            : 'bg-blue-100 text-blue-800'
                                                    }
                                                >
                                                    {user.role.name}
                                                </Badge>
                                            ) : (
                                                <Badge variant="secondary" className="bg-gray-100 text-gray-800">
                                                    Sin rol
                                                </Badge>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {formatDate(user.created_at)}
                                    </TableCell>
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
                                                            users.show({ company: companyId, id: user.id }).url,
                                                        )
                                                    }
                                                >
                                                    <Eye className="mr-2 h-4 w-4" />
                                                    Ver
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        router.visit(
                                                            users.edit({ company: companyId, id: user.id }).url,
                                                        )
                                                    }
                                                >
                                                    <Edit className="mr-2 h-4 w-4" />
                                                    Editar
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem onClick={() => handleToggleStatus(user)}>
                                                    <Power className="mr-2 h-4 w-4" />
                                                    {user.status === 'active' ? 'Inactivar' : 'Activar'}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </div>
        </div>
    );
};
