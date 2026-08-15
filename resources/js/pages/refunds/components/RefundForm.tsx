import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
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
import { useRefundFormContext } from '../contexts/RefundFormContext';
import type { Refund, RefundableSale } from '../types/Refund';

interface RefundFormProps {
    /** Ventas candidatas (solo en modo creación). */
    sales?: RefundableSale[];
    /** Reembolso en edición (para mostrar la venta inmutable). */
    refund?: Refund;
    onCancel: () => void;
}

export function RefundForm({ sales = [], refund, onCancel }: RefundFormProps) {
    const { data, setData, processing, errors, handleSubmit, mode } =
        useRefundFormContext();

    const handleSaleChange = (saleId: string) => {
        setData('sale_id', saleId);
        const sale = sales.find((s) => s.id === saleId);
        if (sale && !data.amount) {
            setData('amount', Number(sale.price));
        }
    };

    return (
        <form onSubmit={handleSubmit} className="flex flex-col gap-5">
            <div className="flex flex-col gap-1.5">
                <Label htmlFor="sale_id">Venta *</Label>
                {mode === 'create' ? (
                    <Select
                        value={data.sale_id}
                        onValueChange={handleSaleChange}
                    >
                        <SelectTrigger
                            id="sale_id"
                            className={`h-[42px] rounded-[10px] ${errors.sale_id ? 'border-bad' : ''}`}
                        >
                            <SelectValue placeholder="Selecciona una venta" />
                        </SelectTrigger>
                        <SelectContent>
                            {sales.map((sale) => (
                                <SelectItem key={sale.id} value={sale.id}>
                                    {sale.code} · {sale.client_name ?? '—'} ·{' '}
                                    {clp(Number(sale.price))}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                ) : (
                    <div className="rounded-[10px] border bg-muted px-3 py-2.5 text-sm font-semibold">
                        {refund?.sale?.code ?? '—'} ·{' '}
                        {refund?.client?.name ?? '—'}
                    </div>
                )}
                {errors.sale_id && (
                    <p className="text-sm text-bad">{errors.sale_id}</p>
                )}
            </div>

            <div className="flex flex-col gap-1.5">
                <Label htmlFor="amount">Monto *</Label>
                <CurrencyInput
                    id="amount"
                    min={0}
                    decimals={2}
                    value={data.amount}
                    onValueChange={(value) => setData('amount', value)}
                    className={`h-[42px] rounded-[10px] ${errors.amount ? 'border-bad' : ''}`}
                />
                {errors.amount && (
                    <p className="text-sm text-bad">{errors.amount}</p>
                )}
            </div>

            <div className="flex flex-col gap-1.5">
                <Label htmlFor="reason">Razón</Label>
                <Textarea
                    id="reason"
                    value={data.reason}
                    onChange={(e) => setData('reason', e.target.value)}
                    rows={3}
                    maxLength={255}
                    className="rounded-[10px]"
                    placeholder="Motivo del reembolso"
                />
                {errors.reason && (
                    <p className="text-sm text-bad">{errors.reason}</p>
                )}
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
