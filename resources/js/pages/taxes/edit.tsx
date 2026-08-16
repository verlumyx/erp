import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import taxRoutes from '@/routes/taxes';
import type { BreadcrumbItem } from '@/types';
import { TaxForm } from './components/TaxForm';
import { TaxFormProvider } from './contexts/TaxFormContext';
import { useTaxForm } from './hooks/useTaxForm';
import type { Tax } from './types/Tax';

interface Props {
    tax: Tax;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function TaxesEdit({ tax }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Impuestos',
            href: taxRoutes.index(companyId).url,
        },
        {
            title: tax.name,
            href: taxRoutes.show({ company: companyId, id: tax.id }).url,
        },
        {
            title: 'Editar',
            href: taxRoutes.edit({ company: companyId, id: tax.id }).url,
        },
    ];

    const formMethods = useTaxForm({ mode: 'edit', initialData: tax });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${tax.name}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        taxRoutes.show({ company: companyId, id: tax.id }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {tax.name}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar impuesto
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica el porcentaje, la retención o la descripción
                    </p>
                </div>
                <TaxFormProvider value={formMethods}>
                    <TaxForm />
                </TaxFormProvider>
            </div>
        </AppLayout>
    );
}
