import React from 'react';
import { Head } from '@inertiajs/react';
import { RoleForm } from './components/role-form';
import { PermissionsSection } from './components/permissions-section';
import { RoleFormProvider } from './contexts/RoleFormContext';
import { useRoleForm } from './hooks/useRoleForm';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

interface Role {
    id: string;
    name: string;
    status: 'active' | 'inactive';
    description: string;
    permission_type: 'all' | 'custom';
    permissions?: string[];
    created_at: string;
    updated_at: string | null;
}

interface Props  {
    role: Role;
}

export default function RolesEdit({ role }: Props) {
    const formMethods = useRoleForm({
        mode: 'edit',
        initialData: role,
        onSuccess: () => {
            // Opcional: mostrar notificación de éxito
        }
    });

    return (
        <AppLayout>
            <Head title={`Editar ${role.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="space-y-6">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                Editar Rol
                            </h1>
                            <p className="text-muted-foreground">
                                Modifica la información del rol
                            </p>
                        </div>

                        <RoleFormProvider value={formMethods}>
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {/* Card de Información del Rol */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Información del Rol</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <RoleForm />
                                    </CardContent>
                                </Card>

                                {/* Card de Permisos */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Permisos</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <PermissionsSection />
                                    </CardContent>
                                </Card>
                            </div>
                        </RoleFormProvider>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

