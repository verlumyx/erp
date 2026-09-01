import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import salesReturns from '@/routes/sales-returns';
import type { BreadcrumbItem } from '@/types';
import { SalesReturnForm } from './components/SalesReturnForm';
import { SalesReturnFormProvider } from './contexts/SalesReturnFormContext';
import { useSalesReturnForm } from './hooks/useSalesReturnForm';
import type { SalesReturnOptions } from './types/SalesReturn';

interface Props {
    options: SalesReturnOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SalesReturnsCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Devoluciones de ventas',
            href: salesReturns.index(companyId).url,
        },
        {
            title: 'Nueva devolución',
            href: salesReturns.create(companyId).url,
        },
    ];

    const formMethods = useSalesReturnForm({ mode: 'create', options });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva devolución de venta" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={salesReturns.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Devoluciones de ventas
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva devolución
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra la mercancía que devuelve el cliente y a qué
                        bodega entra
                    </p>
                </div>
                <SalesReturnFormProvider value={{ ...formMethods, options }}>
                    <SalesReturnForm />
                </SalesReturnFormProvider>
            </div>
        </AppLayout>
    );
}
