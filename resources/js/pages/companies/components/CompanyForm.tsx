import { useCompanyFormContext } from '../contexts/CompanyFormContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export function CompanyForm() {
    const { data, setData, processing, errors, handleSubmit } = useCompanyFormContext();

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="space-y-2">
                <Label htmlFor="name">Nombre *</Label>
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
                <Label htmlFor="description">Descripción</Label>
                <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Descripción de la empresa"
                    className={errors.description ? 'border-red-500' : ''}
                    rows={4}
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
