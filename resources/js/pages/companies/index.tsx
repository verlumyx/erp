import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { CompanyList } from './components/CompanyList';
import { Company, CompanyFilters, CompanyMeta } from './types/Company';
import type { BreadcrumbItem } from '@/types';
import companies from '@/routes/companies';

interface Props {
    companies: Company[];
    meta: CompanyMeta;
    filters: CompanyFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function CompaniesIndex({ companies: items, meta, filters }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Empresas', href: companies.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Empresas" />
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <CompanyList companies={items} meta={meta} filters={filters} />
                </div>
            </div>
        </AppLayout>
    );
}
