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
import { useMeasurementUnitFormContext } from '../contexts/MeasurementUnitFormContext';

export function MeasurementUnitForm() {
    const { data, setData, processing, errors, handleSubmit, mode } =
        useMeasurementUnitFormContext();

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos de la unidad"
                sub="Nombre y símbolo con los que se identifica"
            >
                <div className="flex flex-col gap-4 p-5">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                placeholder="Ej. Kilogramo"
                                className={`h-[42px] rounded-[10px] ${errors.name ? 'border-bad' : ''}`}
                                maxLength={100}
                                required
                            />
                            {errors.name && (
                                <p className="text-sm text-bad">
                                    {errors.name}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="abbreviation"
                                className="text-[13px] font-semibold"
                            >
                                Símbolo *
                            </Label>
                            <Input
                                id="abbreviation"
                                type="text"
                                value={data.abbreviation}
                                onChange={(e) =>
                                    setData('abbreviation', e.target.value)
                                }
                                placeholder="Ej. kg"
                                className={`h-[42px] rounded-[10px] ${errors.abbreviation ? 'border-bad' : ''}`}
                                maxLength={10}
                                required
                            />
                            {errors.abbreviation && (
                                <p className="text-sm text-bad">
                                    {errors.abbreviation}
                                </p>
                            )}
                        </div>
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
                            placeholder="Para qué se usa esta unidad…"
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
                        La unidad no guarda factores de conversión: la
                        equivalencia se define por artículo.
                    </p>
                </div>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Unidad">
                    {data.name || (mode === 'create' ? 'Nueva' : '—')}
                </SummaryRow>
                <SummaryRow label="Símbolo">
                    {data.abbreviation || '—'}
                </SummaryRow>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear unidad' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
