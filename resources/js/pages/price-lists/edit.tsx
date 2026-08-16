import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import priceLists from '@/routes/price-lists';
import type { BreadcrumbItem } from '@/types';
import { PriceListForm } from './components/PriceListForm';
import { PriceListFormProvider } from './contexts/PriceListFormContext';
import { usePriceListForm } from './hooks/usePriceListForm';
import type { PriceList } from './types/PriceList';

interface Props {
    priceList: PriceList;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PriceListsEdit({ priceList }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Listas de precio', href: priceLists.index(companyId).url },
        {
            title: priceList.name,
            href: priceLists.show({ company: companyId, id: priceList.id }).url,
        },
        {
            title: 'Editar',
            href: priceLists.edit({ company: companyId, id: priceList.id }).url,
        },
    ];

    const formMethods = usePriceListForm({
        mode: 'edit',
        initialData: priceList,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${priceList.name}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        priceLists.show({
                            company: companyId,
                            id: priceList.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {priceList.name}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar lista de precio
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica el nombre y la descripción de la lista
                    </p>
                </div>
                <PriceListFormProvider value={formMethods}>
                    <PriceListForm />
                </PriceListFormProvider>
            </div>
        </AppLayout>
    );
}
