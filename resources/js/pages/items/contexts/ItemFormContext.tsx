import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useItemForm } from '../hooks/useItemForm';
import type { ItemOptions } from '../types/Item';

type ItemFormContextType = ReturnType<typeof useItemForm> & {
    options: ItemOptions;
};

const ItemFormContext = createContext<ItemFormContextType | undefined>(
    undefined,
);

interface ItemFormProviderProps {
    children: ReactNode;
    value: ItemFormContextType;
}

export function ItemFormProvider({ children, value }: ItemFormProviderProps) {
    return (
        <ItemFormContext.Provider value={value}>
            {children}
        </ItemFormContext.Provider>
    );
}

export function useItemFormContext(): ItemFormContextType {
    const context = useContext(ItemFormContext);
    if (!context) {
        throw new Error(
            'useItemFormContext must be used within ItemFormProvider',
        );
    }
    return context;
}
