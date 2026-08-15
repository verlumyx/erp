import React from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { UserList } from './components/user-list';
import users from '@/routes/users';

export interface User {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    status: 'active' | 'inactive';
    created_at: string;
    role?: {
        id: string;
        name: string;
    } | null;
}

interface Props {
    users: User[];
    meta: {
        total: number;
        limit: number;
        offset: number;
        has_more: boolean;
    };
    filters: {
        name?: string;
        email?: string;
        email_verified?: boolean;
        limit?: number;
        offset?: number;
    };
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function UsersIndex({ users: items, meta, filters }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const handleCreate = () => {
        router.visit(users.create(companyId).url);
    };

    const handleSearch = (searchFilters: any) => {
        router.get(users.index(companyId).url, searchFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout>
            <Head title="Usuarios" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <UserList
                        users={items}
                        loading={false}
                        error={null}
                        onCreate={handleCreate}
                        onSearch={handleSearch}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
