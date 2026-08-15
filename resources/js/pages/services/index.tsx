import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import services from '@/routes/services';
import type { BreadcrumbItem } from '@/types';
import { ServiceList } from './components/ServiceList';
import type { Service, ServiceFilters, ServiceMeta } from './types/Service';

interface Props {
    services: Service[];
    meta: ServiceMeta;
    filters: ServiceFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ServicesIndex({
    services: items,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Catálogo', href: services.index(companyId).url },
        { title: 'Servicios', href: services.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Servicios" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ServiceList services={items} meta={meta} filters={filters} />
            </div>
        </AppLayout>
    );
}
