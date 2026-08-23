import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useItemSerialForm } from '../hooks/useItemSerialForm';

type ItemSerialFormContextType = ReturnType<typeof useItemSerialForm>;

const ItemSerialFormContext = createContext<
    ItemSerialFormContextType | undefined
>(undefined);

interface ItemSerialFormProviderProps {
    children: ReactNode;
    value: ItemSerialFormContextType;
}

export function ItemSerialFormProvider({
    children,
    value,
}: ItemSerialFormProviderProps) {
    return (
        <ItemSerialFormContext.Provider value={value}>
            {children}
        </ItemSerialFormContext.Provider>
    );
}

export function useItemSerialFormContext(): ItemSerialFormContextType {
    const context = useContext(ItemSerialFormContext);
    if (!context) {
        throw new Error(
            'useItemSerialFormContext must be used within ItemSerialFormProvider',
        );
    }
    return context;
}
