import React, { useState } from 'react';
import { useUserFormContext } from '../contexts/UserFormContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, OptionType } from '@/components/ui/select2';

interface Role {
    id: string;
    name: string;
    description: string;
}

interface UserFormProps {
    roles: Role[];
}

export function UserForm({ roles }: UserFormProps) {
    const {
        data,
        setData,
        errors,
        processing,
        handleSubmit,
        emailStep,
        existingUser,
        isCheckingEmail,
        alreadyInCompany,
        handleEmailCheck,
        handleChangeEmail,
    } = useUserFormContext();

    const [emailInput, setEmailInput] = useState(data.email);

    const roleOptions: OptionType[] = roles.map((role) => ({
        value: role.id,
        label: role.name,
    }));

    const selectedRole = roleOptions.find((option) => option.value === data.role_id) || null;

    const handleContinue = async (e: React.FormEvent) => {
        e.preventDefault();
        setData('email', emailInput);
        await handleEmailCheck(emailInput);
    };

    if (emailStep) {
        return (
            <form onSubmit={handleContinue} className="space-y-6">
                <div className="space-y-2">
                    <Label htmlFor="email">Email *</Label>
                    <Input
                        id="email"
                        type="email"
                        value={emailInput}
                        onChange={(e) => setEmailInput(e.target.value)}
                        placeholder="Ingresa el email"
                        required
                        autoFocus
                    />
                    {alreadyInCompany && (
                        <p className="text-sm text-red-500 mt-1">
                            Este usuario ya tiene acceso a esta empresa.
                        </p>
                    )}
                </div>

                <div className="flex justify-end space-x-4 pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => window.history.back()}
                        disabled={isCheckingEmail}
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" disabled={isCheckingEmail || !emailInput}>
                        {isCheckingEmail ? 'Verificando...' : 'Continuar'}
                    </Button>
                </div>
            </form>
        );
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            {existingUser && (
                <div className="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Este usuario ya existe en el sistema. Al guardar, se le dará acceso a esta empresa.
                </div>
            )}

            {/* Email (read-only after step 1) */}
            <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    value={data.email}
                    disabled
                    className="bg-muted"
                />
                <button
                    type="button"
                    onClick={handleChangeEmail}
                    className="text-xs text-muted-foreground underline underline-offset-2 hover:text-foreground"
                >
                    Cambiar email
                </button>
            </div>

            {/* Name */}
            <div className="space-y-2">
                <Label htmlFor="name">Nombre *</Label>
                <Input
                    id="name"
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="Ingresa el nombre"
                    className={errors.name ? 'border-red-500' : ''}
                    disabled={!!existingUser}
                    required={!existingUser}
                />
                {errors.name && (
                    <p className="text-sm text-red-500 mt-1">{errors.name}</p>
                )}
            </div>

            {/* Password fields — only for new users */}
            {!existingUser && (
                <>
                    <div className="space-y-2">
                        <Label htmlFor="password">Contraseña *</Label>
                        <Input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder="Ingresa la contraseña"
                            className={errors.password ? 'border-red-500' : ''}
                            minLength={8}
                            required
                        />
                        {errors.password && (
                            <p className="text-sm text-red-500 mt-1">{errors.password}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password_confirmation">Confirmar Contraseña *</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            placeholder="Vuelve a ingresar la contraseña"
                            className={errors.password_confirmation ? 'border-red-500' : ''}
                            minLength={8}
                            required
                        />
                        {errors.password_confirmation && (
                            <p className="text-sm text-red-500 mt-1">{errors.password_confirmation}</p>
                        )}
                    </div>
                </>
            )}

            {/* Role */}
            <div className="space-y-2">
                <Label htmlFor="role_id">Rol</Label>
                <Select2
                    id="role_id"
                    options={roleOptions}
                    value={selectedRole}
                    onChange={(option) => setData('role_id', option?.value || '')}
                    error={!!errors.role_id}
                    placeholder="Selecciona el rol"
                    isClearable
                    isSearchable
                />
                {errors.role_id && (
                    <p className="text-sm text-red-500 mt-1">{errors.role_id}</p>
                )}
            </div>

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
                    {processing
                        ? 'Guardando...'
                        : existingUser
                          ? 'Dar Acceso'
                          : 'Guardar Usuario'}
                </Button>
            </div>
        </form>
    );
}
