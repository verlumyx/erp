import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Edit, Building2, Calendar, Clock, FileText } from 'lucide-react';
import { Company } from './types/Company';
import type { BreadcrumbItem } from '@/types';
import companies from '@/routes/companies';

interface Props {
    company: Company;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function CompaniesShow({ company }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({ status: company.status === 'active' ? 'inactive' : 'active' });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Empresas', href: companies.index(companyId).url },
        { title: company.name, href: companies.show({ company: companyId, id: company.id }).url },
    ];

    const formatDate = (date: string) =>
        new Date(date).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });

    const handleToggleStatus = () => {
        put(companies.updateStatus({ company: companyId, id: company.id }).url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={company.name} />
            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <Link href={companies.index(companyId).url} className="text-muted-foreground hover:text-foreground transition-colors">
                                <ArrowLeft className="w-5 h-5" />
                            </Link>
                            <div>
                                <h1 className="text-2xl font-bold">{company.name}</h1>
                                <p className="text-muted-foreground text-sm">Detalles de la empresa</p>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleToggleStatus}
                                disabled={processing}
                            >
                                {company.status === 'active' ? 'Desactivar' : 'Activar'}
                            </Button>
                            <Link href={companies.edit({ company: companyId, id: company.id }).url}>
                                <Button size="sm">
                                    <Edit className="w-4 h-4 mr-2" />
                                    Editar
                                </Button>
                            </Link>
                        </div>
                    </div>

                    {/* Detail card */}
                    <Card>
                        <CardHeader className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-t-lg">
                            <div className="flex items-center gap-4">
                                <div className="h-16 w-16 rounded-full bg-white/20 flex items-center justify-center">
                                    <Building2 className="w-8 h-8 text-white" />
                                </div>
                                <div>
                                    <CardTitle className="text-white text-xl">{company.name}</CardTitle>
                                    <div className="mt-1">
                                        {company.status === 'active' ? (
                                            <Badge className="bg-green-400/20 text-green-100 border-green-400/30">Activo</Badge>
                                        ) : (
                                            <Badge className="bg-red-400/20 text-red-100 border-red-400/30">Inactivo</Badge>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-4">
                                    <h3 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide border-b pb-2">
                                        Información
                                    </h3>
                                    <div>
                                        <p className="text-xs font-medium text-muted-foreground">Nombre</p>
                                        <p className="mt-1 text-sm font-medium">{company.name}</p>
                                    </div>
                                    {company.description && (
                                        <div>
                                            <p className="text-xs font-medium text-muted-foreground flex items-center gap-1">
                                                <FileText className="w-3 h-3" /> Descripción
                                            </p>
                                            <p className="mt-1 text-sm text-muted-foreground whitespace-pre-wrap">{company.description}</p>
                                        </div>
                                    )}
                                </div>
                                <div className="space-y-4">
                                    <h3 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide border-b pb-2">
                                        Auditoría
                                    </h3>
                                    <div>
                                        <p className="text-xs font-medium text-muted-foreground flex items-center gap-1">
                                            <Calendar className="w-3 h-3" /> Fecha de Creación
                                        </p>
                                        <p className="mt-1 text-sm">{formatDate(company.created_at)}</p>
                                    </div>
                                    {company.updated_at && (
                                        <div>
                                            <p className="text-xs font-medium text-muted-foreground flex items-center gap-1">
                                                <Clock className="w-3 h-3" /> Última Actualización
                                            </p>
                                            <p className="mt-1 text-sm">{formatDate(company.updated_at)}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
