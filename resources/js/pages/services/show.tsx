import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Hash, ImageIcon, Power, Users } from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import services from '@/routes/services';
import type { BreadcrumbItem } from '@/types';
import { ServiceLogo } from './components/ServiceLogo';
import type { Service } from './types/Service';

interface Props {
    service: Service;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function MiniStat({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: string | number;
    icon: typeof Users;
}) {
    return (
        <Card className="gap-1 rounded-2xl px-[18px] py-4">
            <span className="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-muted-foreground">
                <Icon className="size-3.5" />
                {label}
            </span>
            <span className="text-[21px] font-extrabold tracking-tight tabular-nums">
                {value}
            </span>
        </Card>
    );
}

export default function ServicesShow({ service }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({ active: !service.active });

    const handleToggleStatus = () => {
        put(services.updateStatus({ company: companyId, id: service.id }).url);
    };

    const createdAt = new Date(service.created_at).toLocaleDateString('es-CL', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Servicios', href: services.index(companyId).url },
        {
            title: service.name,
            href: services.show({ company: companyId, id: service.id }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={service.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={services.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Servicios
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <ServiceLogo
                            name={service.name}
                            logoUrl={service.logo_url}
                            className="size-16 rounded-[16px]"
                            iconClassName="size-7"
                        />
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {service.name}
                                </h1>
                                <StatusPill
                                    kind={service.active ? 'activo' : 'inactivo'}
                                />
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {service.code}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Users className="size-3.5 opacity-80" />
                                    {service.max_profiles} perfil
                                    {service.max_profiles !== 1 ? 'es' : ''} máx.
                                </span>
                            </div>
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
                            {service.active ? 'Desactivar' : 'Activar'}
                        </Button>
                    </div>
                </Card>

                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <MiniStat
                        label="Máximo de perfiles"
                        value={service.max_profiles}
                        icon={Users}
                    />
                    <MiniStat label="Código" value={service.code} icon={Hash} />
                    <MiniStat
                        label="Creado"
                        value={createdAt}
                        icon={ImageIcon}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
