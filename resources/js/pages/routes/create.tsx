import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import routeRoutes from '@/routes/routes';
import type { BreadcrumbItem } from '@/types';
import { RouteForm } from './components/RouteForm';
import { RouteFormProvider } from './contexts/RouteFormContext';
import { useRouteForm } from './hooks/useRouteForm';
import type { RouteOptions } from './types/Route';

interface Props {
    options: RouteOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function RoutesCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Rutas',
            href: routeRoutes.index(companyId).url,
        },
        {
            title: 'Nueva ruta',
            href: routeRoutes.create(companyId).url,
        },
    ];

    const formMethods = useRouteForm({ mode: 'create', options });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva ruta" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={routeRoutes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Rutas
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva ruta
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Agrupa clientes y ordena las visitas para entregar y
                        cobrar
                    </p>
                </div>
                <RouteFormProvider value={formMethods}>
                    <RouteForm />
                </RouteFormProvider>
            </div>
        </AppLayout>
    );
}
