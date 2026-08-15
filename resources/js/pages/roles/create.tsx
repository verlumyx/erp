import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import { RoleForm } from './components/role-form';
import { PermissionsSection } from './components/permissions-section';
import { RoleFormProvider } from './contexts/RoleFormContext';
import { useRoleForm } from './hooks/useRoleForm';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import roles from '@/routes/roles';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function RolesCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Roles',
            href: roles.index(companyId).url,
        },
        {
            title: 'Crear',
            href: roles.create(companyId).url,
        },
    ];

    const formMethods = useRoleForm({
        mode: 'create',
        onSuccess: () => {},
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear Rol" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="space-y-6">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Crear Rol</h1>
                            <p className="text-muted-foreground">
                                Completa la información para crear un nuevo rol
                            </p>
                        </div>

                        <RoleFormProvider value={formMethods}>
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Información del Rol</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <RoleForm />
                                    </CardContent>
                                </Card>

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
