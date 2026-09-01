import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import routeRoutes from '@/routes/routes';
import type { BreadcrumbItem } from '@/types';
import { RouteForm } from './components/RouteForm';
import { RouteFormProvider } from './contexts/RouteFormContext';
import { useRouteForm } from './hooks/useRouteForm';
import type { Route, RouteOptions } from './types/Route';

interface Props {
    route: Route;
    options: RouteOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function RoutesEdit({ route, options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Rutas',
            href: routeRoutes.index(companyId).url,
        },
        {
            title: route.code,
            href: routeRoutes.show({ company: companyId, id: route.id }).url,
        },
        {
            title: 'Editar',
            href: routeRoutes.edit({ company: companyId, id: route.id }).url,
        },
    ];

    const formMethods = useRouteForm({
        mode: 'edit',
        options,
        initialData: route,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${route.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        routeRoutes.show({ company: companyId, id: route.id })
                            .url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {route.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar ruta
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Cambiar la plantilla no toca los días ya planificados
                    </p>
                </div>
                <RouteFormProvider value={formMethods}>
                    <RouteForm />
                </RouteFormProvider>
            </div>
        </AppLayout>
    );
}
