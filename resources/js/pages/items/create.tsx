import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import items from '@/routes/items';
import type { BreadcrumbItem } from '@/types';
import { ItemForm } from './components/ItemForm';
import { ItemFormProvider } from './contexts/ItemFormContext';
import { useItemForm } from './hooks/useItemForm';
import type { ItemOptions } from './types/Item';

interface Props {
    options: ItemOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ItemsCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Catálogo de artículos', href: items.index(companyId).url },
        { title: 'Nuevo artículo', href: items.create(companyId).url },
    ];

    const formMethods = useItemForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo artículo" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={items.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Catálogo de artículos
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo artículo
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Define el artículo, sus unidades de medida y sus precios
                        por lista
                    </p>
                </div>
                <ItemFormProvider value={{ ...formMethods, options }}>
                    <ItemForm />
                </ItemFormProvider>
            </div>
        </AppLayout>
    );
}
