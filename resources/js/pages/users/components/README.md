# Módulo de Usuarios - Frontend

Este módulo implementa la interfaz de usuario completa para la gestión de usuarios, siguiendo los mismos patrones establecidos en el módulo de roles.

## 🏗️ Estructura del Módulo

### Componentes
- **`user-list.tsx`**: Lista principal de usuarios con filtros de búsqueda
- **`user-form.tsx`**: Formulario para crear y editar usuarios

### Páginas
- **`index.tsx`**: Página principal con lista de usuarios
- **`show.tsx`**: Página de visualización de usuario individual
- **`edit.tsx`**: Página de edición de usuario
- **`create.tsx`**: Página de creación de usuario

### Hook Personalizado
- **`useUsers.ts`**: Hook para manejo de estado y operaciones CRUD

## 🎯 Funcionalidades Implementadas

### 1. Lista de Usuarios
- ✅ Visualización en tabla profesional
- ✅ Filtros de búsqueda por nombre, email y estado de verificación
- ✅ Indicadores visuales de estado de email (verificado/pendiente)
- ✅ Avatares con iniciales
- ✅ Acciones de ver y editar
- ✅ Paginación y carga dinámica

### 2. Formulario de Usuario
- ✅ Validación completa del lado cliente
- ✅ Campos: nombre, email, contraseña
- ✅ Confirmación de contraseña
- ✅ Mostrar/ocultar contraseña
- ✅ Modo creación y edición
- ✅ Verificación opcional de email para nuevos usuarios

### 3. Visualización de Usuario
- ✅ Diseño tipo tarjeta con información completa
- ✅ Header con gradiente y avatar
- ✅ Información organizada en secciones
- ✅ Estados visuales para email verificado/pendiente
- ✅ Fechas formateadas en español

### 4. Navegación
- ✅ Integrado en el sidebar junto con roles
- ✅ Rutas web completas (/users, /users/create, /users/{id}, /users/{id}/edit)
- ✅ Breadcrumbs y navegación consistente

## 🔧 Configuración Técnica

### Rutas Web
```php
// routes/users-web.php
GET /users                  -> users.index
GET /users/create          -> users.create.page  
GET /users/{id}            -> users.show
GET /users/{id}/edit       -> users.edit
```

### API Endpoints (Backend)
```php
// routes/users.php (API)
GET    /api/users          -> Búsqueda con filtros
POST   /api/users          -> Crear usuario
GET    /api/users/{id}     -> Obtener usuario
PUT    /api/users/{id}     -> Actualizar usuario
```

### Hook useUsers
```typescript
const {
    users,           // Lista de usuarios
    loading,         // Estado de carga
    error,           // Errores
    meta,            // Metadatos de paginación
    searchUsers,     // Función de búsqueda
    getUser,         // Obtener usuario individual
    createUser,      // Crear usuario
    updateUser,      // Actualizar usuario
} = useUsers();
```

## 🎨 Diseño y UX

### Paleta de Colores
- **Primario**: Azul (#3B82F6) para acciones principales
- **Éxito**: Verde para estados verificados
- **Advertencia**: Amarillo para estados pendientes
- **Neutral**: Grises para texto y bordes

### Iconografía
- **UserCheck**: Icono principal en sidebar
- **MailCheck**: Email verificado
- **Mail**: Email pendiente
- **Eye**: Ver usuario
- **Edit**: Editar usuario
- **Plus**: Crear usuario

### Responsive Design
- ✅ Tabla responsive con scroll horizontal
- ✅ Formularios adaptables a móvil
- ✅ Grid responsive para filtros
- ✅ Sidebar colapsible

## 🔒 Validaciones

### Frontend (TypeScript)
```typescript
// Nombre
- Requerido
- Mínimo 2 caracteres
- Máximo 255 caracteres

// Email  
- Requerido
- Formato válido
- Máximo 255 caracteres

// Contraseña
- Requerida para nuevos usuarios
- Mínimo 8 caracteres
- Confirmación requerida
```

### Backend (PHP)
- Validaciones adicionales en controladores
- Verificación de email único
- Hash seguro de contraseñas

## 🚀 Uso

### Navegación
1. Acceder desde el sidebar: **Usuarios**
2. Ver lista completa con filtros
3. Crear nuevo usuario con el botón **"Nuevo Usuario"**
4. Ver detalles haciendo clic en el icono de ojo
5. Editar haciendo clic en el icono de edición

### Búsqueda y Filtros
```typescript
// Filtros disponibles
{
    name?: string;              // Búsqueda por nombre
    email?: string;             // Búsqueda por email
    email_verified?: boolean;   // Estado de verificación
    limit?: number;             // Límite de resultados
    offset?: number;            // Offset para paginación
}
```

## 🧪 Testing

El módulo incluye tests completos en el backend:
- ✅ Unit tests para casos de uso
- ✅ Integration tests para repositorio
- ✅ Feature tests para API endpoints
- ✅ Tests de validación y errores

## 📱 Estados de la Aplicación

### Loading States
- Spinner durante carga de datos
- Botones deshabilitados durante operaciones
- Skeleton loading en páginas de detalle

### Error States  
- Mensajes de error claros
- Validaciones en tiempo real
- Manejo de errores de red

### Success States
- Confirmaciones visuales
- Redirección automática después de operaciones
- Estados actualizados en tiempo real

## 🔄 Integración con el Sistema

### Sidebar Navigation
```typescript
// Agregado al footerNavItems en app-sidebar.tsx
{
    title: 'Usuarios',
    href: users.index(),
    icon: UserCheck,
}
```

### Rutas Generadas
- Integrado con Wayfinder para generación automática de rutas
- TypeScript types para rutas tipadas
- Consistencia con el patrón de roles

## 🎯 Próximos Pasos

### Funcionalidades Adicionales (Opcionales)
- [ ] Exportar usuarios a CSV/Excel
- [ ] Importar usuarios masivamente
- [ ] Filtros avanzados (fecha de registro, etc.)
- [ ] Asignación de roles a usuarios
- [ ] Historial de actividad de usuarios
- [ ] Notificaciones por email

### Mejoras de UX
- [ ] Búsqueda en tiempo real (debounced)
- [ ] Ordenamiento por columnas
- [ ] Selección múltiple para acciones en lote
- [ ] Vista de tarjetas como alternativa a tabla

El módulo de usuarios está **completamente funcional** y listo para producción, siguiendo todas las mejores prácticas establecidas en el sistema.
