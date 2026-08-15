import { createContext, useContext, type ReactNode } from 'react';
import type { SaleFormState } from '../hooks/useSaleForm';
import type { AvailableProfile, ClientOption, PlanOption } from '../types/Sale';

interface SaleFormContextValue extends SaleFormState {
    companyId: string;
    clients: ClientOption[];
    plans: PlanOption[];
    availableProfiles: AvailableProfile[];
}

const SaleFormContext = createContext<SaleFormContextValue | null>(null);

interface SaleFormProviderProps {
    value: SaleFormContextValue;
    children: ReactNode;
}

export function SaleFormProvider({ value, children }: SaleFormProviderProps) {
    return (
        <SaleFormContext.Provider value={value}>
            {children}
        </SaleFormContext.Provider>
    );
}

/**
 * Acceso al estado del wizard de venta. Los componentes de paso lo consumen en
 * lugar de recibir props directamente.
 */
export function useSaleFormContext(): SaleFormContextValue {
    const context = useContext(SaleFormContext);

    if (context === null) {
        throw new Error(
            'useSaleFormContext debe usarse dentro de un SaleFormProvider.',
        );
    }

    return context;
}
