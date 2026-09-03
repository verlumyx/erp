import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useStoreItemForm } from '../hooks/useStoreItemForm';

type StoreItemFormContextType = ReturnType<typeof useStoreItemForm>;

const StoreItemFormContext = createContext<
    StoreItemFormContextType | undefined
>(undefined);

interface StoreItemFormProviderProps {
    children: ReactNode;
    value: StoreItemFormContextType;
}

export function StoreItemFormProvider({
    children,
    value,
}: StoreItemFormProviderProps) {
    return (
        <StoreItemFormContext.Provider value={value}>
            {children}
        </StoreItemFormContext.Provider>
    );
}

export function useStoreItemFormContext(): StoreItemFormContextType {
    const context = useContext(StoreItemFormContext);
    if (!context) {
        throw new Error(
            'useStoreItemFormContext must be used within StoreItemFormProvider',
        );
    }
    return context;
}
