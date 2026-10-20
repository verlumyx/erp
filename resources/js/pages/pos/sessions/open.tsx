import { useState } from 'react';
import { Head, useForm, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Monitor, DollarSign, Banknote, ArrowRight, AlertCircle, CheckCircle } from 'lucide-react';
import type { BreadcrumbItem } from '@/types';
import type { PosRegister, PosSession } from '../types/Pos';

interface OpenSessionProps {
    alreadyOpen: boolean;
    currentSession: PosSession | null;
    availableRegisters: { data: PosRegister[] } | PosRegister[];
}

export default function PosSessionOpenPage({
    alreadyOpen,
    currentSession,
    availableRegisters,
}: OpenSessionProps) {
    const { currentCompany } = usePage<{ currentCompany?: { id: string; name: string } }>().props;
    const companyId = currentCompany?.id || '';

    const registers: PosRegister[] = Array.isArray(availableRegisters)
        ? availableRegisters
        : (availableRegisters as { data: PosRegister[] })?.data || [];

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Punto de Venta', href: `/${companyId}/pos` },
        { title: 'Apertura de Turno', href: `/${companyId}/pos-sessions/open` },
    ];

    const { data, setData, post, processing, errors } = useForm({
        pos_register_id: registers.length > 0 ? registers[0].id : '',
        opening_cash_usd: '0.00',
        opening_cash_ves: '0.00',
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/${companyId}/pos-sessions/open`);
    };

    if (alreadyOpen && currentSession) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Turno de Caja Activo" />
                <div className="mx-auto max-w-2xl p-6 py-12">
                    <Card className="border-primary/30 shadow-md">
                        <CardHeader className="text-center pb-4">
                            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 mb-2">
                                <CheckCircle className="h-8 w-8" />
                            </div>
                            <CardTitle className="text-2xl font-bold">Tienes un turno de caja abierto</CardTitle>
                            <CardDescription className="text-sm">
                                Ya tienes una sesión de ventas activa en esta empresa.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4 rounded-xl border bg-muted/40 p-4 text-sm">
                                <div>
                                    <span className="text-xs uppercase text-muted-foreground font-semibold">Caja:</span>
                                    <p className="font-bold text-foreground text-base">
                                        {currentSession.pos_register_name || 'Caja Registradora'}
                                    </p>
                                </div>
                                <div>
                                    <span className="text-xs uppercase text-muted-foreground font-semibold">Código Turno:</span>
                                    <p className="font-mono font-bold text-primary text-base">
                                        {currentSession.code}
                                    </p>
                                </div>
                                <div>
                                    <span className="text-xs uppercase text-muted-foreground font-semibold">Apertura:</span>
                                    <p className="font-medium text-foreground">
                                        {new Date(currentSession.opened_at).toLocaleString()}
                                    </p>
                                </div>
                                <div>
                                    <span className="text-xs uppercase text-muted-foreground font-semibold">Fondo Inicial:</span>
                                    <p className="font-mono font-bold text-foreground">
                                        $ {Number(currentSession.opening_cash_usd || 0).toFixed(2)} / Bs. {Number(currentSession.opening_cash_ves || 0).toFixed(2)}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter className="flex justify-between gap-3">
                            <Link href={`/${companyId}/pos-sessions`} className="w-1/2">
                                <Button variant="outline" className="w-full">
                                    Ver historial de turnos
                                </Button>
                            </Link>
                            <Link href={`/${companyId}/pos`} className="w-1/2">
                                <Button className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">
                                    Ir al Terminal POS <ArrowRight className="ml-2 h-4 w-4" />
                                </Button>
                            </Link>
                        </CardFooter>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Apertura de Turno de Caja" />
            <div className="mx-auto max-w-xl p-6 py-12">
                <form onSubmit={handleSubmit}>
                    <Card className="shadow-md border-border">
                        <CardHeader className="text-center pb-4">
                            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary mb-2">
                                <Monitor className="h-6 w-6" />
                            </div>
                            <CardTitle className="text-2xl font-bold">Apertura de Turno de Caja</CardTitle>
                            <CardDescription className="text-sm">
                                Selecciona la caja registradora y declara el fondo inicial en gaveta
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-5">
                            {/* Register Selection */}
                            <div className="space-y-2">
                                <Label htmlFor="pos_register_id" className="font-semibold text-xs uppercase tracking-wider text-muted-foreground">
                                    Caja Registradora <span className="text-destructive">*</span>
                                </Label>
                                {registers.length === 0 ? (
                                    <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive flex items-center gap-2">
                                        <AlertCircle className="h-4 w-4 shrink-0" />
                                        <span>No hay cajas disponibles o todas están ocupadas por otro cajero.</span>
                                    </div>
                                ) : (
                                    <select
                                        id="pos_register_id"
                                        value={data.pos_register_id}
                                        onChange={(e) => setData('pos_register_id', e.target.value)}
                                        className="h-11 w-full rounded-xl border border-border bg-card px-3 text-sm font-medium shadow-xs focus:border-primary focus:outline-hidden focus:ring-2 focus:ring-primary/20"
                                    >
                                        {registers.map((r) => (
                                            <option key={r.id} value={r.id}>
                                                {r.name} ({r.code}) {r.warehouse_name ? `- Almacén: ${r.warehouse_name}` : ''}
                                            </option>
                                        ))}
                                    </select>
                                )}
                                {errors.pos_register_id && (
                                    <p className="text-xs text-destructive">{errors.pos_register_id}</p>
                                )}
                            </div>

                            {/* Cash drawer initial funds */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="opening_cash_usd" className="font-semibold text-xs uppercase tracking-wider text-muted-foreground">
                                        Fondo Efectivo USD
                                    </Label>
                                    <div className="relative">
                                        <DollarSign className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-emerald-600" />
                                        <Input
                                            id="opening_cash_usd"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.opening_cash_usd}
                                            onChange={(e) => setData('opening_cash_usd', e.target.value)}
                                            className="h-11 pl-9 font-mono font-bold text-base"
                                            placeholder="0.00"
                                        />
                                    </div>
                                    {errors.opening_cash_usd && (
                                        <p className="text-xs text-destructive">{errors.opening_cash_usd}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="opening_cash_ves" className="font-semibold text-xs uppercase tracking-wider text-muted-foreground">
                                        Fondo Efectivo VES (Bs.)
                                    </Label>
                                    <div className="relative">
                                        <Banknote className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-blue-600" />
                                        <Input
                                            id="opening_cash_ves"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.opening_cash_ves}
                                            onChange={(e) => setData('opening_cash_ves', e.target.value)}
                                            className="h-11 pl-9 font-mono font-bold text-base"
                                            placeholder="0.00"
                                        />
                                    </div>
                                    {errors.opening_cash_ves && (
                                        <p className="text-xs text-destructive">{errors.opening_cash_ves}</p>
                                    )}
                                </div>
                            </div>

                            {/* Notes */}
                            <div className="space-y-2">
                                <Label htmlFor="notes" className="font-semibold text-xs uppercase tracking-wider text-muted-foreground">
                                    Observaciones de Apertura
                                </Label>
                                <Input
                                    id="notes"
                                    type="text"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder="Novedades de la gaveta o hardware..."
                                    className="h-10 text-sm"
                                />
                                {errors.notes && (
                                    <p className="text-xs text-destructive">{errors.notes}</p>
                                )}
                            </div>
                        </CardContent>

                        <CardFooter className="flex justify-end gap-3 pt-2">
                            <Link href={`/${companyId}/pos-sessions`}>
                                <Button type="button" variant="outline">
                                    Cancelar
                                </Button>
                            </Link>
                            <Button
                                type="submit"
                                disabled={processing || registers.length === 0}
                                className="bg-primary hover:bg-primary/90 text-primary-foreground font-semibold px-6"
                            >
                                {processing ? 'Abriendo Turno...' : 'Abrir Turno y Comenzar'}
                            </Button>
                        </CardFooter>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}
