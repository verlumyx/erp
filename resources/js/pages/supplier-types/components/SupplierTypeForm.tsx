import { FormLayout } from '@/components/form-layout';
import { FormSection } from '@/components/form-section';
import {
    FormActionBar,
    FormSummary,
    SummaryRow,
} from '@/components/form-summary';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useSupplierTypeFormContext } from '../contexts/SupplierTypeFormContext';

export function SupplierTypeForm() {
    const { data, setData, processing, errors, handleSubmit, mode } =
        useSupplierTypeFormContext();

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos del tipo de proveedor"
                sub="Nombre con el que se clasifican los proveedores"
            >
                <div className="flex flex-col gap-4 p-5">
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="name"
                            className="text-[13px] font-semibold"
                        >
                            Nombre *
                        </Label>
                        <Input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Ej. Nacional"
                            className={`h-[42px] rounded-[10px] ${errors.name ? 'border-bad' : ''}`}
                            maxLength={100}
                            required
                        />
                        {errors.name && (
                            <p className="text-sm text-bad">{errors.name}</p>
                        )}
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="description"
                            className="text-[13px] font-semibold"
                        >
                            Descripción (opcional)
                        </Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            placeholder="Qué proveedores agrupa este tipo…"
                            className={`rounded-[10px] ${errors.description ? 'border-bad' : ''}`}
                            rows={3}
                        />
                        {errors.description && (
                            <p className="text-sm text-bad">
                                {errors.description}
                            </p>
                        )}
                    </div>
                    <p className="text-[13px] text-muted-foreground">
                        El tipo de proveedor es solo clasificación: se usa para
                        reportes y filtros de Compras.
                    </p>
                </div>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Tipo">
                    {data.name || (mode === 'create' ? 'Nuevo' : '—')}
                </SummaryRow>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear tipo' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
