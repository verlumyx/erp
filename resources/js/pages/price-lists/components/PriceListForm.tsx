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
import { usePriceListFormContext } from '../contexts/PriceListFormContext';

export function PriceListForm() {
    const { data, setData, processing, errors, handleSubmit, mode } =
        usePriceListFormContext();

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos de la lista"
                sub="Los precios se configuran dentro de cada artículo"
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
                            placeholder="Ej. Mayorista"
                            className={`h-[42px] rounded-[10px] ${errors.name ? 'border-bad' : ''}`}
                            maxLength={150}
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
                            placeholder="A qué clientes o campaña corresponde esta lista…"
                            className={`rounded-[10px] ${errors.description ? 'border-bad' : ''}`}
                            rows={3}
                        />
                        {errors.description && (
                            <p className="text-sm text-bad">
                                {errors.description}
                            </p>
                        )}
                    </div>
                </div>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Lista">
                    {data.name || (mode === 'create' ? 'Nueva' : '—')}
                </SummaryRow>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create'
                        ? 'Crear lista de precio'
                        : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
