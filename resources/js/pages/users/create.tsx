import React from 'react';
import { Head } from '@inertiajs/react';
import { UserForm } from './components/UserForm';
import { UserFormProvider } from './contexts/UserFormContext';
import { useUserForm } from './hooks/useUserForm';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

interface Role {
    id: string;
    name: string;
    description: string;
}

interface Props {
    roles: Role[];
}

export default function UserCreate({ roles }: Props) {
    const formMethods = useUserForm({
        mode: 'create',
        onSuccess: () => {
            // Opcional: mostrar notificación de éxito
        }
    });

    return (
        <AppLayout>
            <Head title="Crear Usuario" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="space-y-6">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Crear Usuario</h1>
                            <p className="text-muted-foreground">
                                Completa la información para crear un nuevo usuario
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
