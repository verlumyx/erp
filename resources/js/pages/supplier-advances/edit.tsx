import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import supplierAdvances from '@/routes/supplier-advances';
import type { BreadcrumbItem } from '@/types';
import { SupplierAdvanceForm } from './components/SupplierAdvanceForm';
import { SupplierAdvanceFormProvider } from './contexts/SupplierAdvanceFormContext';
import { useSupplierAdvanceForm } from './hooks/useSupplierAdvanceForm';
import type { SupplierAdvance } from './types/SupplierAdvance';

interface Props {
    supplierAdvance: SupplierAdvance;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SupplierAdvancesEdit({ supplierAdvance }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Anticipos a proveedor',
            href: supplierAdvances.index(companyId).url,
        },
        {
            title: supplierAdvance.code,
            href: supplierAdvances.show({
                company: companyId,
                id: supplierAdvance.id,
            }).url,
        },
        {
            title: 'Editar',
            href: supplierAdvances.edit({
                company: companyId,
                id: supplierAdvance.id,
            }).url,
        },
    ];

    const formMethods = useSupplierAdvanceForm({
        mode: 'edit',
        initialData: supplierAdvance,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${supplierAdvance.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        supplierAdvances.show({
                            company: companyId,
                            id: supplierAdvance.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {supplierAdvance.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar anticipo a proveedor
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo se edita mientras el anticipo está en borrador
                    </p>
                </div>
                <SupplierAdvanceFormProvider value={formMethods}>
                    <SupplierAdvanceForm />
                </SupplierAdvanceFormProvider>
            </div>
        </AppLayout>
    );
}
