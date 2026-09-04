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
import { useClientTypeFormContext } from '../contexts/ClientTypeFormContext';

export function ClientTypeForm() {
    const { data, setData, processing, errors, handleSubmit, mode } =
        useClientTypeFormContext();

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos del tipo de cliente"
                sub="Nombre con el que se clasifica al cliente"
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
                            placeholder="Para qué se usa esta clasificación…"
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
                        El tipo de cliente no interviene en el precio: es solo
                        clasificación para reportes y filtros.
                    </p>
                </div>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Tipo de cliente">
                    {data.name || (mode === 'create' ? 'Nuevo' : '—')}
                </SummaryRow>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create'
                        ? 'Crear tipo de cliente'
                        : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
