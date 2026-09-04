import { Select2Ajax } from '@/components/select2-ajax';
import { FormLayout } from '@/components/form-layout';
import { FormSection } from '@/components/form-section';
import {
    FormActionBar,
    FormSummary,
    SummaryRow,
} from '@/components/form-summary';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useItemLotFormContext } from '../contexts/ItemLotFormContext';

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
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos del lote"
                sub="Qué artículo y con qué número se identifica"
            >
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
                                    setData('manufactured_at', e.target.value)
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
                                La salida consume primero lo que vence antes.
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
            </FormSection>

            <FormSummary>
                <SummaryRow label="Lote">{data.lot_number || '—'}</SummaryRow>
                <SummaryRow
                    label="Artículo"
                    valueClassName="max-w-[60%] truncate font-bold"
                >
                    {itemOption.label}
                </SummaryRow>
                <SummaryRow label="Vence">
                    {data.expires_at || 'Sin vencimiento'}
                </SummaryRow>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel="Guardar cambios"
            />
        </FormLayout>
    );
}
