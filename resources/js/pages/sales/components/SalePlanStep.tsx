import { Check, Package } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { clp } from '@/lib/crm-demo';
import { useSaleFormContext } from '../contexts/SaleFormContext';
import { SALE_CAPACITY_LABELS } from '../types/Sale';

/**
 * Paso 2 del wizard: elegir el plan. Al seleccionarlo se fija automáticamente el
 * servicio y el snapshot (capacidad, duración, precio) que verá la venta.
 */
export function SalePlanStep() {
    const { plans, form, selectPlan, selectedPlan } = useSaleFormContext();

    return (
        <div className="flex flex-col gap-4">
            <div className="flex flex-col gap-1.5">
                <Label htmlFor="start_date">Fecha de inicio *</Label>
                <Input
                    id="start_date"
                    type="date"
                    value={form.data.start_date}
                    onChange={(e) => form.setData('start_date', e.target.value)}
                    className={`h-[42px] max-w-xs rounded-[10px] ${
                        form.errors.start_date ? 'border-bad' : ''
                    }`}
                />
                {form.errors.start_date && (
                    <p className="text-sm text-bad">{form.errors.start_date}</p>
                )}
            </div>

            {form.errors.plan_id && (
                <p className="text-sm text-bad">{form.errors.plan_id}</p>
            )}

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                {plans.map((plan) => {
                    const selected = selectedPlan?.id === plan.id;
                    return (
                        <button
                            key={plan.id}
                            type="button"
                            onClick={() => selectPlan(plan.id)}
                            className={`flex flex-col gap-2 rounded-[12px] border p-4 text-left transition-colors ${
                                selected
                                    ? 'border-primary bg-primary/5'
                                    : 'hover:bg-muted'
                            }`}
                        >
                            <div className="flex items-center justify-between">
                                <span className="flex items-center gap-2 font-bold">
                                    <Package className="size-4 text-muted-foreground" />
                                    {plan.name}
                                </span>
                                {selected && (
                                    <Check className="size-5 text-primary" />
                                )}
                            </div>
                            <div className="flex flex-wrap gap-x-3 gap-y-1 text-[12.5px] text-muted-foreground">
                                <span>{plan.service_name ?? '—'}</span>
                                <span>
                                    · {SALE_CAPACITY_LABELS[plan.capacity]}
                                </span>
                                <span>· {plan.duration_days} días</span>
                            </div>
                            <span className="text-lg font-extrabold tabular-nums">
                                {clp(Number(plan.sale_price))}
                            </span>
                        </button>
                    );
                })}
                {plans.length === 0 && (
                    <p className="p-6 text-center text-sm text-muted-foreground">
                        No hay planes activos.
                    </p>
                )}
            </div>
        </div>
    );
}
