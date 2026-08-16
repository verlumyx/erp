import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import salesOrders from '@/routes/sales-orders';
import type { BreadcrumbItem } from '@/types';
import { SalesOrderForm } from './components/SalesOrderForm';
import { SalesOrderFormProvider } from './contexts/SalesOrderFormContext';
import { useSalesOrderForm } from './hooks/useSalesOrderForm';
import type { SalesOrder, SalesOrderOptions } from './types/SalesOrder';

interface Props {
    salesOrder: SalesOrder;
    options: SalesOrderOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SalesOrdersEdit({ salesOrder, options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Órdenes de venta', href: salesOrders.index(companyId).url },
        {
            title: salesOrder.code,
            href: salesOrders.show({ company: companyId, id: salesOrder.id })
                .url,
        },
        {
            title: 'Editar',
            href: salesOrders.edit({ company: companyId, id: salesOrder.id })
                .url,
        },
    ];

    const formMethods = useSalesOrderForm({
        mode: 'edit',
        options,
        initialData: salesOrder,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${salesOrder.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        salesOrders.show({
                            company: companyId,
                            id: salesOrder.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {salesOrder.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar orden de venta
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo un pedido en borrador se edita
                    </p>
                </div>
                <SalesOrderFormProvider value={{ ...formMethods, options }}>
                    <SalesOrderForm />
                </SalesOrderFormProvider>
            </div>
        </AppLayout>
    );
}
