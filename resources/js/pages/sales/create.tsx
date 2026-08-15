import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import sales from '@/routes/sales';
import type { BreadcrumbItem } from '@/types';
import { SaleWizard } from './components/SaleWizard';
import { SaleFormProvider } from './contexts/SaleFormContext';
import { useSaleForm } from './hooks/useSaleForm';
import type { AvailableProfile, ClientOption, PlanOption } from './types/Sale';

interface Props {
    clients: ClientOption[];
    plans: PlanOption[];
    availableProfiles: AvailableProfile[];
    preselectedClientId?: string | null;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SalesCreate({
    clients,
    plans,
    availableProfiles,
    preselectedClientId,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const formState = useSaleForm(
        companyId,
        plans,
        availableProfiles,
        preselectedClientId ?? '',
    );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Ventas', href: sales.index(companyId).url },
        { title: 'Nueva venta', href: sales.create(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva venta" />
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={sales.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Ventas
                </Link>

                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva venta
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Cliente, plan y perfiles en tres pasos.
                    </p>
                </div>

                <SaleFormProvider
                    value={{
                        ...formState,
                        companyId,
                        clients,
                        plans,
                        availableProfiles,
                    }}
                >
                    <SaleWizard />
                </SaleFormProvider>
            </div>
        </AppLayout>
    );
}
