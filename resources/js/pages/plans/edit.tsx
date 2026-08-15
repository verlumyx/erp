import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import plans from '@/routes/plans';
import type { BreadcrumbItem } from '@/types';
import { PlanForm } from './components/PlanForm';
import { PlanFormProvider } from './contexts/PlanFormContext';
import { usePlanForm } from './hooks/usePlanForm';
import type { Plan, PlanServiceOption } from './types/Plan';

interface Props {
    plan: Plan;
    services: PlanServiceOption[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PlansEdit({ plan, services }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Planes', href: plans.index(companyId).url },
        {
            title: plan.name,
            href: plans.show({ company: companyId, id: plan.id }).url,
        },
        {
            title: 'Editar',
            href: plans.edit({ company: companyId, id: plan.id }).url,
        },
    ];

    const formMethods = usePlanForm({
        mode: 'edit',
        services,
        initialData: plan,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${plan.name}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={plans.show({ company: companyId, id: plan.id }).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {plan.name}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar plan
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica los datos del plan del catálogo
                    </p>
                </div>
                <PlanFormProvider value={formMethods}>
                    <PlanForm />
                </PlanFormProvider>
            </div>
        </AppLayout>
    );
}
