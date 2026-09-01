import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import dispatchRoutes from '@/routes/dispatches';
import type { BreadcrumbItem } from '@/types';
import { DispatchForm } from './components/DispatchForm';
import { DispatchFormProvider } from './contexts/DispatchFormContext';
import { useDispatchForm } from './hooks/useDispatchForm';
import type { Dispatch, DispatchOptions } from './types/Dispatch';

interface Props {
    dispatch: Dispatch;
    options: DispatchOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function DispatchesEdit({ dispatch, options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Despachos',
            href: dispatchRoutes.index(companyId).url,
        },
        {
            title: dispatch.code,
            href: dispatchRoutes.show({
                company: companyId,
                id: dispatch.id,
            }).url,
        },
        {
            title: 'Editar',
            href: dispatchRoutes.edit({
                company: companyId,
                id: dispatch.id,
            }).url,
        },
    ];

    const formMethods = useDispatchForm({
        mode: 'edit',
        options,
        initialData: dispatch,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${dispatch.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        dispatchRoutes.show({
                            company: companyId,
                            id: dispatch.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {dispatch.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar despacho
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo se edita mientras el despacho está en borrador
                    </p>
                </div>
                <DispatchFormProvider value={{ ...formMethods, options }}>
                    <DispatchForm />
                </DispatchFormProvider>
            </div>
        </AppLayout>
    );
}
