import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import categories from '@/routes/categories';
import type { BreadcrumbItem } from '@/types';
import { CategoryForm } from './components/CategoryForm';
import { CategoryFormProvider } from './contexts/CategoryFormContext';
import { useCategoryForm } from './hooks/useCategoryForm';
import type { Category } from './types/Category';

interface Props {
    category: Category;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function CategoriesEdit({ category }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Categorías', href: categories.index(companyId).url },
        {
            title: category.name,
            href: categories.show({ company: companyId, id: category.id }).url,
        },
        {
            title: 'Editar',
            href: categories.edit({ company: companyId, id: category.id }).url,
        },
    ];

    const formMethods = useCategoryForm({
        mode: 'edit',
        initialData: category,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${category.name}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        categories.show({
                            company: companyId,
                            id: category.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {category.name}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar categoría
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica el nombre, la descripción y el orden de
                        presentación
                    </p>
                </div>
                <CategoryFormProvider value={formMethods}>
                    <CategoryForm />
                </CategoryFormProvider>
            </div>
        </AppLayout>
    );
}
