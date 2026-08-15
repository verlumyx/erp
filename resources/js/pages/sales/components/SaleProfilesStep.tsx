import { Check, KeyRound } from 'lucide-react';
import { useMemo } from 'react';
import { useSaleFormContext } from '../contexts/SaleFormContext';
import type { AvailableProfile } from '../types/Sale';

/**
 * Paso 3 del wizard: elegir el/los profile(s) disponibles del servicio del plan.
 * Para capacidad "profile" se elige 1; para "full_account" se eligen N profiles
 * de una misma cuenta (N = max_profiles del servicio).
 */
export function SaleProfilesStep() {
    const {
        form,
        selectedPlan,
        requiredCount,
        serviceProfiles,
        toggleProfile,
    } = useSaleFormContext();

    const grouped = useMemo(() => {
        const map = new Map<
            string,
            { email: string; profiles: AvailableProfile[] }
        >();
        for (const profile of serviceProfiles) {
            const entry = map.get(profile.account_id) ?? {
                email: profile.account_email,
                profiles: [],
            };
            entry.profiles.push(profile);
            map.set(profile.account_id, entry);
        }
        return Array.from(map.values());
    }, [serviceProfiles]);

    if (selectedPlan === null) {
        return (
            <p className="p-6 text-center text-sm text-muted-foreground">
                Selecciona un plan en el paso anterior.
            </p>
        );
    }

    return (
        <div className="flex flex-col gap-4">
            <div className="flex items-center justify-between">
                <p className="text-sm font-medium">
                    Selecciona {requiredCount} perfil
                    {requiredCount !== 1 ? 'es' : ''}
                    {selectedPlan.capacity === 'full_account'
                        ? ' de una misma cuenta'
                        : ''}
                    .
                </p>
                <span className="text-[12.5px] font-semibold text-muted-foreground tabular-nums">
                    {form.data.profile_ids.length}/{requiredCount}
                </span>
            </div>

            {form.errors.profile_ids && (
                <p className="text-sm text-bad">{form.errors.profile_ids}</p>
            )}

            <div className="flex flex-col gap-4">
                {grouped.map((account) => (
                    <div
                        key={account.email}
                        className="overflow-hidden rounded-[12px] border"
                    >
                        <div className="flex items-center gap-2 border-b bg-muted px-4 py-2.5 text-[13px] font-bold">
                            <KeyRound className="size-3.5 text-muted-foreground" />
                            {account.email}
                        </div>
                        <div className="grid grid-cols-2 gap-2 p-3 sm:grid-cols-4">
                            {account.profiles.map((profile) => {
                                const selected = form.data.profile_ids.includes(
                                    profile.id,
                                );
                                return (
                                    <button
                                        key={profile.id}
                                        type="button"
                                        onClick={() =>
                                            toggleProfile(profile.id)
                                        }
                                        className={`flex items-center justify-between gap-2 rounded-[10px] border px-3 py-2.5 text-left text-sm transition-colors ${
                                            selected
                                                ? 'border-primary bg-primary/5 font-semibold'
                                                : 'hover:bg-muted'
                                        }`}
                                    >
                                        Perfil {profile.number}
                                        {selected && (
                                            <Check className="size-4 text-primary" />
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                ))}
                {grouped.length === 0 && (
                    <p className="p-6 text-center text-sm text-muted-foreground">
                        No hay perfiles disponibles para este servicio.
                    </p>
                )}
            </div>
        </div>
    );
}
