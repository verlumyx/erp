import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import purchaseReturns from '@/routes/purchase-returns';
import type { BreadcrumbItem } from '@/types';
import { PurchaseReturnForm } from './components/PurchaseReturnForm';
import { PurchaseReturnFormProvider } from './contexts/PurchaseReturnFormContext';
import { usePurchaseReturnForm } from './hooks/usePurchaseReturnForm';
import type {
    PurchaseReturn,
    PurchaseReturnOptions,
} from './types/PurchaseReturn';

interface Props {
    purchaseReturn: PurchaseReturn;
    options: PurchaseReturnOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PurchaseReturnsEdit({
    purchaseReturn,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Devoluciones de compras',
            href: purchaseReturns.index(companyId).url,
        },
        {
            title: purchaseReturn.code,
            href: purchaseReturns.show({
                company: companyId,
                id: purchaseReturn.id,
            }).url,
        },
        {
            title: 'Editar',
            href: purchaseReturns.edit({
                company: companyId,
                id: purchaseReturn.id,
            }).url,
        },
    ];

    const formMethods = usePurchaseReturnForm({
        mode: 'edit',
        options,
        initialData: purchaseReturn,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${purchaseReturn.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        purchaseReturns.show({
                            company: companyId,
                            id: purchaseReturn.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {purchaseReturn.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar devolución
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo se edita mientras la devolución está en borrador
                    </p>
                </div>
                <PurchaseReturnFormProvider value={{ ...formMethods, options }}>
                    <PurchaseReturnForm />
                </PurchaseReturnFormProvider>
            </div>
        </AppLayout>
    );
}
