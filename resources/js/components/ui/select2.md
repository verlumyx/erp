# Select2 Component

Componente Select2 basado en **react-select** con estilos personalizados que coinciden con el diseño de shadcn/ui.

## 📦 Instalación

```bash
npm install react-select
```

## 🎯 Características

- ✅ **Búsqueda**: Permite buscar opciones escribiendo
- ✅ **Clearable**: Opción para limpiar la selección
- ✅ **Estilos personalizados**: Coincide con el tema de shadcn/ui
- ✅ **Manejo de errores**: Soporte para mostrar estados de error
- ✅ **TypeScript**: Completamente tipado
- ✅ **Accesibilidad**: Soporte completo de ARIA

## 📝 Uso Básico

```tsx
import { Select2, OptionType } from '@/components/ui/select2';

const options: OptionType[] = [
    { value: '1', label: 'Opción 1' },
    { value: '2', label: 'Opción 2' },
    { value: '3', label: 'Opción 3' },
];

function MyComponent() {
    const [selectedOption, setSelectedOption] = React.useState<OptionType | null>(null);

    return (
        <Select2
            options={options}
            value={selectedOption}
            onChange={(option) => setSelectedOption(option)}
            placeholder="Selecciona una opción..."
        />
    );
}
```

## 🔧 Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `options` | `OptionType[]` | `[]` | Array de opciones disponibles |
| `value` | `OptionType \| null` | `null` | Opción seleccionada |
| `onChange` | `(option: OptionType \| null) => void` | - | Callback cuando cambia la selección |
| `error` | `boolean` | `false` | Muestra el estado de error |
| `placeholder` | `string` | `"Selecciona una opción..."` | Texto del placeholder |
| `isClearable` | `boolean` | `false` | Permite limpiar la selección |
| `isSearchable` | `boolean` | `true` | Permite buscar opciones |
| `isDisabled` | `boolean` | `false` | Deshabilita el select |
| `size` | `'sm' \| 'md'` | `'sm'` | Alto del control: `sm` = 36px (filtros de listado), `md` = 42px y radio 10px (campos de formulario) |
| `inputId` | `string` | - | Id del input interno; úsalo para enlazar el `<Label htmlFor>` |
| `className` | `string` | - | Clases CSS adicionales |

> El menú se renderiza en un portal sobre `document.body` con `menuPosition="fixed"`,
> así no lo recorta ningún contenedor con `overflow` (tablas, tarjetas, diálogos).

## 📚 Ejemplos

### Ejemplo 1: Select Simple

```tsx
import { Select2, OptionType } from '@/components/ui/select2';
import { Label } from '@/components/ui/label';

const roleOptions: OptionType[] = [
    { value: 'admin', label: 'Administrador' },
    { value: 'user', label: 'Usuario' },
    { value: 'guest', label: 'Invitado' },
];

function RoleSelector() {
    const [role, setRole] = React.useState<OptionType | null>(null);

    return (
        <div className="space-y-2">
            <Label htmlFor="role">Rol</Label>
            <Select2
                id="role"
                options={roleOptions}
                value={role}
                onChange={setRole}
                placeholder="Selecciona un rol"
            />
        </div>
    );
}
```

### Ejemplo 2: Select con Búsqueda y Limpieza

```tsx
<Select2
    options={options}
    value={selectedOption}
    onChange={setSelectedOption}
    placeholder="Busca y selecciona..."
    isClearable
    isSearchable
/>
```

### Ejemplo 3: Select con Manejo de Errores

```tsx
import { Select2, OptionType } from '@/components/ui/select2';
import { Label } from '@/components/ui/label';

function FormWithValidation() {
    const [selectedOption, setSelectedOption] = React.useState<OptionType | null>(null);
    const [error, setError] = React.useState<string>('');

    const handleSubmit = () => {
        if (!selectedOption) {
            setError('Este campo es requerido');
            return;
        }
        setError('');
        // Procesar formulario...
    };

    return (
        <div className="space-y-2">
            <Label htmlFor="option">Opción *</Label>
            <Select2
                id="option"
                options={options}
                value={selectedOption}
                onChange={(option) => {
                    setSelectedOption(option);
                    setError('');
                }}
                error={!!error}
                placeholder="Selecciona una opción"
            />
            {error && (
                <p className="text-sm text-red-500 mt-1">{error}</p>
            )}
        </div>
    );
}
```

