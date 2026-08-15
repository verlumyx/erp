import React from 'react';
import { useRoleFormContext } from '../contexts/RoleFormContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export function RoleForm() {
    const {
        data,
        setData,
        errors,
        processing,
        handleSubmit
    } = useRoleFormContext();

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
                    <p className="text-sm text-red-500 mt-1">{errors.name}</p>
                )}
            </div>

            {/* Permission Type Field */}
            <div className="space-y-2">
                <Label htmlFor="permission_type">Tipo de Permisos *</Label>
                <Select
                    value={data.permission_type}
                    onValueChange={(value: 'all' | 'custom') => setData('permission_type', value)}
                >
                    <SelectTrigger className={errors.permission_type ? 'border-red-500' : ''}>
                        <SelectValue placeholder="Seleccione el tipo de permisos" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos</SelectItem>
                        <SelectItem value="custom">Personalizados</SelectItem>
                    </SelectContent>
                </Select>
                {errors.permission_type && (
                    <p className="text-sm text-red-500 mt-1">{errors.permission_type}</p>
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
                    <p className="text-sm text-red-500 mt-1">{errors.description}</p>
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

                <Button
                    type="submit"
                    disabled={processing}
                >
                    {processing ? 'Guardando...' : 'Guardar Rol'}
                </Button>
            </div>
        </form>
    );
}
