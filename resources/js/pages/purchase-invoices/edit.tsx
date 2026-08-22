import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import purchaseInvoices from '@/routes/purchase-invoices';
import type { BreadcrumbItem } from '@/types';
import { PurchaseInvoiceForm } from './components/PurchaseInvoiceForm';
import { PurchaseInvoiceFormProvider } from './contexts/PurchaseInvoiceFormContext';
import { usePurchaseInvoiceForm } from './hooks/usePurchaseInvoiceForm';
import type {
    PurchaseInvoice,
    PurchaseInvoiceOptions,
} from './types/PurchaseInvoice';

interface Props {
    purchaseInvoice: PurchaseInvoice;
    options: PurchaseInvoiceOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PurchaseInvoicesEdit({
    purchaseInvoice,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Facturas de compra',
            href: purchaseInvoices.index(companyId).url,
        },
        {
            title: purchaseInvoice.code,
            href: purchaseInvoices.show({
                company: companyId,
                id: purchaseInvoice.id,
            }).url,
        },
        {
            title: 'Editar',
            href: purchaseInvoices.edit({
                company: companyId,
                id: purchaseInvoice.id,
            }).url,
        },
    ];

    const formMethods = usePurchaseInvoiceForm({
        mode: 'edit',
        options,
        initialData: purchaseInvoice,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${purchaseInvoice.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        purchaseInvoices.show({
                            company: companyId,
                            id: purchaseInvoice.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {purchaseInvoice.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar factura de compra
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo se edita mientras la factura está en borrador
                    </p>
                </div>
                <PurchaseInvoiceFormProvider
                    value={{ ...formMethods, options }}
                >
                    <PurchaseInvoiceForm />
                </PurchaseInvoiceFormProvider>
            </div>
        </AppLayout>
    );
}
