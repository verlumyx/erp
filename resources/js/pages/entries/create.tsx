import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import entries from '@/routes/entries';
import type { BreadcrumbItem } from '@/types';
import { EntryForm } from './components/EntryForm';
import { EntryFormProvider } from './contexts/EntryFormContext';
import { useEntryForm } from './hooks/useEntryForm';
import type { EntryOptions } from './types/Entry';

interface Props {
    options: EntryOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function EntriesCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Entradas',
            href: entries.index(companyId).url,
        },
        {
            title: 'Nueva entrada',
            href: entries.create(companyId).url,
        },
    ];

    const formMethods = useEntryForm({ mode: 'create', options });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva entrada" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={entries.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Entradas
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva entrada
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra la mercancía que llega y a qué bodega entra
                    </p>
                </div>
                <EntryFormProvider value={{ ...formMethods, options }}>
                    <EntryForm />
                </EntryFormProvider>
            </div>
        </AppLayout>
    );
}
