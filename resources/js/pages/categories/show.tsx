import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowUpDown,
    Edit,
    Hash,
    Power,
    StickyNote,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import categories from '@/routes/categories';
import type { BreadcrumbItem } from '@/types';
import type { Category } from './types/Category';

interface Props {
    category: Category;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function CategoriesShow({ category }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({
        status: category.status === 'active' ? 'inactive' : 'active',
    });

    const estadoCategoria =
        category.status === 'inactive' ? 'inactivo' : 'activo';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Categorías', href: categories.index(companyId).url },
        {
            title: category.name,
            href: categories.show({ company: companyId, id: category.id }).url,
        },
    ];

    const handleToggleStatus = () => {
        put(
            categories.updateStatus({ company: companyId, id: category.id })
                .url,
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={category.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={categories.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Categorías
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {category.name}
                            </h1>
                            <StatusPill kind={estadoCategoria} />
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {category.code}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <ArrowUpDown className="size-3.5 opacity-80" />
                                Orden {category.order}
                            </span>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        <Button
                            variant="outline"
                            className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            onClick={handleToggleStatus}
                            disabled={processing}
                        >
                            <Power />
                            {category.status === 'active'
                                ? 'Desactivar'
                                : 'Activar'}
                        </Button>
                        <Link
                            href={
                                categories.edit({
                                    company: companyId,
                                    id: category.id,
                                }).url
                            }
                        >
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            >
                                <Edit />
                                Editar
                            </Button>
                        </Link>
                    </div>
                </Card>

                {category.description && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Descripción
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {category.description}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
