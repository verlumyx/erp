import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import company from '@/routes/company';
import { type BreadcrumbItem } from '@/types';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const MESES_LARGOS = [
    'enero',
    'febrero',
    'marzo',
    'abril',
    'mayo',
    'junio',
    'julio',
    'agosto',
    'septiembre',
    'octubre',
    'noviembre',
    'diciembre',
];

export default function Dashboard() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany?.id;

    const breadcrumbs: BreadcrumbItem[] = companyId
        ? [{ title: 'Resumen', href: company.dashboard(companyId).url }]
        : [];

    const hoy = new Date();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Resumen" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Resumen
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {MESES_LARGOS[hoy.getMonth()]} {hoy.getFullYear()}
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
