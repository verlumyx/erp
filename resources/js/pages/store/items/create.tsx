import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import storeItems from '@/routes/store-items';
import type { BreadcrumbItem } from '@/types';
import { StoreItemForm } from '../components/StoreItemForm';
import { StoreItemFormProvider } from '../contexts/StoreItemFormContext';
import { useStoreItemForm } from '../hooks/useStoreItemForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function StoreItemsCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Publicaciones', href: storeItems.index(companyId).url },
        { title: 'Publicar artículo', href: storeItems.create(companyId).url },
    ];

    const formMethods = useStoreItemForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Publicar artículo" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={storeItems.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Publicaciones
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Publicar artículo
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Elige un artículo del catálogo y escribe cómo se muestra
                        en la tienda
                    </p>
                </div>
                <StoreItemFormProvider value={formMethods}>
                    <StoreItemForm />
                </StoreItemFormProvider>
            </div>
        </AppLayout>
    );
}
