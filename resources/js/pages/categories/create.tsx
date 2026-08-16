import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import categories from '@/routes/categories';
import type { BreadcrumbItem } from '@/types';
import { CategoryForm } from './components/CategoryForm';
import { CategoryFormProvider } from './contexts/CategoryFormContext';
import { useCategoryForm } from './hooks/useCategoryForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function CategoriesCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Categorías', href: categories.index(companyId).url },
        { title: 'Nueva categoría', href: categories.create(companyId).url },
    ];

    const formMethods = useCategoryForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva categoría" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={categories.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Categorías
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva categoría
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Clasifica tus artículos en un solo nivel: sin
                        subcategorías
                    </p>
                </div>
                <CategoryFormProvider value={formMethods}>
                    <CategoryForm />
                </CategoryFormProvider>
            </div>
        </AppLayout>
    );
}
