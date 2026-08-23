import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import itemLots from '@/routes/item-lots';
import type { BreadcrumbItem } from '@/types';
import { ItemLotForm } from './components/ItemLotForm';
import { ItemLotFormProvider } from './contexts/ItemLotFormContext';
import { useItemLotForm } from './hooks/useItemLotForm';
import type { ItemLot } from './types/ItemLot';

interface Props {
    lot: ItemLot;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ItemLotsEdit({ lot }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Lotes', href: itemLots.index(companyId).url },
        {
            title: lot.lot_number,
            href: itemLots.show({ company: companyId, id: lot.id }).url,
        },
        {
            title: 'Editar',
            href: itemLots.edit({ company: companyId, id: lot.id }).url,
        },
    ];

    const formMethods = useItemLotForm({ initialData: lot });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${lot.lot_number}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={itemLots.show({ company: companyId, id: lot.id }).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {lot.lot_number}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar lote
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Corrige los datos del lote. El alta ocurre al recibir la
                        mercancía, no aquí
                    </p>
                </div>
                <ItemLotFormProvider value={formMethods}>
                    <ItemLotForm />
                </ItemLotFormProvider>
            </div>
        </AppLayout>
    );
}
