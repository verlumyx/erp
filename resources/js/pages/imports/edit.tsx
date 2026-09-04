import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import imports from '@/routes/imports';
import type { BreadcrumbItem } from '@/types';
import { ImportForm } from './components/ImportForm';
import { ImportFormProvider } from './contexts/ImportFormContext';
import { useImportForm } from './hooks/useImportForm';
import type { Import, ImportOptions } from './types/Import';

interface Props {
    import: Import;
    options: ImportOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ImportsEdit({ import: model, options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Importaciones', href: imports.index(companyId).url },
        {
            title: model.code,
            href: imports.show({ company: companyId, id: model.id }).url,
        },
        {
            title: 'Editar',
            href: imports.edit({ company: companyId, id: model.id }).url,
        },
    ];

    const formMethods = useImportForm({
        mode: 'edit',
        options,
        initialData: model,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${model.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        imports.show({ company: companyId, id: model.id }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {model.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar expediente
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo se edita mientras el expediente está en borrador
                    </p>
                </div>
                <ImportFormProvider value={formMethods}>
                    <ImportForm />
                </ImportFormProvider>
            </div>
        </AppLayout>
    );
}
