import DefaultCompanyController from '@/actions/App/Http/Controllers/Settings/DefaultCompanyController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, usePage } from '@inertiajs/react';
import { Building2, Star } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Empresa predeterminada',
        href: '/settings/company',
    },
];

interface Company {
    id: string;
    name: string;
}

interface PageProps {
    defaultCompanyId?: string | null;
    userCompanies?: Company[];
    [key: string]: unknown;
}

export default function CompanySettings() {
    const { defaultCompanyId, userCompanies = [] } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Empresa predeterminada" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Empresa predeterminada"
                        description="Selecciona la empresa que se cargará automáticamente al iniciar sesión"
                    />

                    {userCompanies.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No tienes empresas asignadas.
                        </p>
                    ) : (
                        <div className="space-y-2">
                            {userCompanies.map((c) => {
                                const isDefault = defaultCompanyId === c.id;

                                return (
                                    <div
                                        key={c.id}
                                        className={cn(
                                            'flex items-center gap-3 rounded-lg border px-4 py-3 transition-colors',
                                            isDefault
                                                ? 'border-primary bg-primary/5'
                                                : 'border-border',
                                        )}
                                    >
                                        <Building2
                                            className={cn(
                                                'h-5 w-5 shrink-0',
                                                isDefault
                                                    ? 'text-primary'
                                                    : 'text-muted-foreground',
                                            )}
                                        />
                                        <span
                                            className={cn(
                                                'flex-1 text-sm font-medium',
                                                isDefault && 'text-primary',
                                            )}
                                        >
                                            {c.name}
                                        </span>

                                        {isDefault ? (
                                            <Star className="h-4 w-4 fill-primary text-primary" />
                                        ) : (
                                            <Form
                                                action={DefaultCompanyController.update.url()}
                                                method="put"
                                                options={{ preserveScroll: true }}
                                            >
                                                {({ processing, recentlySuccessful }) => (
                                                    <>
                                                        <input
                                                            type="hidden"
                                                            name="company_id"
                                                            value={c.id}
                                                        />
                                                        <Button
                                                            type="submit"
                                                            variant="ghost"
                                                            size="sm"
                                                            disabled={processing}
                                                            className="text-muted-foreground hover:text-primary"
                                                        >
                                                            <Star className="h-4 w-4" />
                                                            Establecer como predeterminada
                                                        </Button>
                                                        <Transition
                                                            show={recentlySuccessful}
                                                            enter="transition ease-in-out"
                                                            enterFrom="opacity-0"
                                                            leave="transition ease-in-out"
                                                            leaveTo="opacity-0"
                                                        >
                                                            <span className="text-xs text-green-600">
                                                                Guardado
                                                            </span>
                                                        </Transition>
                                                    </>
                                                )}
                                            </Form>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}

                    <p className="text-xs text-muted-foreground">
                        La empresa marcada con{' '}
                        <Star className="inline h-3 w-3 fill-primary text-primary" />{' '}
                        se cargará automáticamente al iniciar sesión.
                    </p>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
