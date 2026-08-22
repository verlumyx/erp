import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import purchaseOrders from '@/routes/purchase-orders';
import type { BreadcrumbItem } from '@/types';
import { PurchaseOrderForm } from './components/PurchaseOrderForm';
import { PurchaseOrderFormProvider } from './contexts/PurchaseOrderFormContext';
import { usePurchaseOrderForm } from './hooks/usePurchaseOrderForm';
import type {
    PurchaseOrder,
    PurchaseOrderOptions,
} from './types/PurchaseOrder';

interface Props {
    purchaseOrder: PurchaseOrder;
    options: PurchaseOrderOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PurchaseOrdersEdit({ purchaseOrder, options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Órdenes de compra',
            href: purchaseOrders.index(companyId).url,
        },
        {
            title: purchaseOrder.code,
            href: purchaseOrders.show({
                company: companyId,
                id: purchaseOrder.id,
            }).url,
        },
        {
            title: 'Editar',
            href: purchaseOrders.edit({
                company: companyId,
                id: purchaseOrder.id,
            }).url,
        },
    ];

    const formMethods = usePurchaseOrderForm({
        mode: 'edit',
        options,
        initialData: purchaseOrder,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${purchaseOrder.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        purchaseOrders.show({
                            company: companyId,
                            id: purchaseOrder.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {purchaseOrder.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar orden de compra
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo se edita mientras la orden está en borrador
                    </p>
                </div>
                <PurchaseOrderFormProvider value={{ ...formMethods, options }}>
                    <PurchaseOrderForm />
                </PurchaseOrderFormProvider>
            </div>
        </AppLayout>
    );
}
