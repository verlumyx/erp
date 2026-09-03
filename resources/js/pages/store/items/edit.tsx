import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import storeItems from '@/routes/store-items';
import type { BreadcrumbItem } from '@/types';
import { StoreItemForm } from '../components/StoreItemForm';
import { StoreItemGallery } from '../components/StoreItemGallery';
import { StoreItemFormProvider } from '../contexts/StoreItemFormContext';
import { useStoreItemForm } from '../hooks/useStoreItemForm';
import { useStorePermissions } from '../hooks/useStorePermissions';
import type { StoreItem } from '../types/Store';

interface Props {
    store_item: StoreItem;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function StoreItemsEdit({ store_item }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const { can } = useStorePermissions();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Publicaciones', href: storeItems.index(companyId).url },
        {
            title: store_item.code,
            href: storeItems.show({ company: companyId, id: store_item.id })
                .url,
        },
        {
            title: 'Editar',
            href: storeItems.edit({ company: companyId, id: store_item.id })
                .url,
        },
    ];

    const formMethods = useStoreItemForm({
        mode: 'edit',
        initialData: store_item,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${store_item.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        storeItems.show({
                            company: companyId,
                            id: store_item.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {store_item.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar publicación
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Textos, orden y galería de {store_item.title}
                    </p>
                </div>
                <StoreItemFormProvider value={formMethods}>
                    <StoreItemForm />
                </StoreItemFormProvider>
                <StoreItemGallery
                    storeItem={store_item}
                    canEdit={can('store-items.edit')}
                />
            </div>
        </AppLayout>
    );
}
