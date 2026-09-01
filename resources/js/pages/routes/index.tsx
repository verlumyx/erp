import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import routeRoutes from '@/routes/routes';
import type { BreadcrumbItem } from '@/types';
import { RouteList } from './components/RouteList';
import type {
    Route,
    RouteFilters,
    RouteMeta,
    RouteOptions,
} from './types/Route';

interface Props {
    routes: Route[];
    meta: RouteMeta;
    filters: RouteFilters;
    options: RouteOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function RoutesIndex({
    routes: rows,
    meta,
    filters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Rutas',
            href: routeRoutes.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rutas" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <RouteList
                    routes={rows}
                    meta={meta}
                    filters={filters}
                    options={options}
                />
            </div>
        </AppLayout>
    );
}
