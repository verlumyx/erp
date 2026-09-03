import { Head, router } from '@inertiajs/react';
import { ArrowLeft, FileQuestion, Home, List } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface Props {
    message?: string;
    /**
     * El listado del módulo del que venía la dirección, cuando se pudo
     * deducir. Es la salida más útil: casi siempre el registro que se buscaba
     * está ahí al lado.
     */
    listUrl?: string | null;
}

export default function Error404({
    message = 'El registro que buscas no existe o fue movido.',
    listUrl = null,
}: Props) {
    /** Sin historial —una dirección tecleada o un enlace de fuera— no hay atrás. */
    const canGoBack =
        typeof window !== 'undefined' && window.history.length > 1;

    return (
        <>
            <Head title="404 - No encontrado" />

            <div className="flex min-h-screen items-center justify-center bg-background p-4">
                <Card className="w-full max-w-md">
                    <CardHeader className="text-center">
                        <div className="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-muted">
                            <FileQuestion className="h-10 w-10 text-muted-foreground" />
                        </div>
                        <CardTitle className="text-3xl font-bold">
                            404
                        </CardTitle>
                        <CardDescription className="text-lg">
                            No encontramos ese registro
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="text-center">
                        <p className="text-muted-foreground">{message}</p>
                        <p className="mt-4 text-sm text-muted-foreground">
                            Puede que lo hayan anulado, que pertenezca a otra
                            empresa o que el enlace esté mal copiado.
                        </p>
                    </CardContent>

                    <CardFooter className="flex flex-col gap-2 sm:flex-row sm:justify-center">
                        {canGoBack && (
                            <Button
                                variant="outline"
                                onClick={() => window.history.back()}
                                className="w-full sm:w-auto"
                            >
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver atrás
                            </Button>
                        )}
                        <Button
                            variant="outline"
                            onClick={() => router.visit('/dashboard')}
                            className="w-full sm:w-auto"
                        >
                            <Home className="mr-2 h-4 w-4" />
                            Ir al Dashboard
                        </Button>
                        {listUrl && (
                            <Button
                                onClick={() => router.visit(listUrl)}
                                className="w-full sm:w-auto"
                            >
                                <List className="mr-2 h-4 w-4" />
                                Ver el listado
                            </Button>
                        )}
                    </CardFooter>
                </Card>
            </div>
        </>
    );
}
