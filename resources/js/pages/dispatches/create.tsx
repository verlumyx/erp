import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import dispatchRoutes from '@/routes/dispatches';
import type { BreadcrumbItem } from '@/types';
import { DispatchForm } from './components/DispatchForm';
import { DispatchFormProvider } from './contexts/DispatchFormContext';
import { useDispatchForm } from './hooks/useDispatchForm';
import type { DispatchOptions } from './types/Dispatch';

interface Props {
    options: DispatchOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function DispatchesCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Despachos',
            href: dispatchRoutes.index(companyId).url,
        },
        {
            title: 'Nuevo despacho',
            href: dispatchRoutes.create(companyId).url,
        },
    ];

    const formMethods = useDispatchForm({ mode: 'create', options });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo despacho" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={dispatchRoutes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Despachos
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo despacho
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Arma la carga que sale hacia el cliente y quién la lleva
                    </p>
                </div>
                <DispatchFormProvider value={{ ...formMethods, options }}>
                    <DispatchForm />
                </DispatchFormProvider>
            </div>
        </AppLayout>
    );
}
