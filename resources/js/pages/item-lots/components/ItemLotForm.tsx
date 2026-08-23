import { Check } from 'lucide-react';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useItemLotFormContext } from '../contexts/ItemLotFormContext';

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

export function ItemLotForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        itemOption,
        supplierOption,
        selectSupplier,
        supplierLookupUrl,
    } = useItemLotFormContext();

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos del lote"
                        sub="Qué artículo y con qué número se identifica"
                    />
                    <div className="flex flex-col gap-4 p-5">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Artículo
                                </Label>
                                <div className="flex h-[42px] items-center rounded-[10px] border bg-muted px-3 text-sm font-semibold">
                                    <span className="truncate">
                                        {itemOption.label}
                                    </span>
                                </div>
                                <p className="text-[12px] text-muted-foreground">
                                    El artículo no se cambia: el lote se creó al
                                    recibir su mercancía.
                                </p>
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="lot_number"
                                    className="text-[13px] font-semibold"
                                >
                                    Número de lote *
                                </Label>
                                <Input
                                    id="lot_number"
                                    type="text"
                                    value={data.lot_number}
                                    onChange={(e) =>
                                        setData('lot_number', e.target.value)
                                    }
                                    placeholder="Ej. L-2026-001"
                                    className={`h-[42px] rounded-[10px] ${errors.lot_number ? 'border-bad' : ''}`}
                                    maxLength={60}
                                    required
                                />
                                <p className="text-[12px] text-muted-foreground">
                                    Único dentro del artículo.
                                </p>
                                {errors.lot_number && (
                                    <p className="text-sm text-bad">
                                        {errors.lot_number}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="manufactured_at"
                                    className="text-[13px] font-semibold"
                                >
                                    Fabricación
                                </Label>
                                <Input
                                    id="manufactured_at"
                                    type="date"
                                    value={data.manufactured_at}
                                    onChange={(e) =>
                                        setData(
                                            'manufactured_at',
                                            e.target.value,
                                        )
                                    }
                                    className={`h-[42px] rounded-[10px] ${errors.manufactured_at ? 'border-bad' : ''}`}
                                />
                                {errors.manufactured_at && (
                                    <p className="text-sm text-bad">
                                        {errors.manufactured_at}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="expires_at"
                                    className="text-[13px] font-semibold"
                                >
                                    Vencimiento
                                </Label>
                                <Input
                                    id="expires_at"
                                    type="date"
                                    value={data.expires_at}
                                    onChange={(e) =>
                                        setData('expires_at', e.target.value)
                                    }
                                    className={`h-[42px] rounded-[10px] ${errors.expires_at ? 'border-bad' : ''}`}
                                />
                                <p className="text-[12px] text-muted-foreground">
                                    La salida consume primero lo que vence
                                    antes.
                                </p>
                                {errors.expires_at && (
                                    <p className="text-sm text-bad">
                                        {errors.expires_at}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="flex flex-col gap-1.5 md:max-w-[50%]">
                            <Label
                                htmlFor="supplier_id"
                                className="text-[13px] font-semibold"
                            >
                                Proveedor
                            </Label>
                            <Select2Ajax
                                inputId="supplier_id"
                                url={supplierLookupUrl}
                                value={supplierOption}
                                onChange={selectSupplier}
                                error={!!errors.supplier_id}
                                size="md"
                                isClearable
                                placeholder="Busca un proveedor"
                            />
                            <p className="text-[12px] text-muted-foreground">
                                Origen del lote. Se deja vacío en los lotes
                                internos.
                            </p>
                            {errors.supplier_id && (
                                <p className="text-sm text-bad">
                                    {errors.supplier_id}
                                </p>
                            )}
                        </div>
                    </div>
                </Card>
            </div>

            <Card className="gap-3.5 rounded-2xl p-5 xl:sticky xl:top-[86px]">
                <div className="text-base font-bold tracking-tight">
                    Resumen
                </div>
                <div className="flex flex-col gap-2.5">
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Lote
                        </span>
                        <b className="font-bold">{data.lot_number || '—'}</b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Artículo
                        </span>
                        <b className="max-w-[60%] truncate font-bold">
                            {itemOption.label}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Vence
                        </span>
                        <b className="font-bold tabular-nums">
                            {data.expires_at || 'Sin vencimiento'}
                        </b>
                    </div>
                </div>
                <Button
                    type="submit"
                    disabled={processing}
                    className="h-10 w-full justify-center rounded-[11px] font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                >
                    <Check />
                    {processing ? 'Guardando…' : 'Guardar cambios'}
                </Button>
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