### Ejemplo 4: Uso en Formularios con Context API

```tsx
import { Select2, OptionType } from '@/components/ui/select2';
import { useUserFormContext } from '../contexts/UserFormContext';

interface Role {
    id: string;
    name: string;
}

interface UserFormProps {
    roles: Role[];
}

export function UserForm({ roles }: UserFormProps) {
    const { data, setData, errors } = useUserFormContext();

    // Convertir roles a formato de opciones
    const roleOptions: OptionType[] = roles.map(role => ({
        value: role.id,
        label: role.name
    }));

    // Encontrar la opción seleccionada
    const selectedRole = roleOptions.find(option => option.value === data.role_id) || null;

    return (
        <div className="space-y-2">
            <Label htmlFor="role_id">Rol *</Label>
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
    );
}
```

### Ejemplo 5: Select Deshabilitado

```tsx
<Select2
    options={options}
    value={selectedOption}
    onChange={setSelectedOption}
    isDisabled
    placeholder="Este select está deshabilitado"
/>
```

## 🎨 Personalización de Estilos

El componente usa estilos personalizados que coinciden con el tema de shadcn/ui. Los estilos se adaptan automáticamente al modo claro/oscuro usando variables CSS.

### Variables CSS Utilizadas

- `--input`: Color del borde
- `--ring`: Color del foco
- `--destructive`: Color de error
- `--popover`: Color de fondo del menú
- `--accent`: Color de selección
- `--foreground`: Color del texto
- `--muted-foreground`: Color del texto secundario

## 🔍 Características Avanzadas

### Búsqueda

Por defecto, el componente permite buscar opciones escribiendo. Puedes deshabilitarlo con `isSearchable={false}`.

### Limpieza

Puedes permitir que el usuario limpie la selección con `isClearable={true}`.

### Mensajes Personalizados

El componente incluye mensajes en español:
- "No hay opciones disponibles" cuando no hay opciones
- "Selecciona una opción..." como placeholder por defecto

## 📦 Tipo OptionType

```typescript
export interface OptionType {
    value: string;
    label: string;
}
```

## 🚀 Comparación con Select de shadcn/ui

| Característica | Select (shadcn/ui) | Select2 (react-select) |
|----------------|-------------------|------------------------|
| Búsqueda | ❌ No | ✅ Sí |
| Clearable | ❌ No | ✅ Sí |
| Multi-select | ❌ No | ✅ Sí (configurable) |
| Async loading | ❌ No | ✅ Sí (configurable) |
| Estilos | ✅ Nativo | ✅ Personalizado |
| Tamaño | ✅ Pequeño | ⚠️ Más grande |

## 💡 Cuándo Usar

Select2 es el select estándar de la aplicación: todos los formularios y filtros
de listado lo usan. El `Select` de shadcn/ui (`@/components/ui/select`) ya no se
usa en ninguna pantalla.

- Campos de formulario: `size="md"` y `error={!!errors.campo}`.
- Filtros de listado: tamaño por defecto; añade `isSearchable={false}` cuando el
  filtro tiene pocas opciones fijas (estado, sí/no).

## 🐛 Troubleshooting

### El select no se ve correctamente

Asegúrate de que las variables CSS de shadcn/ui estén definidas en tu archivo CSS global.

### Los estilos no coinciden con el tema

Verifica que estés usando las variables CSS correctas en `customStyles`.

### TypeScript muestra errores

Asegúrate de importar el tipo `OptionType` desde el componente.

## 📚 Recursos

- [react-select Documentation](https://react-select.com/)
- [shadcn/ui Documentation](https://ui.shadcn.com/)

