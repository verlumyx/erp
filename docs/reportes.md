# Reportes

> **Estado: por definir.** Este archivo es una propuesta inicial para discutir el alcance.
> No hay decisiones cerradas todavía; las tablas descritas abajo son la infraestructura mínima
> que haría falta si se opta por reportes configurables.

Los reportes **no crean datos**: leen de los módulos existentes. Por eso este grupo se implementa
al final, cuando Inventario, Compras, Ventas y Logística ya generan información real.

---

## 1. Catálogo propuesto de reportes

### Inventario

| Reporte | Fuente principal | Filtros clave |
|---|---|---|
| Existencias por bodega | `app_item_stocks` | Bodega, categoría, artículo, con/sin stock |
| Valuación de inventario | `app_item_stocks` | Fecha de corte, bodega, categoría |
| Kardex por artículo | `app_inventory_movements` | Artículo, bodega, rango de fechas |
| Artículos bajo mínimo | `app_item_stocks` + `app_items` | Bodega, categoría |
| Rotación de inventario | `app_inventory_movements` | Periodo, categoría |
| Inventario sin movimiento | `app_inventory_movements` | Días sin movimiento |
| Lotes por vencer | `app_item_lots` | Días de anticipación, bodega |
| Diferencias de conteo | `app_adjustment_lines` | Periodo, bodega, tipo |

### Compras

| Reporte | Fuente principal | Filtros clave |
|---|---|---|
| Compras por proveedor | `app_purchase_invoices` | Periodo, proveedor, tipo de proveedor |
| Compras por artículo | `app_purchase_invoice_lines` | Periodo, categoría, artículo |
| Órdenes pendientes de recibir | `app_purchase_orders` | Proveedor, fecha esperada |
| Cuentas por pagar | `app_purchase_invoices` | Fecha de corte, proveedor |
| Antigüedad de saldos por pagar | `app_purchase_invoices` | Corte, rangos 0-30/31-60/61-90/90+ |
| Pagos realizados | `app_supplier_payments` | Periodo, método, proveedor |
| Anticipos pendientes de aplicar | `app_supplier_advances` | Proveedor, corte |
| Devoluciones a proveedor | `app_purchase_returns` | Periodo, motivo, proveedor |
| Evolución del costo de compra | `app_purchase_invoice_lines` | Artículo, periodo |

### Ventas

| Reporte | Fuente principal | Filtros clave |
|---|---|---|
| Ventas por cliente | `app_sales_invoices` | Periodo, cliente, tipo de cliente |
| Ventas por artículo / categoría | `app_sales_invoice_lines` | Periodo, categoría |
| Ventas por vendedor | `app_sales_invoices` | Periodo, vendedor |
| Ventas por ruta / zona | `app_sales_invoices` + `app_routes` | Periodo, ruta |
| Margen de utilidad | `app_sales_invoice_lines` | Periodo, artículo, cliente |
| Cuentas por cobrar | `app_sales_invoices` | Fecha de corte, cliente |
| Antigüedad de saldos por cobrar | `app_sales_invoices` | Corte, rangos 0-30/31-60/61-90/90+ |
| Cobros realizados | `app_client_collections` | Periodo, método, cobrador |
| Cheques pendientes / rebotados | `app_client_collections` | `check_status`, periodo |
| Clientes sin compras | `app_sales_invoices` | Días sin comprar |
| Devoluciones de venta | `app_sales_returns` | Periodo, motivo, cliente |
| Descuentos otorgados | `app_sales_invoice_lines` | Periodo, vendedor, cliente |

### Logística

| Reporte | Fuente principal | Filtros clave |
|---|---|---|
| Despachos por ruta | `app_dispatches` | Periodo, ruta, conductor |
| Cumplimiento de entregas | `app_dispatches` | Periodo, `delivery_status` |
| Entregas rechazadas | `app_dispatches` | Periodo, motivo |
| Traslados en tránsito | `app_transfers` | Bodega origen/destino |
| Diferencias en traslados | `app_transfer_lines` | Periodo, bodega |
| Productividad por ruta | `app_route_stops` | Periodo, ruta, paradas cumplidas |
| Entradas recibidas | `app_entries` | Periodo, proveedor, bodega |

### Gerenciales

| Reporte | Descripción |
|---|---|
| Tablero general | Ventas del mes, cobros, saldo por cobrar, valor de inventario, margen |
| Comparativo de periodos | Ventas y compras mes contra mes / año contra año |
| Top 20 artículos | Por venta, por margen, por rotación |
| Top 20 clientes | Por venta y por saldo pendiente |
| Flujo de caja proyectado | Cuentas por cobrar contra cuentas por pagar por vencimiento |

