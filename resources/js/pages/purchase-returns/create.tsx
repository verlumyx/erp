import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import purchaseReturns from '@/routes/purchase-returns';
import type { BreadcrumbItem } from '@/types';
import { PurchaseReturnForm } from './components/PurchaseReturnForm';
import { PurchaseReturnFormProvider } from './contexts/PurchaseReturnFormContext';
import { usePurchaseReturnForm } from './hooks/usePurchaseReturnForm';
import type { PurchaseReturnOptions } from './types/PurchaseReturn';

interface Props {
    options: PurchaseReturnOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PurchaseReturnsCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Devoluciones de compras',
            href: purchaseReturns.index(companyId).url,
        },
        {
            title: 'Nueva devolución',
            href: purchaseReturns.create(companyId).url,
        },
    ];

    const formMethods = usePurchaseReturnForm({ mode: 'create', options });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva devolución de compra" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={purchaseReturns.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Devoluciones de compras
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva devolución
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra la mercancía que vuelve al proveedor y de qué
                        bodega sale
                    </p>
                </div>
                <PurchaseReturnFormProvider value={{ ...formMethods, options }}>
                    <PurchaseReturnForm />
                </PurchaseReturnFormProvider>
            </div>
        </AppLayout>
    );
}
