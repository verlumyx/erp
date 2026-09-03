import { Head, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CompanyMenusTree } from './components/CompanyMenusTree';
import type { Company, CompanyMenus } from './types/Company';
import type { BreadcrumbItem } from '@/types';
import companies from '@/routes/companies';

interface Props {
    company: Company;
    menus: CompanyMenus;
    disabled_menus: string[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

interface CompanyMenusFormData {
    disabled_menus: string[];
}

/**
 * Qué menús ve la empresa. Se guarda la lista de los deshabilitados: sin
 * nada desmarcado la empresa ve todo lo que su rol permite.
 */
export default function CompaniesMenus({ company, menus, disabled_menus }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, put, processing, errors } = useForm<CompanyMenusFormData>({
        disabled_menus,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Empresas', href: companies.index(companyId).url },
        { title: company.name, href: companies.show({ company: companyId, id: company.id }).url },
        { title: 'Menús', href: companies.menus.edit({ company: companyId, id: company.id }).url },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(companies.menus.update({ company: companyId, id: company.id }).url);
    };

    const errorMessage = Object.values(errors)[0];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Menús de ${company.name}`} />
            <div className="py-6">
                <div className="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Menús de la empresa</h1>
                            <p className="text-muted-foreground">
                                Desmarca las opciones que {company.name} no debe ver. Lo demás sigue dependiendo de los permisos de cada rol.
                            </p>
                        </div>

                        <Card>
                            <CardHeader>
                                <CardTitle>Menú principal</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <CompanyMenusTree
                                    nodes={menus.mainNavItems}
                                    disabledIds={data.disabled_menus}
                                    onChange={(ids) => setData('disabled_menus', ids)}
                                    disabled={processing}
                                />
                            </CardContent>
                        </Card>

                        {menus.footerNavItems.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Pie de página</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <CompanyMenusTree
                                        nodes={menus.footerNavItems}
                                        disabledIds={data.disabled_menus}
                                        onChange={(ids) => setData('disabled_menus', ids)}
                                        disabled={processing}
                                    />
                                </CardContent>
                            </Card>
                        )}

                        {errorMessage && <p className="text-sm text-red-500">{errorMessage}</p>}

                        <div className="flex justify-end gap-3 pt-2">
                            <Button type="button" variant="outline" onClick={() => window.history.back()} disabled={processing}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Guardando...' : 'Guardar'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
