import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import loadPlans from '@/routes/load-plans';
import type { BreadcrumbItem } from '@/types';
import { LoadPlanForm } from './components/LoadPlanForm';
import { LoadPlanFormProvider } from './contexts/LoadPlanFormContext';
import type { LoadPlan, LoadPlanOptions } from './types/LoadPlan';

interface Props {
    loadPlan: LoadPlan;
    options: LoadPlanOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function LoadPlansEdit({ loadPlan, options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Planes de Carga', href: loadPlans.index(companyId).url },
        {
            title: loadPlan.code,
            href: loadPlans.show({ company: companyId, id: loadPlan.id }).url,
        },
        {
            title: 'Editar',
            href: loadPlans.edit({ company: companyId, id: loadPlan.id }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${loadPlan.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        loadPlans.show({ company: companyId, id: loadPlan.id })
                            .url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Volver al detalle
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar {loadPlan.code}
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica los despachos o la tripulación asignada al plan de carga
                    </p>
                </div>

                <LoadPlanFormProvider
                    mode="edit"
                    initialData={loadPlan}
                    options={options}
                >
                    <LoadPlanForm />
                </LoadPlanFormProvider>
            </div>
        </AppLayout>
    );
}
