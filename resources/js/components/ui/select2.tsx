import { ChevronDown } from 'lucide-react';
import ReactSelect, {
    components,
    DropdownIndicatorProps,
    GroupBase,
    Props as ReactSelectProps,
    StylesConfig,
} from 'react-select';
import { cn } from '@/lib/utils';

export interface OptionType {
    value: string;
    label: string;
}

type Select2Size = 'sm' | 'md';

interface Select2Props
    extends Omit<
        ReactSelectProps<OptionType, false, GroupBase<OptionType>>,
        'classNames'
    > {
    error?: boolean;
    className?: string;
    size?: Select2Size;
}

const SIZES: Record<Select2Size, { height: string; radius: string }> = {
    sm: { height: '36px', radius: '0.375rem' },
    md: { height: '42px', radius: '10px' },
};

const DropdownIndicator = (
    props: DropdownIndicatorProps<OptionType, false, GroupBase<OptionType>>,
) => {
    return (
        <components.DropdownIndicator {...props}>
            <ChevronDown className="h-4 w-4 opacity-50" />
        </components.DropdownIndicator>
    );
};

function Select2({
    error = false,
    className,
    size = 'sm',
    ...props
}: Select2Props) {
    const { height, radius } = SIZES[size];

    const customStyles: StylesConfig<
        OptionType,
        false,
        GroupBase<OptionType>
    > = {
        control: (base, state) => {
            const getBorderColor = () => {
                if (error) {
                    return 'var(--destructive)';
                }

                if (state.isFocused) {
                    return 'var(--ring)';
                }

                return 'var(--input)';
            };

            return {
                ...base,
                minHeight: height,
                height,
                borderRadius: radius,
                borderWidth: '1px',
                borderStyle: 'solid',
                borderColor: getBorderColor(),
                backgroundColor: state.isDisabled
                    ? 'var(--muted)'
                    : 'var(--background)',
                boxShadow: state.isFocused
                    ? '0 0 0 3px color-mix(in oklab, var(--ring) 50%, transparent)'
                    : '0 1px 2px 0 rgb(0 0 0 / 0.05)',
                transition: 'all 0.2s',
                outline: 'none',
                cursor: state.isDisabled ? 'not-allowed' : 'pointer',
                opacity: state.isDisabled ? 0.6 : 1,
                '&:hover': {
                    borderColor: getBorderColor(),
                },
            };
        },
        valueContainer: (base) => ({
            ...base,
            height,
            padding: '0 0.75rem',
            display: 'flex',
            alignItems: 'center',
            flexWrap: 'nowrap',
            overflow: 'hidden',
        }),
        input: (base) => ({
            ...base,
            margin: 0,
            padding: 0,
            fontSize: '0.875rem',
            color: 'var(--foreground)',
        }),
        indicatorSeparator: () => ({
            display: 'none',
        }),
        indicatorsContainer: (base) => ({
            ...base,
            height,
        }),
        clearIndicator: (base) => ({
            ...base,
            padding: '0 0.25rem',
            color: 'var(--muted-foreground)',
            cursor: 'pointer',
        }),
        dropdownIndicator: (base) => ({
            ...base,
            padding: '0 0.5rem',
            color: 'var(--muted-foreground)',
        }),
        menu: (base) => ({
            ...base,
            backgroundColor: 'var(--popover)',
            border: '1px solid var(--border)',
            borderRadius: '0.5rem',
            boxShadow:
                '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
            marginTop: '0.25rem',
            overflow: 'hidden',
            zIndex: 9999,
        }),
        menuPortal: (base) => ({
            ...base,
            zIndex: 9999,
        }),
        menuList: (base) => ({
            ...base,
            padding: '0.25rem',
            maxHeight: '300px',
        }),
        option: (base, state) => ({
            ...base,
            backgroundColor:
                state.isSelected || state.isFocused
                    ? 'var(--accent)'
                    : 'transparent',
            color: state.isSelected
                ? 'var(--accent-foreground)'
                : 'var(--foreground)',
            fontSize: '0.875rem',
            padding: '0.5rem 0.75rem',
            borderRadius: '0.25rem',
            cursor: 'pointer',
            '&:active': {
                backgroundColor: 'var(--accent)',
            },
        }),
        placeholder: (base) => ({
            ...base,
            color: 'var(--muted-foreground)',
            fontSize: '0.875rem',
            margin: 0,
            whiteSpace: 'nowrap',
            overflow: 'hidden',
            textOverflow: 'ellipsis',
        }),
        singleValue: (base) => ({
            ...base,
            color: 'var(--foreground)',
            fontSize: '0.875rem',
            margin: 0,
        }),
        noOptionsMessage: (base) => ({
            ...base,
            color: 'var(--muted-foreground)',
            fontSize: '0.875rem',
            padding: '0.5rem 0.75rem',
        }),
    };

    return (
        <ReactSelect<OptionType, false, GroupBase<OptionType>>
            styles={customStyles}
            components={{ DropdownIndicator }}
            className={cn('react-select-container', className)}
            classNamePrefix="react-select"
            menuPortalTarget={
                typeof document !== 'undefined' ? document.body : undefined
            }
            menuPosition="fixed"
            noOptionsMessage={() => 'No hay opciones disponibles'}
            placeholder="Selecciona una opción..."
            {...props}
        />
    );
}

export { Select2 };
