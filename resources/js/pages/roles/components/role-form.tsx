import React from 'react';
import { useRoleFormContext } from '../contexts/RoleFormContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select2, type OptionType } from '@/components/ui/select2';

const PERMISSION_TYPE_OPTIONS: OptionType[] = [
    { value: 'all', label: 'Todos' },
    { value: 'custom', label: 'Personalizados' },
];

export function RoleForm() {
    const { data, setData, errors, processing, handleSubmit } =
        useRoleFormContext();

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            {/* Name Field */}
            <div className="space-y-2">
                <Label htmlFor="name">Nombre del Rol *</Label>
                <Input
                    id="name"
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="Ingrese el nombre del rol"
                    className={errors.name ? 'border-red-500' : ''}
                    required
                    maxLength={255}
                />
                {errors.name && (
                    <p className="mt-1 text-sm text-red-500">{errors.name}</p>
                )}
            </div>

            {/* Permission Type Field */}
            <div className="space-y-2">
                <Label htmlFor="permission_type">Tipo de Permisos *</Label>
                <Select2
                    inputId="permission_type"
                    options={PERMISSION_TYPE_OPTIONS}
                    value={
                        PERMISSION_TYPE_OPTIONS.find(
                            (option) => option.value === data.permission_type,
                        ) ?? null
                    }
                    onChange={(option) =>
                        setData(
                            'permission_type',
                            (option?.value ?? 'all') as 'all' | 'custom',
                        )
                    }
                    error={!!errors.permission_type}
                    placeholder="Seleccione el tipo de permisos"
                    isSearchable={false}
                />
                {errors.permission_type && (
                    <p className="mt-1 text-sm text-red-500">
                        {errors.permission_type}
                    </p>
                )}
            </div>

            {/* Description Field */}
            <div className="space-y-2">
                <Label htmlFor="description">Descripción</Label>
                <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Ingrese una descripción del rol (opcional)"
                    className={errors.description ? 'border-red-500' : ''}
                    maxLength={1000}
                    rows={4}
                />
                {errors.description && (
                    <p className="mt-1 text-sm text-red-500">
                        {errors.description}
                    </p>
                )}
            </div>

            {/* Actions */}
            <div className="flex justify-end space-x-4 pt-4">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => window.history.back()}
                    disabled={processing}
                >
                    Cancelar
                </Button>

                <Button type="submit" disabled={processing}>
                    {processing ? 'Guardando...' : 'Guardar Rol'}
                </Button>
            </div>
        </form>
    );
}
