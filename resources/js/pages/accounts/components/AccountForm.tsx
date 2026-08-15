import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { clp } from '@/lib/crm-demo';
import { useAccountFormContext } from '../contexts/AccountFormContext';
import {
    ACCOUNT_STATUS_LABELS,
    ACCOUNT_STATUSES,
    PROFILE_STATUS_LABELS,
    PROFILE_STATUSES,
    type AccountStatus,
    type ProfileStatus,
} from '../types/Account';

interface FormSectionHeadProps {
    step: number;
    title: string;
    sub: string;
}

function FormSectionHead({ step, title, sub }: FormSectionHeadProps) {
    return (
        <div className="flex items-center gap-3 border-b p-5">
            <span className="grid size-[30px] shrink-0 place-items-center rounded-[9px] bg-primary-soft text-sm font-extrabold text-primary">
                {step}
            </span>
            <div className="mr-auto">
                <div className="text-base font-bold tracking-tight">
                    {title}
                </div>
                <div className="mt-0.5 text-[13px] text-muted-foreground">
                    {sub}
                </div>
            </div>
        </div>
    );
}

export function AccountForm() {
    const {
        data,
        setData,
        setProfile,
        changeService,
        processing,
        errors,
        handleSubmit,
        mode,
        services,
        selectedService,
    } = useAccountFormContext();

    const err = errors as Record<string, string>;
    const isEdit = mode === 'edit';

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                {/* Paso 1 — Cabecera */}
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos de la cuenta"
                        sub="Cabecera de la cuenta del servicio de streaming"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="service_id"
                                className="text-[13px] font-semibold"
                            >
                                Servicio *
                            </Label>
                            <Select
                                value={data.service_id || undefined}
                                onValueChange={changeService}
                                disabled={isEdit || services.length === 0}
                            >
                                <SelectTrigger
                                    id="service_id"
                                    className={`h-[42px] w-full rounded-[10px] ${err.service_id ? 'border-bad' : ''}`}
                                >
                                    <SelectValue
                                        placeholder={
                                            services.length === 0
                                                ? 'Sin services activos'
                                                : 'Selecciona un service'
                                        }
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {services.map((s) => (
                                        <SelectItem key={s.id} value={s.id}>
                                            {s.name} ({s.code}) ·{' '}
                                            {s.max_profiles} perfiles
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {isEdit && (
                                <p className="text-xs text-muted-foreground">
                                    El servicio no puede cambiarse en una cuenta
                                    existente.
                                </p>
                            )}
                            {err.service_id && (
                                <p className="text-sm text-bad">
                                    {err.service_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="email"
                                className="text-[13px] font-semibold"
                            >
                                Email de la cuenta *
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                                placeholder="cuenta@servicio.com"
                                className={`h-[42px] rounded-[10px] ${err.email ? 'border-bad' : ''}`}
                                maxLength={255}
                                required
                            />
                            {err.email && (
                                <p className="text-sm text-bad">{err.email}</p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="password"
                                className="text-[13px] font-semibold"
                            >
                                Contraseña {isEdit ? '' : '*'}
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                placeholder={
                                    isEdit
                                        ? 'Dejar en blanco para mantener'
                                        : 'Contraseña de la cuenta'
                                }
                                className={`h-[42px] rounded-[10px] ${err.password ? 'border-bad' : ''}`}
                                maxLength={255}
                                autoComplete="new-password"
                                required={!isEdit}
                            />
                            {err.password && (
                                <p className="text-sm text-bad">
                                    {err.password}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="cost"
                                className="text-[13px] font-semibold"
                            >
                                Costo *
                            </Label>
                            <CurrencyInput
                                id="cost"
                                min={0}
                                decimals={2}
                                value={data.cost}
                                onValueChange={(value) =>
                                    setData('cost', value)
                                }
                                className={`h-[42px] rounded-[10px] ${err.cost ? 'border-bad' : ''}`}
                                required
                            />
                            {err.cost && (
                                <p className="text-sm text-bad">{err.cost}</p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="purchase_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha de compra *
                            </Label>
                            <Input
                                id="purchase_date"
                                type="date"
                                value={data.purchase_date}
                                onChange={(e) =>
                                    setData('purchase_date', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${err.purchase_date ? 'border-bad' : ''}`}
                                required
                            />
                            {err.purchase_date && (
                                <p className="text-sm text-bad">
                                    {err.purchase_date}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="next_renewal"
                                className="text-[13px] font-semibold"
                            >
                                Próxima renovación *
                            </Label>
                            <Input
                                id="next_renewal"
                                type="date"
                                value={data.next_renewal}
                                onChange={(e) =>
                                    setData('next_renewal', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${err.next_renewal ? 'border-bad' : ''}`}
                                required
                            />
                            {err.next_renewal && (
                                <p className="text-sm text-bad">
                                    {err.next_renewal}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="status"
                                className="text-[13px] font-semibold"
                            >
                                Estado *
                            </Label>
                            <Select
                                value={data.status}
                                onValueChange={(value) =>
                                    setData('status', value as AccountStatus)
                                }
                            >
                                <SelectTrigger
                                    id="status"
                                    className={`h-[42px] w-full rounded-[10px] ${err.status ? 'border-bad' : ''}`}
                                >
                                    <SelectValue placeholder="Estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    {ACCOUNT_STATUSES.map((e) => (
                                        <SelectItem key={e} value={e}>
                                            {ACCOUNT_STATUS_LABELS[e]}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {err.status && (
                                <p className="text-sm text-bad">{err.status}</p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5 md:col-span-2">
                            <Label
                                htmlFor="notes"
                                className="text-[13px] font-semibold"
                            >
                                Notas
                            </Label>
                            <Textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                                placeholder="Notas internas (opcional)"
                                className="min-h-[72px] rounded-[10px]"
                            />
                            {err.notes && (
                                <p className="text-sm text-bad">{err.notes}</p>
                            )}
                        </div>
                    </div>
                </Card>

                {/* Paso 2 — Profiles (líneas tipo factura/items) */}
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Perfiles"
                        sub={
                            isEdit
                                ? 'Actualiza PIN, estado y notas de cada perfil'
                                : 'Se generan automáticamente según el servicio; puedes precargar los PIN'
                        }
                    />
                    {err.profiles && (
                        <p className="px-5 pt-4 text-sm text-bad">
                            {err.profiles}
                        </p>
                    )}
                    {data.profiles.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">
                            {services.length === 0
                                ? 'Primero crea un servicio activo.'
                                : 'Selecciona un servicio para generar sus perfiles.'}
                        </div>
                    ) : (
                        <div className="flex flex-col">
                            <div
                                className={`hidden h-11 items-center gap-3 border-b bg-muted px-5 text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase lg:grid ${
                                    isEdit
                                        ? 'lg:grid-cols-[60px_1fr_1fr_2fr]'
                                        : 'lg:grid-cols-[60px_1fr]'
                                }`}
                            >
                                <div>#</div>
                                <div>PIN</div>
                                {isEdit && <div>Estado</div>}
                                {isEdit && <div>Notas</div>}
                            </div>
                            {data.profiles.map((row, index) => (
                                <div
                                    key={row.number}
                                    className={`grid grid-cols-1 items-center gap-3 border-b px-5 py-3 last:border-b-0 lg:gap-3 ${
                                        isEdit
                                            ? 'lg:grid-cols-[60px_1fr_1fr_2fr]'
                                            : 'lg:grid-cols-[60px_1fr]'
                                    }`}
                                >
                                    <div className="font-bold text-muted-foreground tabular-nums">
                                        #{row.number}
                                    </div>
                                    <div>
                                        <Input
                                            value={row.pin}
                                            onChange={(e) =>
                                                setProfile(
                                                    index,
                                                    'pin',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="PIN"
                                            maxLength={10}
                                            className={`h-[38px] rounded-[10px] ${err[`profiles.${index}.pin`] ? 'border-bad' : ''}`}
                                        />
                                        {err[`profiles.${index}.pin`] && (
                                            <p className="mt-1 text-xs text-bad">
                                                {err[`profiles.${index}.pin`]}
                                            </p>
                                        )}
                                    </div>
                                    {isEdit && (
                                        <div>
                                            <Select
                                                value={row.status}
                                                onValueChange={(value) =>
                                                    setProfile(
                                                        index,
                                                        'status',
                                                        value as ProfileStatus,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="h-[38px] w-full rounded-[10px]">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {PROFILE_STATUSES.map(
                                                        (e) => (
                                                            <SelectItem
                                                                key={e}
                                                                value={e}
                                                            >
                                                                {
                                                                    PROFILE_STATUS_LABELS[
                                                                        e
                                                                    ]
                                                                }
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                            {err[
                                                `profiles.${index}.status`
                                            ] && (
                                                <p className="mt-1 text-xs text-bad">
                                                    {
                                                        err[
                                                            `profiles.${index}.status`
                                                        ]
                                                    }
                                                </p>
                                            )}
                                        </div>
                                    )}
                                    {isEdit && (
                                        <div>
                                            <Input
                                                value={row.notes}
                                                onChange={(e) =>
                                                    setProfile(
                                                        index,
                                                        'notes',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Notas del perfil"
                                                className="h-[38px] rounded-[10px]"
                                            />
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </div>

            {/* Resumen */}
            <Card className="gap-3.5 rounded-2xl p-5 xl:sticky xl:top-[86px]">
                <div className="text-base font-bold tracking-tight">
                    Resumen
                </div>
                <div className="flex flex-col gap-2.5">
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Servicio
                        </span>
                        <b className="font-bold">
                            {selectedService?.name ?? '—'}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Email
                        </span>
                        <b className="max-w-[160px] truncate font-bold">
                            {data.email || '—'}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Estado
                        </span>
                        <b className="font-bold">
                            {ACCOUNT_STATUS_LABELS[data.status]}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Perfiles
                        </span>
                        <b className="font-bold tabular-nums">
                            {data.profiles.length}
                        </b>
                    </div>
                    <div className="flex items-center justify-between border-t border-dashed border-input pt-2.5 text-[15px]">
                        <span className="font-medium text-muted-foreground">
                            Costo
                        </span>
                        <b className="font-bold tabular-nums">
                            {clp(data.cost)}
                        </b>
                    </div>
                </div>
                <Button
                    type="submit"
                    disabled={processing || services.length === 0}
                    className="h-10 w-full justify-center rounded-[11px] font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                >
                    <Check />
                    {processing
                        ? 'Guardando…'
                        : mode === 'create'
                          ? 'Crear cuenta'
                          : 'Guardar cambios'}
                </Button>
                {services.length === 0 && (
                    <p className="text-xs text-muted-foreground">
                        Primero crea un servicio activo para registrar cuentas.
                    </p>
                )}
                <Button
                    type="button"
                    variant="outline"
                    className="h-10 w-full justify-center rounded-[11px] bg-card font-semibold"
                    onClick={() => window.history.back()}
                    disabled={processing}
                >
                    Cancelar
                </Button>
            </Card>
        </form>
    );
}
