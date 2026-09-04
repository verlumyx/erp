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
import { useCategoryFormContext } from '../contexts/CategoryFormContext';

export function CategoryForm() {
    const { data, setData, processing, errors, handleSubmit, mode } =
        useCategoryFormContext();

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos de la categoría"
                sub="Clasificación de los artículos"
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
                                placeholder="Ej. Bebidas"
                                className={`h-[42px] rounded-[10px] ${errors.name ? 'border-bad' : ''}`}
                                maxLength={150}
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
                                htmlFor="order"
                                className="text-[13px] font-semibold"
                            >
                                Orden
                            </Label>
                            <Input
                                id="order"
                                type="number"
                                min={0}
                                value={data.order}
                                onChange={(e) =>
                                    setData(
                                        'order',
                                        Number(e.target.value) || 0,
                                    )
                                }
                                placeholder="0"
                                className={`h-[42px] rounded-[10px] ${errors.order ? 'border-bad' : ''}`}
                            />
                            <p className="text-[12px] text-muted-foreground">
                                Orden de presentación en listados y selectores.
                            </p>
                            {errors.order && (
                                <p className="text-sm text-bad">
                                    {errors.order}
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
                            placeholder="Qué artículos agrupa esta categoría…"
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
                <SummaryRow label="Categoría">
                    {data.name || (mode === 'create' ? 'Nueva' : '—')}
                </SummaryRow>
                <SummaryRow label="Orden">{data.order}</SummaryRow>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear categoría' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
