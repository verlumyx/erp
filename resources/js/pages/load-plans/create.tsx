import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import loadPlans from '@/routes/load-plans';
import type { BreadcrumbItem } from '@/types';
import { LoadPlanForm } from './components/LoadPlanForm';
import { LoadPlanFormProvider } from './contexts/LoadPlanFormContext';
import type { LoadPlanOptions } from './types/LoadPlan';

interface Props {
    options: LoadPlanOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function LoadPlansCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Planes de Carga', href: loadPlans.index(companyId).url },
        { title: 'Nuevo plan de carga', href: loadPlans.create(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Plan de Carga" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={loadPlans.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Planes de Carga
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo Plan de Carga
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Planifica la carga del camión consolidando los despachos pendientes por ruta
                    </p>
                </div>

                <LoadPlanFormProvider mode="create" options={options}>
                    <LoadPlanForm />
                </LoadPlanFormProvider>
            </div>
        </AppLayout>
    );
}
