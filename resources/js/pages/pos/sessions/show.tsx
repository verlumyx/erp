import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Printer, ArrowLeft, CheckCircle2, AlertTriangle, XCircle, FileText, Calendar, User, Monitor, Warehouse } from 'lucide-react';
import type { BreadcrumbItem } from '@/types';
import type { PosZReport } from '../types/Pos';
import { printThermalReportXZ } from '../utils/printReportXZ';

interface ShowSessionProps {
    report: PosZReport;
}

export default function PosSessionShowPage({ report }: ShowSessionProps) {
    const { currentCompany } = usePage<{ currentCompany?: { id: string; name: string } }>().props;
    const companyId = currentCompany?.id || '';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Sesiones y Cortes', href: `/${companyId}/pos-sessions` },
        { title: `${report.type === 'Z' ? 'Corte Z' : 'Lectura X'} - ${report.session.code}`, href: '#' },
    ];

    const handlePrint = () => {
        printThermalReportXZ(report);
    };

    const handlePdf = () => {
        window.open(`/${companyId}/pos-sessions/${report.session.id}/pdf`, '_blank');
    };

    const getDiffBadge = (diff: number | null | undefined, status: string) => {
        if (diff === null || diff === undefined) {
            return <Badge variant="outline">Sin declarar</Badge>;
        }
        if (status === 'exact' || Math.abs(diff) < 0.009) {
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <CheckCircle2 className="h-3.5 w-3.5" /> Cuadrado
                </span>
            );
        }
        if (status === 'surplus' || diff > 0) {
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                    <AlertTriangle className="h-3.5 w-3.5" /> Sobrante (+{diff.toFixed(2)})
                </span>
            );
        }
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                <XCircle className="h-3.5 w-3.5" /> Faltante ({diff.toFixed(2)})
            </span>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${report.type === 'Z' ? 'Corte Z' : 'Lectura X'} - ${report.session.code}`} />

            <div className="mx-auto max-w-5xl p-6 space-y-6">
                {/* Action Bar (hidden in print) */}
                <div className="flex items-center justify-between no-print">
                    <div className="flex items-center gap-3">
                        <Link href={`/${companyId}/pos-sessions`}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="mr-1.5 h-4 w-4" /> Volver al listado
                            </Button>
                        </Link>
                        <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <span>{report.type === 'Z' ? 'Corte de Caja Definitivo (Corte Z)' : 'Lectura Parcial (Corte X)'}</span>
                            <span className="font-mono text-primary font-semibold">{report.session.code}</span>
                        </h1>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button variant="outline" onClick={handlePdf} className="no-print">
                            <FileText className="mr-1.5 h-4 w-4" /> Hoja Carta/A4 (PDF)
                        </Button>
                        <Button onClick={handlePrint} className="bg-primary hover:bg-primary/90 font-bold">
                            <Printer className="mr-1.5 h-4 w-4" /> Imprimir Ticket (58mm)
                        </Button>
                    </div>
                </div>

                {/* Printable Document Container */}
                <div className="rounded-2xl border bg-card p-6 shadow-sm space-y-6 print:border-none print:shadow-none print:p-0">
                    {/* Header Details */}
                    <div className="border-b pb-6">
                        <div className="flex justify-between items-start">
                            <div>
                                <h2 className="text-2xl font-black text-foreground uppercase tracking-tight">
                                    {report.company.name || 'EMPRESA'}
                                </h2>
                                <p className="text-sm text-muted-foreground font-mono">
                                    RIF / NIT: {report.company.tax_number || 'J-00000000-0'}
                                </p>
                                {report.company.address && (
                                    <p className="text-xs text-muted-foreground mt-0.5">{report.company.address}</p>
                                )}
                            </div>

                            <div className="text-right">
                                <div className="inline-block rounded-xl border bg-muted/40 px-4 py-2 text-right">
                                    <span className="text-xs uppercase font-bold text-muted-foreground block">
                                        {report.type === 'Z' ? 'Reporte Corte Z' : 'Lectura Parcial X'}
                                    </span>
                                    <span className="text-lg font-mono font-black text-primary">
                                        {report.session.code}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Metadata Grid */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-4 border-t text-xs">
                            <div className="flex items-start gap-2">
                                <Monitor className="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                <div>
                                    <span className="font-semibold text-muted-foreground block uppercase text-[10px]">Caja / Terminal</span>
                                    <span className="font-bold text-foreground">{report.session.register_name || 'N/A'}</span>
                                    {report.session.warehouse_name && (
                                        <span className="text-muted-foreground block text-[11px]">({report.session.warehouse_name})</span>
                                    )}
                                </div>
                            </div>

                            <div className="flex items-start gap-2">
                                <User className="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                <div>
                                    <span className="font-semibold text-muted-foreground block uppercase text-[10px]">Cajero Responsable</span>
                                    <span className="font-bold text-foreground">{report.session.cashier_name || 'N/A'}</span>
                                </div>
                            </div>

                            <div className="flex items-start gap-2">
                                <Calendar className="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                <div>
                                    <span className="font-semibold text-muted-foreground block uppercase text-[10px]">Apertura de Turno</span>
                                    <span className="font-mono text-foreground">{report.session.opened_at}</span>
                                </div>
                            </div>

                            <div className="flex items-start gap-2">
                                <Calendar className="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                <div>
                                    <span className="font-semibold text-muted-foreground block uppercase text-[10px]">Cierre de Turno</span>
                                    <span className="font-mono text-foreground">{report.session.closed_at || 'En curso (Abierto)'}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Operational Summary */}
                    <div>
                        <h3 className="text-sm font-bold uppercase tracking-wider text-muted-foreground mb-3">
                            1. Resumen de Operaciones
                        </h3>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div className="rounded-xl border bg-muted/20 p-3">
                                <span className="text-[11px] uppercase font-semibold text-muted-foreground block">Ventas Realizadas</span>
                                <span className="text-xl font-bold font-mono text-foreground">{report.summary.sales_count}</span>
                                {report.summary.cancelled_sales_count > 0 && (
                                    <span className="text-xs text-rose-600 block mt-0.5">({report.summary.cancelled_sales_count} anuladas)</span>
                                )}
                            </div>

                            <div className="rounded-xl border bg-muted/20 p-3">
                                <span className="text-[11px] uppercase font-semibold text-muted-foreground block">Ticket Promedio</span>
                                <span className="text-xl font-bold font-mono text-foreground">
                                    $ {report.summary.average_ticket.toFixed(2)}
                                </span>
                            </div>

                            <div className="rounded-xl border bg-muted/20 p-3">
                                <span className="text-[11px] uppercase font-semibold text-muted-foreground block">Base Imponible</span>
                                <span className="text-xl font-bold font-mono text-foreground">
                                    $ {report.summary.taxable_base.toFixed(2)}
                                </span>
                                <span className="text-xs text-muted-foreground block mt-0.5">IVA: $ {report.summary.total_tax.toFixed(2)}</span>
                            </div>

                            <div className="rounded-xl border bg-primary/10 border-primary/20 p-3">
                                <span className="text-[11px] uppercase font-bold text-primary block">Total Facturado Neto</span>
                                <span className="text-xl font-black font-mono text-primary">
                                    $ {report.summary.total_net.toFixed(2)}
                                </span>
                                {report.summary.discounts > 0 && (
                                    <span className="text-xs text-muted-foreground block mt-0.5">Dctos: $ {report.summary.discounts.toFixed(2)}</span>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Payments Breakdown */}
                    <div>
                        <h3 className="text-sm font-bold uppercase tracking-wider text-muted-foreground mb-3">
                            2. Cobros por Medio de Pago
                        </h3>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                            <div className="rounded-xl border p-3 flex justify-between items-center">
                                <div>
                                    <span className="text-xs text-muted-foreground block">Efectivo USD</span>
                                    <span className="font-mono font-bold">$ {report.payments.cash_usd.toFixed(2)}</span>
                                </div>
                            </div>
                            <div className="rounded-xl border p-3 flex justify-between items-center">
                                <div>
                                    <span className="text-xs text-muted-foreground block">Efectivo VES</span>
                                    <span className="font-mono font-bold">Bs. {report.payments.cash_ves.toFixed(2)}</span>
                                </div>
                            </div>
                            <div className="rounded-xl border p-3 flex justify-between items-center">
                                <div>
                                    <span className="text-xs text-muted-foreground block">Punto / Débito VES</span>
                                    <span className="font-mono font-bold">Bs. {report.payments.card_ves.toFixed(2)}</span>
                                </div>
                            </div>
                            <div className="rounded-xl border p-3 flex justify-between items-center">
                                <div>
                                    <span className="text-xs text-muted-foreground block">Pago Móvil VES</span>
                                    <span className="font-mono font-bold">Bs. {report.payments.mobile_ves.toFixed(2)}</span>
                                </div>
                            </div>
                            <div className="rounded-xl border p-3 flex justify-between items-center">
                                <div>
                                    <span className="text-xs text-muted-foreground block">Transferencia / Zelle</span>
                                    <span className="font-mono font-bold">$ {(report.payments.transfer_usd ?? 0).toFixed(2)}</span>
                                </div>
                            </div>
                            {report.payments.biopago_ves !== undefined && report.payments.biopago_ves > 0 && (
                                <div className="rounded-xl border p-3 flex justify-between items-center">
                                    <div>
                                        <span className="text-xs text-muted-foreground block">Biopago VES</span>
                                        <span className="font-mono font-bold">Bs. {report.payments.biopago_ves.toFixed(2)}</span>
                                    </div>
                                </div>
                            )}
                            {report.payments.transfer_ves !== undefined && report.payments.transfer_ves > 0 && (
                                <div className="rounded-xl border p-3 flex justify-between items-center">
                                    <div>
                                        <span className="text-xs text-muted-foreground block">Transferencia VES</span>
                                        <span className="font-mono font-bold">Bs. {report.payments.transfer_ves.toFixed(2)}</span>
                                    </div>
                                </div>
                            )}
                            {report.payments.other !== undefined && report.payments.other > 0 && (
                                <div className="rounded-xl border p-3 flex justify-between items-center">
                                    <div>
                                        <span className="text-xs text-muted-foreground block">Otros</span>
                                        <span className="font-mono font-bold">$ {report.payments.other.toFixed(2)}</span>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Cash Audit & Reconciliation Tables */}
                    <div>
                        <h3 className="text-sm font-bold uppercase tracking-wider text-muted-foreground mb-3">
                            3. Auditoría y Conciliación de Efectivo
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* USD Reconciliation */}
                            <div className="rounded-xl border bg-muted/10 p-4 space-y-2.5 text-xs">
                                <div className="flex items-center justify-between pb-2 border-b">
                                    <span className="font-bold uppercase tracking-wider text-foreground text-sm">Efectivo USD</span>
                                    {getDiffBadge(report.audit_usd.difference, report.audit_usd.status)}
                                </div>
                                <div className="flex justify-between text-muted-foreground">
                                    <span>(+) Fondo Inicial:</span>
                                    <span className="font-mono font-semibold text-foreground">$ {report.audit_usd.opening_cash.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between text-muted-foreground">
                                    <span>(+) Cobros por Ventas:</span>
                                    <span className="font-mono font-semibold text-foreground">$ {report.audit_usd.sales_cash.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between text-muted-foreground">
                                    <span>(+) Entradas de Efectivo:</span>
                                    <span className="font-mono font-semibold text-emerald-600">+$ {report.audit_usd.cash_in.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between text-muted-foreground">
                                    <span>(-) Retiros / Sangrías de Caja:</span>
                                    <span className="font-mono font-semibold text-rose-600">-$ {report.audit_usd.cash_out.toFixed(2)}</span>
                                </div>
                                <div className="pt-2 border-t flex justify-between font-bold text-foreground text-sm">
                                    <span>(=) Total Esperado en Sistema:</span>
                                    <span className="font-mono">$ {report.audit_usd.expected.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between text-muted-foreground">
                                    <span>Total Físico Contado y Declarado:</span>
                                    <span className="font-mono font-bold text-foreground">
                                        {report.audit_usd.declared != null ? `$ ${report.audit_usd.declared.toFixed(2)}` : 'N/A'}
                                    </span>
                                </div>
                                <div className="pt-2 border-t flex justify-between font-bold text-sm">
                                    <span>DIFERENCIA USD:</span>
                                    <span className={`font-mono ${report.audit_usd.difference && report.audit_usd.difference < 0 ? 'text-destructive' : 'text-emerald-600'}`}>
                                        {report.audit_usd.difference != null ? `$ ${report.audit_usd.difference.toFixed(2)}` : 'N/A'}
                                    </span>
                                </div>
                            </div>

                            {/* VES Reconciliation */}
                            <div className="rounded-xl border bg-muted/10 p-4 space-y-2.5 text-xs">
                                <div className="flex items-center justify-between pb-2 border-b">
                                    <span className="font-bold uppercase tracking-wider text-foreground text-sm">Efectivo Bolívares (VES)</span>
                                    {getDiffBadge(report.audit_ves.difference, report.audit_ves.status)}
                                </div>
                                <div className="flex justify-between text-muted-foreground">
                                    <span>(+) Fondo Inicial:</span>
                                    <span className="font-mono font-semibold text-foreground">Bs. {report.audit_ves.opening_cash.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between text-muted-foreground">
                                    <span>(+) Cobros por Ventas:</span>
                                    <span className="font-mono font-semibold text-foreground">Bs. {report.audit_ves.sales_cash.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between text-muted-foreground">
                                    <span>(+) Entradas de Efectivo:</span>
                                    <span className="font-mono font-semibold text-emerald-600">+Bs. {report.audit_ves.cash_in.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between text-muted-foreground">
                                    <span>(-) Retiros / Sangrías / Gastos:</span>
                                    <span className="font-mono font-semibold text-rose-600">-Bs. {report.audit_ves.cash_out.toFixed(2)}</span>
                                </div>
                                <div className="pt-2 border-t flex justify-between font-bold text-foreground text-sm">
                                    <span>(=) Total Esperado en Sistema:</span>
                                    <span className="font-mono">Bs. {report.audit_ves.expected.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between text-muted-foreground">
                                    <span>Total Físico Contado y Declarado:</span>
                                    <span className="font-mono font-bold text-foreground">
                                        {report.audit_ves.declared != null ? `Bs. ${report.audit_ves.declared.toFixed(2)}` : 'N/A'}
                                    </span>
                                </div>
                                <div className="pt-2 border-t flex justify-between font-bold text-sm">
                                    <span>DIFERENCIA VES:</span>
                                    <span className={`font-mono ${report.audit_ves.difference && report.audit_ves.difference < 0 ? 'text-destructive' : 'text-emerald-600'}`}>
                                        {report.audit_ves.difference != null ? `Bs. ${report.audit_ves.difference.toFixed(2)}` : 'N/A'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Denominations breakdown (if declared) */}
                    {report.denominations && report.denominations.length > 0 && (
                        <div>
                            <h3 className="text-sm font-bold uppercase tracking-wider text-muted-foreground mb-3">
                                4. Conteo Detallado de Billetes Declarados
                            </h3>
                            <div className="overflow-x-auto rounded-xl border">
                                <table className="w-full text-xs text-left">
                                    <thead className="bg-muted/50 border-b uppercase text-[10px] text-muted-foreground">
                                        <tr>
                                            <th className="p-2.5">Moneda</th>
                                            <th className="p-2.5">Denominación</th>
                                            <th className="p-2.5 text-center">Billetes Contados</th>
                                            <th className="p-2.5 text-right">Monto Total</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {report.denominations.map((d, i) => (
                                            <tr key={i} className="hover:bg-muted/30">
                                                <td className="p-2.5 font-bold">{d.currency}</td>
                                                <td className="p-2.5 font-mono">
                                                    {d.currency === 'USD' ? `$ ${d.denomination.toFixed(2)}` : `Bs. ${d.denomination.toFixed(2)}`}
                                                </td>
                                                <td className="p-2.5 text-center font-mono font-semibold">{d.count}</td>
                                                <td className="p-2.5 text-right font-mono font-bold">
                                                    {d.currency === 'USD' ? `$ ${d.total_amount.toFixed(2)}` : `Bs. ${d.total_amount.toFixed(2)}`}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {/* Closing notes */}
                    {report.session.closing_notes && (
                        <div className="rounded-xl border bg-muted/20 p-4 text-xs">
                            <span className="font-bold uppercase tracking-wider text-muted-foreground block mb-1">
                                Observaciones de Cierre:
                            </span>
                            <p className="italic text-foreground">{report.session.closing_notes}</p>
                        </div>
                    )}

                    {/* Signatures for physical audit print */}
                    <div className="grid grid-cols-2 gap-12 pt-12 text-center text-xs">
                        <div className="border-t border-foreground/40 pt-2">
                            <span className="font-bold block text-foreground">{report.session.cashier_name || 'Cajero'}</span>
                            <span className="text-muted-foreground">Firma Cajero Responsable</span>
                        </div>
                        <div className="border-t border-foreground/40 pt-2">
                            <span className="font-bold block text-foreground">{report.session.supervisor_name || 'Supervisor'}</span>
                            <span className="text-muted-foreground">Firma Supervisor de Caja</span>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
