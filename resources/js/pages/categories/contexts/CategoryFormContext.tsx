import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useCategoryForm } from '../hooks/useCategoryForm';

type CategoryFormContextType = ReturnType<typeof useCategoryForm>;

const CategoryFormContext = createContext<CategoryFormContextType | undefined>(
    undefined,
);

interface CategoryFormProviderProps {
    children: ReactNode;
    value: CategoryFormContextType;
}

export function CategoryFormProvider({
    children,
    value,
}: CategoryFormProviderProps) {
    return (
        <CategoryFormContext.Provider value={value}>
            {children}
        </CategoryFormContext.Provider>
    );
}

export function useCategoryFormContext(): CategoryFormContextType {
    const context = useContext(CategoryFormContext);
    if (!context) {
        throw new Error(
            'useCategoryFormContext must be used within CategoryFormProvider',
        );
    }
    return context;
}
