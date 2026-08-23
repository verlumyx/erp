import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import itemSerials from '@/routes/item-serials';
import type { BreadcrumbItem } from '@/types';
import { ItemSerialForm } from './components/ItemSerialForm';
import { ItemSerialFormProvider } from './contexts/ItemSerialFormContext';
import { useItemSerialForm } from './hooks/useItemSerialForm';
import type { ItemSerial, WarehouseOption } from './types/ItemSerial';

interface Props {
    serial: ItemSerial;
    warehouses: WarehouseOption[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ItemSerialsEdit({ serial, warehouses }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Series', href: itemSerials.index(companyId).url },
        {
            title: serial.serial_number,
            href: itemSerials.show({ company: companyId, id: serial.id }).url,
        },
        {
            title: 'Editar',
            href: itemSerials.edit({ company: companyId, id: serial.id }).url,
        },
    ];

    const formMethods = useItemSerialForm({
        warehouses,
        initialData: serial,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${serial.serial_number}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        itemSerials.show({ company: companyId, id: serial.id })
                            .url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {serial.serial_number}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar serie
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Corrige los datos de la serie. El alta ocurre al recibir
                        la mercancía, no aquí
                    </p>
                </div>
                <ItemSerialFormProvider value={formMethods}>
                    <ItemSerialForm />
                </ItemSerialFormProvider>
            </div>
        </AppLayout>
    );
}
