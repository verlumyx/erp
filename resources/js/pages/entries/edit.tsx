import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import entries from '@/routes/entries';
import type { BreadcrumbItem } from '@/types';
import { EntryForm } from './components/EntryForm';
import { EntryFormProvider } from './contexts/EntryFormContext';
import { useEntryForm } from './hooks/useEntryForm';
import type { Entry, EntryOptions } from './types/Entry';

interface Props {
    entry: Entry;
    options: EntryOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function EntriesEdit({ entry, options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Entradas',
            href: entries.index(companyId).url,
        },
        {
            title: entry.code,
            href: entries.show({ company: companyId, id: entry.id }).url,
        },
        {
            title: 'Editar',
            href: entries.edit({ company: companyId, id: entry.id }).url,
        },
    ];

    const formMethods = useEntryForm({
        mode: 'edit',
        options,
        initialData: entry,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${entry.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        entries.show({ company: companyId, id: entry.id }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {entry.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar entrada
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo se edita mientras la entrada está en borrador
                    </p>
                </div>
                <EntryFormProvider value={{ ...formMethods, options }}>
                    <EntryForm />
                </EntryFormProvider>
            </div>
        </AppLayout>
    );
}
