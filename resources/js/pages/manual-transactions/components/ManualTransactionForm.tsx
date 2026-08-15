import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useManualTransactionFormContext } from '../contexts/ManualTransactionFormContext';
import { ManualTransactionLineRow } from './ManualTransactionLineRow';

interface ManualTransactionFormProps {
    onCancel: () => void;
}

export function ManualTransactionForm({ onCancel }: ManualTransactionFormProps) {
    const { data, setData, errors, processing, total, addLine, handleSubmit } =
        useManualTransactionFormContext();

    return (
        <form onSubmit={handleSubmit} className="flex flex-col gap-6">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="date">Fecha *</Label>
                    <Input
                        id="date"
                        type="date"
                        value={data.date}
                        onChange={(e) => setData('date', e.target.value)}
                        className={`h-[42px] rounded-[10px] ${errors.date ? 'border-bad' : ''}`}
                    />
                    {errors.date && (
                        <p className="text-sm text-bad">{errors.date}</p>
                    )}
                </div>

                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="description">Descripción</Label>
                    <Input
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        className="h-[42px] rounded-[10px]"
                        placeholder="Descripción general (opcional)"
                    />
                </div>
            </div>

            <div className="flex flex-col gap-3">
                <div className="flex items-center justify-between">
                    <Label>Líneas *</Label>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="rounded-[10px] bg-card font-semibold"
                        onClick={addLine}
                    >
                        <Plus className="mr-1.5 size-4" />
                        Agregar línea
                    </Button>
                </div>

                {typeof errors.lines === 'string' && (
                    <p className="text-sm text-bad">{errors.lines}</p>
                )}

                <div className="flex flex-col gap-2.5">
                    {data.lines.map((_, index) => (
                        <ManualTransactionLineRow key={index} index={index} />
                    ))}
                </div>

                <div className="flex items-center justify-end gap-3 border-t pt-3 text-sm">
                    <span className="font-semibold text-muted-foreground">
                        Total
                    </span>
                    <span className="text-lg font-bold tabular-nums">
                        {total.toLocaleString('es-VE', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        })}
                    </span>
                </div>
            </div>

            <div className="flex flex-col gap-1.5">
                <Label htmlFor="notes">Notas</Label>
                <Textarea
                    id="notes"
                    value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)}
                    rows={3}
                    className="rounded-[10px]"
                    placeholder="Notas internas (opcional)"
                />
            </div>

            <div className="flex justify-end gap-2.5">
                <Button
                    type="button"
                    variant="outline"
                    className="rounded-[10px] bg-card font-semibold"
                    onClick={onCancel}
                    disabled={processing}
                >
                    Cancelar
                </Button>
                <Button
                    type="submit"
                    className="rounded-[10px] font-semibold"
                    disabled={processing}
                >
                    {processing ? 'Guardando...' : 'Guardar'}
                </Button>
            </div>
        </form>
    );
}
