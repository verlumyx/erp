import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import adjustments from '@/routes/adjustments';
import type { BreadcrumbItem } from '@/types';
import { AdjustmentForm } from './components/AdjustmentForm';
import { AdjustmentFormProvider } from './contexts/AdjustmentFormContext';
import { useAdjustmentForm } from './hooks/useAdjustmentForm';
import type { Adjustment, AdjustmentOptions } from './types/Adjustment';

interface Props {
    adjustment: Adjustment;
    options: AdjustmentOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function AdjustmentsEdit({ adjustment, options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Ajustes',
            href: adjustments.index(companyId).url,
        },
        {
            title: adjustment.code,
            href: adjustments.show({ company: companyId, id: adjustment.id })
                .url,
        },
        {
            title: 'Editar',
            href: adjustments.edit({ company: companyId, id: adjustment.id })
                .url,
        },
    ];

    const formMethods = useAdjustmentForm({
        mode: 'edit',
        options,
        initialData: adjustment,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${adjustment.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        adjustments.show({
                            company: companyId,
                            id: adjustment.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {adjustment.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar ajuste
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo se edita mientras el ajuste está en borrador
                    </p>
                </div>
                <AdjustmentFormProvider value={{ ...formMethods, options }}>
                    <AdjustmentForm />
                </AdjustmentFormProvider>
            </div>
        </AppLayout>
    );
}
