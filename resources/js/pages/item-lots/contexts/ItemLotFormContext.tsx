import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useItemLotForm } from '../hooks/useItemLotForm';

type ItemLotFormContextType = ReturnType<typeof useItemLotForm>;

const ItemLotFormContext = createContext<ItemLotFormContextType | undefined>(
    undefined,
);

interface ItemLotFormProviderProps {
    children: ReactNode;
    value: ItemLotFormContextType;
}

export function ItemLotFormProvider({
    children,
    value,
}: ItemLotFormProviderProps) {
    return (
        <ItemLotFormContext.Provider value={value}>
            {children}
        </ItemLotFormContext.Provider>
    );
}

export function useItemLotFormContext(): ItemLotFormContextType {
    const context = useContext(ItemLotFormContext);
    if (!context) {
        throw new Error(
            'useItemLotFormContext must be used within ItemLotFormProvider',
        );
    }
    return context;
}
