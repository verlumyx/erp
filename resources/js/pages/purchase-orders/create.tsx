import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import purchaseOrders from '@/routes/purchase-orders';
import type { BreadcrumbItem } from '@/types';
import { PurchaseOrderForm } from './components/PurchaseOrderForm';
import { PurchaseOrderFormProvider } from './contexts/PurchaseOrderFormContext';
import { usePurchaseOrderForm } from './hooks/usePurchaseOrderForm';
import type { PurchaseOrderOptions } from './types/PurchaseOrder';

interface Props {
    options: PurchaseOrderOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PurchaseOrdersCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Órdenes de compra',
            href: purchaseOrders.index(companyId).url,
        },
        { title: 'Nueva orden', href: purchaseOrders.create(companyId).url },
    ];

    const formMethods = usePurchaseOrderForm({ mode: 'create', options });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva orden de compra" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={purchaseOrders.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Órdenes de compra
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva orden de compra
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Define el proveedor, la bodega de recepción y las líneas
                        que se le piden
                    </p>
                </div>
                <PurchaseOrderFormProvider value={{ ...formMethods, options }}>
                    <PurchaseOrderForm />
                </PurchaseOrderFormProvider>
            </div>
        </AppLayout>
    );
}