---

## 2. Consideraciones técnicas

- **Solo lectura.** Los reportes consultan sin bloquear ni modificar. Se recomienda una conexión de
  lectura separada si se agrega una réplica.
- **Filtro obligatorio por empresa.** Todo reporte se restringe a `company_id` de la empresa activa.
- **Rango de fechas obligatorio** en reportes transaccionales, para evitar barridos de tabla completa.
- **Permisos propios.** Cada reporte es un permiso independiente en `app_permissions` (los datos de
  costos y márgenes no deberían ser visibles para todos los roles).
- **Exportación** a Excel/CSV y PDF; las exportaciones grandes se procesan en cola (`ShouldQueue`) y se
  notifican al terminar.
- **Índices.** Los reportes más pesados (antigüedad de saldos, kardex, valuación) dependen de los
  índices ya definidos por fecha, estado y saldo en cada módulo.

---

## 3. Tablas propuestas (solo si se implementan reportes configurables)

Si los reportes se construyen fijos en código, estas tablas no son necesarias. Se plantean para el caso
en que el usuario deba poder guardar sus propias vistas y programar envíos.

`app_reports` sería tabla de módulo (con `code`, prefijo `REP`, que habría que agregar a la tabla de
prefijos del [README](README.md)). `app_report_views` y `app_report_executions` son tablas de detalle:
llevan `company_id` y `status`, pero no `code`.

### 3.1 Definición — `app_reports` — Prefijo `REP`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(150)` | No | | Nombre visible del reporte. |
| `slug` | `string(100)` | No | | Identificador técnico (`sales_by_client`). |
| `group` | `enum` | No | | `inventory`, `purchases`, `sales`, `logistics`, `management`. |
| `description` | `text` | Sí | | |
| `permission_action` | `string(100)` | No | | Acción requerida en `app_permissions`. |
| `default_filters` | `json` | Sí | | Filtros por defecto. |
| `available_columns` | `json` | Sí | | Columnas seleccionables. |
| `order` | `integer` | No | `0` | Orden en el menú. |

**Índices:** `unique(company_id, slug)`, `index(group)`.

### 3.2 Vistas guardadas — `app_report_views`

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | `uuid` | No | PK. |
| `company_id` | `uuid` | No | FK → `app_companies.id`. |
| `report_id` | `uuid` | No | FK → `app_reports.id` (`cascadeOnDelete`). |
| `user_id` | `uuid` | No | FK → `users.id` (`cascadeOnDelete`). Dueño de la vista. |
| `name` | `string(150)` | No | Nombre que le da el usuario. |
| `filters` | `json` | No | Filtros guardados. |
| `columns` | `json` | Sí | Columnas y su orden. |
| `is_shared` | `enum` | No | `yes` / `no`, default `'no'`. Visible para otros usuarios de la empresa. |
| `status` | `enum` | No | `active` / `inactive`. |

**Índices:** `unique(report_id, user_id, name)`, `index(user_id)`.

### 3.3 Ejecuciones y exportaciones — `app_report_executions`

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | `uuid` | No | PK. |
| `company_id` | `uuid` | No | FK → `app_companies.id`. |
| `report_id` | `uuid` | No | FK → `app_reports.id`. |
| `user_id` | `uuid` | No | FK → `users.id`. Quién lo ejecutó. |
| `filters` | `json` | No | Filtros usados; permite reproducir el resultado. |
| `format` | `enum` | No | `screen`, `excel`, `csv`, `pdf`. |
| `row_count` | `integer` | No | Filas devueltas. |
| `duration_ms` | `integer` | No | Tiempo de ejecución; sirve para detectar reportes lentos. |
| `file_path` | `string(500)` | Sí | Archivo generado en storage. |
| `expires_at` | `timestamp` | Sí | Cuándo se purga el archivo. |
| `status` | `enum` | No | `queued`, `processing`, `completed`, `failed`. |
| `error_message` | `text` | Sí | |
| `created_at` / `updated_at` | `timestamp` | Sí | |

**Índices:** `index(report_id)`, `index(user_id)`, `index(status)`, `index(created_at)`.

---

## 4. Pendiente de definir

- ¿Reportes fijos en código o configurables por el usuario? Determina si se necesitan las tablas de §3.
- ¿Se requiere envío programado por correo (diario/semanal)? Implicaría una tabla de suscripciones.
- ¿Hace falta un tablero con gráficas en tiempo real o basta con listados exportables?
- ¿Se necesita un cierre contable por periodo que congele los saldos de inventario y cartera?
- ¿Qué reportes deben poder consultarse en móvil (por ejemplo, cartera del vendedor en ruta)?
