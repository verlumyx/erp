import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import plans from '@/routes/plans';
import type { BreadcrumbItem } from '@/types';
import { PlanForm } from './components/PlanForm';
import { PlanFormProvider } from './contexts/PlanFormContext';
import { usePlanForm } from './hooks/usePlanForm';
import type { PlanServiceOption } from './types/Plan';

interface Props {
    services: PlanServiceOption[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PlansCreate({ services }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Planes', href: plans.index(companyId).url },
        { title: 'Nuevo plan', href: plans.create(companyId).url },
    ];

    const formMethods = usePlanForm({ mode: 'create', services });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo plan" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={plans.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Planes
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo plan
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Arma un plan vendible sobre un servicio del catálogo
                    </p>
                </div>
                <PlanFormProvider value={formMethods}>
                    <PlanForm />
                </PlanFormProvider>
            </div>
        </AppLayout>
    );
}
