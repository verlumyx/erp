import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import categories from '@/routes/categories';
import type { BreadcrumbItem } from '@/types';
import { CategoryList } from './components/CategoryList';
import type { Category, CategoryFilters, CategoryMeta } from './types/Category';

interface Props {
    categories: Category[];
    meta: CategoryMeta;
    filters: CategoryFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function CategoriesIndex({
    categories: items,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Categorías', href: categories.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Categorías" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <CategoryList
                    categories={items}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
