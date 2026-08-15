import React from 'react';
import { Head } from '@inertiajs/react';
import { UserForm } from './components/UserForm';
import { UserFormProvider } from './contexts/UserFormContext';
import { useUserForm } from './hooks/useUserForm';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    role?: {
        id: string;
        name: string;
    } | null;
    company?: {
        id: string;
        name: string;
    } | null;
}

interface Role {
    id: string;
    name: string;
    description: string;
}

interface Props {
    user: User;
    roles: Role[];
}

export default function UserEdit({ user, roles }: Props) {
    const formMethods = useUserForm({
        mode: 'edit',
        initialData: user,
        onSuccess: () => {
            // Opcional: mostrar notificación de éxito
        }
    });

    return (
        <AppLayout>
            <Head title={`Editar ${user.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="space-y-6">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                Editar Usuario
                            </h1>
                            <p className="text-muted-foreground">
                                Modifica la información del usuario
                            </p>
                        </div>

                        <Card>
                            <CardHeader>
                                <CardTitle>Información del Usuario</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <UserFormProvider value={formMethods}>
                                    <UserForm roles={roles} />
                                </UserFormProvider>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
