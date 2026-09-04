import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import imports from '@/routes/imports';
import type { BreadcrumbItem } from '@/types';
import { ImportForm } from './components/ImportForm';
import { ImportFormProvider } from './contexts/ImportFormContext';
import { useImportForm } from './hooks/useImportForm';
import type { ImportOptions } from './types/Import';

interface Props {
    options: ImportOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ImportsCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Importaciones', href: imports.index(companyId).url },
        { title: 'Nuevo expediente', href: imports.create(companyId).url },
    ];

    const formMethods = useImportForm({ mode: 'create', options });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo expediente de importación" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={imports.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Importaciones
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo expediente
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Reparte lo que costó traer la mercancía entre lo que de
                        verdad llegó
                    </p>
                </div>
                <ImportFormProvider value={formMethods}>
                    <ImportForm />
                </ImportFormProvider>
            </div>
        </AppLayout>
    );
}
