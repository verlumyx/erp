import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CompanyForm } from './components/CompanyForm';
import { CompanyFormProvider } from './contexts/CompanyFormContext';
import { useCompanyForm } from './hooks/useCompanyForm';
import type { BreadcrumbItem } from '@/types';
import companies from '@/routes/companies';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function CompaniesCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Empresas', href: companies.index(companyId).url },
        { title: 'Crear', href: companies.create(companyId).url },
    ];

    const formMethods = useCompanyForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear Empresa" />
            <div className="py-6">
                <div className="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="space-y-6">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Crear Empresa</h1>
                            <p className="text-muted-foreground">Completa la información para crear una nueva empresa</p>
                        </div>
                        <Card>
                            <CardHeader>
                                <CardTitle>Información de la Empresa</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <CompanyFormProvider value={formMethods}>
                                    <CompanyForm />
                                </CompanyFormProvider>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
