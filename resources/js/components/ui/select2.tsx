import * as React from "react"
import ReactSelect, { 
    Props as ReactSelectProps, 
    GroupBase,
    StylesConfig,
    components,
    DropdownIndicatorProps
} from 'react-select'
import { cn } from "@/lib/utils"
import { ChevronDown } from "lucide-react"

export interface OptionType {
    value: string;
    label: string;
}

interface Select2Props extends Omit<ReactSelectProps<OptionType, false, GroupBase<OptionType>>, 'classNames'> {
    error?: boolean;
    className?: string;
}

const DropdownIndicator = (props: DropdownIndicatorProps<OptionType, false, GroupBase<OptionType>>) => {
    return (
        <components.DropdownIndicator {...props}>
            <ChevronDown className="h-4 w-4 opacity-50" />
        </components.DropdownIndicator>
    );
};

function Select2({
    error = false,
    className,
    ...props
}: Select2Props) {
    // Obtener los valores CSS computados
    const rootStyles = getComputedStyle(document.documentElement);
    const inputColor = rootStyles.getPropertyValue('--input').trim();
    const backgroundColor = rootStyles.getPropertyValue('--background').trim();
    const popoverColor = rootStyles.getPropertyValue('--popover').trim();
    const cardColor = rootStyles.getPropertyValue('--card').trim();
    const ringColor = rootStyles.getPropertyValue('--ring').trim();
    const destructiveColor = rootStyles.getPropertyValue('--destructive').trim();
    const borderColor = rootStyles.getPropertyValue('--border').trim();

    // Usar popover o card para el menú (más sólido)
    const menuBackground = popoverColor || cardColor || backgroundColor;

    const customStyles: StylesConfig<OptionType, false, GroupBase<OptionType>> = {
        control: (base, state) => {
            const getBorderColor = () => {
                if (error) return destructiveColor.includes('oklch') ? destructiveColor : `hsl(${destructiveColor})`;
                if (state.isFocused) return ringColor.includes('oklch') ? ringColor : `hsl(${ringColor})`;
                return inputColor.includes('oklch') ? inputColor : `hsl(${inputColor})`;
            };

            return {
                minHeight: '36px',
                height: '36px',
                borderRadius: '0.375rem',
                borderWidth: '1px',
                borderStyle: 'solid',
                borderColor: getBorderColor(),
                backgroundColor: backgroundColor.includes('oklch') ? backgroundColor : `hsl(${backgroundColor})`,
                boxShadow: state.isFocused
                    ? `0 0 0 3px ${ringColor.includes('oklch') ? ringColor.replace(')', ' / 0.5)') : `hsl(${ringColor} / 0.5)`}`
                    : '0 1px 2px 0 rgb(0 0 0 / 0.05)',
                transition: 'all 0.2s',
                outline: 'none',
                cursor: 'pointer',
                display: 'flex',
                flexWrap: 'wrap',
                justifyContent: 'space-between',
                position: 'relative',
                '&:hover': {
                    borderColor: getBorderColor(),
                },
            };
        },
        valueContainer: (base) => ({
            height: '36px',
            padding: '0 0.75rem',
            display: 'flex',
            alignItems: 'center',
            flex: 1,
            flexWrap: 'wrap',
            position: 'relative',
            overflow: 'hidden',
        }),
        input: (base) => ({
            margin: '0',
            padding: '0',
            fontSize: '0.875rem',
            color: `hsl(${rootStyles.getPropertyValue('--foreground').trim()})`,
            gridArea: '1 / 1 / 2 / 3',
            gridTemplateColumns: '0 min-content',
        }),
        indicatorSeparator: () => ({
            display: 'none',
        }),
        indicatorsContainer: (base) => ({
            height: '36px',
            display: 'flex',
            alignItems: 'center',
        }),
        dropdownIndicator: (base) => ({
            padding: '0 0.5rem',
            color: `hsl(${rootStyles.getPropertyValue('--muted-foreground').trim()})`,
            display: 'flex',
            alignItems: 'center',
        }),
        menu: (base) => ({
            backgroundColor: menuBackground.includes('oklch') ? menuBackground : `hsl(${menuBackground})`,
            border: `1px solid ${borderColor.includes('oklch') ? borderColor : `hsl(${borderColor})`}`,
            borderRadius: '0.5rem',
            boxShadow: '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
            marginTop: '0.5rem',
            zIndex: 9999,
            position: 'absolute',
            width: '100%',
        }),
        menuList: (base) => ({
            padding: '0.25rem',
            backgroundColor: menuBackground.includes('oklch') ? menuBackground : `hsl(${menuBackground})`,
            maxHeight: '300px',
            overflowY: 'auto',
        }),
        option: (base, state) => {
            const accentColor = rootStyles.getPropertyValue('--accent').trim();
            const accentForeground = rootStyles.getPropertyValue('--accent-foreground').trim();
            const foregroundColor = rootStyles.getPropertyValue('--foreground').trim();

            const getBackgroundColor = () => {
                if (state.isSelected || state.isFocused) {
                    return accentColor.includes('oklch') ? accentColor : `hsl(${accentColor})`;
                }
                return menuBackground.includes('oklch') ? menuBackground : `hsl(${menuBackground})`;
            };

            return {
                backgroundColor: getBackgroundColor(),
                color: state.isSelected
                    ? (accentForeground.includes('oklch') ? accentForeground : `hsl(${accentForeground})`)
                    : (foregroundColor.includes('oklch') ? foregroundColor : `hsl(${foregroundColor})`),
                fontSize: '0.875rem',
                padding: '0.5rem 0.75rem',
                borderRadius: '0.25rem',
                cursor: 'pointer',
                '&:active': {
                    backgroundColor: accentColor.includes('oklch') ? accentColor : `hsl(${accentColor})`,
                },
            };
        },
        placeholder: (base) => ({
            color: `hsl(${rootStyles.getPropertyValue('--muted-foreground').trim()})`,
            fontSize: '0.875rem',
            margin: 0,
            position: 'absolute',
        }),
        singleValue: (base) => ({
            color: `hsl(${rootStyles.getPropertyValue('--foreground').trim()})`,
            fontSize: '0.875rem',
            margin: 0,
            position: 'absolute',
        }),
        noOptionsMessage: (base) => ({
            color: `hsl(${rootStyles.getPropertyValue('--muted-foreground').trim()})`,
            fontSize: '0.875rem',
            padding: '0.5rem 0.75rem',
        }),
    };

    return (
        <ReactSelect<OptionType, false, GroupBase<OptionType>>
            styles={customStyles}
            components={{ DropdownIndicator }}
            className={cn("react-select-container", className)}
            classNamePrefix="react-select"
            noOptionsMessage={() => "No hay opciones disponibles"}
            placeholder="Selecciona una opción..."
            {...props}
        />
    );
}

export { Select2 }

