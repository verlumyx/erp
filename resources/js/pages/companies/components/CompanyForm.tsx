import { useCompanyFormContext } from '../contexts/CompanyFormContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select2, type OptionType } from '@/components/ui/select2';

const TAXPAYER_TYPE_OPTIONS: OptionType[] = [
    { value: 'ordinary', label: 'Contribuyente Ordinario' },
    { value: 'formal', label: 'Contribuyente Formal' },
    { value: 'special', label: 'Contribuyente Especial' },
];

export function CompanyForm() {
    const { data, setData, processing, errors, handleSubmit } = useCompanyFormContext();

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                    <Label htmlFor="name">Razón Social / Nombre *</Label>
                    <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="Nombre de la empresa"
                        className={errors.name ? 'border-red-500' : ''}
                        required
                    />
                    {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="rif">RIF (Registro Fiscal)</Label>
                    <Input
                        id="rif"
                        type="text"
                        value={data.rif}
                        onChange={(e) => setData('rif', e.target.value.toUpperCase())}
                        placeholder="Ej: J-12345678-0"
                        className={errors.rif ? 'border-red-500' : ''}
                    />
                    {errors.rif && <p className="text-sm text-red-500">{errors.rif}</p>}
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                    <Label htmlFor="taxpayer_type">Tipo de Contribuyente</Label>
                    <Select2
                        options={TAXPAYER_TYPE_OPTIONS}
                        value={
                            TAXPAYER_TYPE_OPTIONS.find(
                                (option) => option.value === data.taxpayer_type,
                            ) ?? TAXPAYER_TYPE_OPTIONS[0]
                        }
                        onChange={(option) =>
                            setData(
                                'taxpayer_type',
                                (option?.value ?? 'ordinary') as 'ordinary' | 'formal' | 'special',
                            )
                        }
                        error={!!errors.taxpayer_type}
                        size="md"
                    />
                    {errors.taxpayer_type && (
                        <p className="text-sm text-red-500">{errors.taxpayer_type}</p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="phone">Teléfono</Label>
                    <Input
                        id="phone"
                        type="text"
                        value={data.phone}
                        onChange={(e) => setData('phone', e.target.value)}
                        placeholder="Ej: 0212-5551234"
                        className={errors.phone ? 'border-red-500' : ''}
                    />
                    {errors.phone && <p className="text-sm text-red-500">{errors.phone}</p>}
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="fiscal_address">Domicilio Fiscal (SENIAT)</Label>
                <Input
                    id="fiscal_address"
                    type="text"
                    value={data.fiscal_address}
                    onChange={(e) => setData('fiscal_address', e.target.value)}
                    placeholder="Dirección fiscal legal completa para facturación"
                    className={errors.fiscal_address ? 'border-red-500' : ''}
                />
                {errors.fiscal_address && (
                    <p className="text-sm text-red-500">{errors.fiscal_address}</p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="address">Dirección Comercial / Física</Label>
                <Input
                    id="address"
                    type="text"
                    value={data.address}
                    onChange={(e) => setData('address', e.target.value)}
                    placeholder="Dirección física del establecimiento principal"
                    className={errors.address ? 'border-red-500' : ''}
                />
                {errors.address && (
                    <p className="text-sm text-red-500">{errors.address}</p>
                )}
            </div>

            <div className="space-y-2">
                <Label htmlFor="description">Descripción / Notas</Label>
                <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Descripción o actividad comercial de la empresa"
                    className={errors.description ? 'border-red-500' : ''}
                    rows={3}
                />
                {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
            </div>

            <div className="flex justify-end gap-3 pt-2">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => window.history.back()}
                    disabled={processing}
                >
                    Cancelar
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing ? 'Guardando...' : 'Guardar'}
                </Button>
            </div>
        </form>
    );
}
