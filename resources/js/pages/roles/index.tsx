import React from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { RoleList } from './components/role-list';
import { Role } from '@/types/role';
import type { BreadcrumbItem } from '@/types';
import roles from '@/routes/roles';

interface Props {
    roles: Role[];
    meta: {
        total: number;
        limit: number;
        offset: number;
        has_more: boolean;
    };
    filters: {
        name?: string;
        status?: string;
        limit?: number;
        offset?: number;
    };
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function RolesIndex({ roles: items, meta, filters }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Roles',
            href: roles.index(companyId).url,
        },
    ];

    const handleSearch = (searchFilters: any) => {
        router.get(roles.index(companyId).url, searchFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <RoleList
                        roles={items}
                        loading={false}
                        error={null}
                        onSearch={handleSearch}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
