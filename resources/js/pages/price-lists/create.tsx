import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import priceLists from '@/routes/price-lists';
import type { BreadcrumbItem } from '@/types';
import { PriceListForm } from './components/PriceListForm';
import { PriceListFormProvider } from './contexts/PriceListFormContext';
import { usePriceListForm } from './hooks/usePriceListForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PriceListsCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Listas de precio', href: priceLists.index(companyId).url },
        {
            title: 'Nueva lista de precio',
            href: priceLists.create(companyId).url,
        },
    ];

    const formMethods = usePriceListForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva lista de precio" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={priceLists.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Listas de precio
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva lista de precio
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Agrupa precios de venta; el precio de cada artículo se
                        configura dentro del artículo
                    </p>
                </div>
                <PriceListFormProvider value={formMethods}>
                    <PriceListForm />
                </PriceListFormProvider>
            </div>
        </AppLayout>
    );
}
