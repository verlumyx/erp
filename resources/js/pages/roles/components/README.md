# Componentes de Roles

Este directorio contiene todos los componentes React relacionados con la gestión de roles.

## Componentes Disponibles

### 1. RoleForm (`role-form.tsx`)
Formulario reutilizable para crear y editar roles.

**Props:**
- `role?: Role` - Rol existente para edición (opcional)
- `onSubmit: (data: RoleFormData) => void` - Callback para envío del formulario
- `loading?: boolean` - Estado de carga
- `error?: string | null` - Mensaje de error

**Características:**
- ✅ Validación en tiempo real
- ✅ Soporte para creación y edición
- ✅ Estados de carga y error
- ✅ Campos: nombre, estado, descripción

### 2. RoleList (`role-list.tsx`)
Lista de roles con funcionalidades de búsqueda y filtrado.

**Props:**
- `roles: Role[]` - Array de roles a mostrar
- `loading?: boolean` - Estado de carga
- `error?: string | null` - Mensaje de error
- `onSearch: (filters: RoleSearchFilters) => void` - Callback para búsqueda

**Características:**
- ✅ Filtros dinámicos (nombre, estado, descripción)
- ✅ Búsqueda en tiempo real
- ✅ Acciones por rol (ver, editar) - **Sin eliminación**
- ✅ Estados vacíos y de carga
- ✅ Badges de estado visual
- ✅ Tabla responsive y profesional

## Correcciones Aplicadas

### 1. Error de SelectItem con valor vacío
**Problema:** `A <Select.Item /> must have a value prop that is not an empty string`

**Solución aplicada en `role-list.tsx`:**
```tsx
// ❌ Antes (causaba error)
<SelectItem value="">Todos</SelectItem>

// ✅ Después (corregido)
<SelectItem value="all">Todos</SelectItem>
```

### 2. Error de useRoles hook
**Problema:** `Cannot read properties of undefined (reading 'roles')`

**Solución aplicada:**
- Corregida estructura de respuesta API (`data.data.roles`)
- Agregado token CSRF
- Mejorado manejo de errores

### 3. Componente Textarea faltante
**Problema:** `Failed to resolve import "@/components/ui/textarea"`

**Solución:**
- Creado componente `Textarea` en `@/components/ui/textarea.tsx`
- Implementado con estilos consistentes del design system

### 4. Rediseño de interfaz con tabla
**Problema:** Diseño de cards no era el deseado

**Solución:**
- Creado componente `Table` completo
- Rediseñado `RoleList` con tabla responsive
- Mejorada experiencia de usuario

### 5. Eliminación de funcionalidad de borrado
**Regla de negocio:** Los roles NO se pueden eliminar del sistema

**Cambios aplicados:**
- Eliminado botón de eliminar de la interfaz
- Removido `onDelete` prop del componente `RoleList`
- Eliminado `deleteRole` del hook `useRoles`
- Removidos controladores y casos de uso de eliminación
- Eliminada ruta API `DELETE /api/roles/{id}`
- Actualizados todos los tests y documentación

## Reglas de Negocio Importantes

### ⚠️ **REGLA CRÍTICA: NO ELIMINACIÓN DE REGISTROS**

**Los roles NO se pueden eliminar del sistema por las siguientes razones:**

1. **Integridad referencial:** Los roles pueden estar asignados a usuarios
2. **Auditoría:** Se debe mantener el historial completo
3. **Consistencia:** Evita problemas de datos huérfanos
4. **Seguridad:** Previene eliminación accidental de datos críticos

**En lugar de eliminación, se debe usar:**
- **Desactivación:** Cambiar estado a `inactive`
- **Archivado:** Marcar como archivado (si se implementa)
- **Soft delete:** Solo si se especifica explícitamente

**Esta regla aplica a TODOS los módulos del sistema, salvo que se especifique lo contrario.**

## Uso de los Componentes

### Ejemplo de RoleForm
```tsx
import { RoleForm } from '@/components/roles/role-form';

function CreateRolePage() {
  const { createRole, loading, error } = useRoles();

  const handleSubmit = (data: RoleFormData) => {
    createRole(data);
  };

  return (
    <RoleForm
      onSubmit={handleSubmit}
      loading={loading}
      error={error}
    />
  );
}
```

### Ejemplo de RoleList
```tsx
import { RoleList } from '@/components/roles/role-list';

function RolesIndexPage() {
  const { roles, loading, error, searchRoles } = useRoles();

  return (
    <RoleList
      roles={roles}
      loading={loading}
      error={error}
      onSearch={searchRoles}
    />
  );
}
```

## Dependencias

### UI Components
- `@/components/ui/button`
- `@/components/ui/input`
- `@/components/ui/label`
- `@/components/ui/textarea` ✅ **Nuevo**
- `@/components/ui/select`
- `@/components/ui/card`
- `@/components/ui/badge`
- `@/components/ui/table` ✅ **Nuevo**
  - `Table`, `TableHeader`, `TableBody`, `TableRow`, `TableHead`, `TableCell`

### Icons
- `lucide-react` (Eye, Edit, Trash2, Search, Plus)

### Hooks
- `@/hooks/useRoles` - Hook personalizado para operaciones de roles

### Types
- `@/types/role` - Tipos TypeScript para roles

## Estados y Validaciones

### Estados del Formulario
- **loading**: Muestra indicadores de carga
- **error**: Muestra mensajes de error
- **success**: Redirección automática tras éxito

### Validaciones
- **Nombre**: Requerido, 2-255 caracteres
- **Estado**: Opcional, valores válidos: 'active', 'inactive'
- **Descripción**: Opcional, máximo 1000 caracteres

### Estados de la Lista
- **empty**: Mensaje cuando no hay roles
- **loading**: Spinner de carga
- **error**: Mensaje de error
- **filtered**: Resultados de búsqueda

## Navegación

Los componentes están integrados con las siguientes rutas:
- `/roles` - Lista de roles
- `/roles/create` - Crear rol
- `/roles/{id}/edit` - Editar rol
- `/roles/{id}` - Ver rol

## Accesibilidad

- ✅ Labels apropiados para todos los inputs
- ✅ Estados de focus visibles
- ✅ Mensajes de error descriptivos
- ✅ Navegación por teclado
- ✅ Contraste de colores adecuado
