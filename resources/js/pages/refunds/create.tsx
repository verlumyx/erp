import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import refunds from '@/routes/refunds';
import type { BreadcrumbItem } from '@/types';
import { RefundForm } from './components/RefundForm';
import { RefundFormProvider } from './contexts/RefundFormContext';
import { useRefundForm } from './hooks/useRefundForm';
import type { RefundableSale } from './types/Refund';

interface Props {
    sales: RefundableSale[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function RefundsCreate({ sales }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const formState = useRefundForm({ mode: 'create' });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Reembolsos', href: refunds.index(companyId).url },
        { title: 'Nuevo reembolso', href: refunds.create(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo reembolso" />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={refunds.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Reembolsos
                </Link>

                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Crear reembolso
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra un reembolso pendiente de aprobación.
                    </p>
                </div>

                <Card className="rounded-2xl p-6">
                    <RefundFormProvider value={formState}>
                        <RefundForm
                            sales={sales}
                            onCancel={() =>
                                router.visit(refunds.index(companyId).url)
                            }
                        />
                    </RefundFormProvider>
                </Card>
            </div>
        </AppLayout>
    );
}
