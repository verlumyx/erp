import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { ShieldAlert, ArrowLeft, Home } from 'lucide-react';

interface Props {
    message?: string;
}

export default function Error403({ message = 'No tienes permiso para acceder a este recurso.' }: Props) {
    const handleGoBack = () => {
        window.history.back();
    };

    const handleGoHome = () => {
        router.visit('/dashboard');
    };

    return (
        <>
            <Head title="403 - Acceso Denegado" />

            <div className="min-h-screen flex items-center justify-center bg-background p-4">
                <Card className="w-full max-w-md">
                    <CardHeader className="text-center">
                        <div className="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-destructive/10">
                            <ShieldAlert className="h-10 w-10 text-destructive" />
                        </div>
                        <CardTitle className="text-3xl font-bold">403</CardTitle>
                        <CardDescription className="text-lg">
                            Acceso Denegado
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="text-center">
                        <p className="text-muted-foreground">
                            {message}
                        </p>
                        <p className="mt-4 text-sm text-muted-foreground">
                            Si crees que esto es un error, contacta al administrador del sistema.
                        </p>
                    </CardContent>

                    <CardFooter className="flex flex-col gap-2 sm:flex-row sm:justify-center">
                        <Button
                            variant="outline"
                            onClick={handleGoBack}
                            className="w-full sm:w-auto"
                        >
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver Atrás
                        </Button>
                        <Button
                            onClick={handleGoHome}
                            className="w-full sm:w-auto"
                        >
                            <Home className="mr-2 h-4 w-4" />
                            Ir al Dashboard
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </>
    );
}

