# Inventario

Núcleo del ERP: define **qué** se compra, se vende y se mueve (artículos), **dónde** está (bodegas),
**cuánto** hay (existencias) y **cómo** llegó a estar así (kardex).

> Las columnas base (`id`, `company_id`, `code`, `status`, `created_by`, `created_at`, `updated_at`),
> los índices base y la política de no borrado están descritos en [README.md](README.md) y no se repiten aquí.

| Módulo | Tabla | Prefijo |
|---|---|---|
| Catálogo de artículos | `app_items` (+ detalle: `app_item_units`, `app_item_taxes`, `app_item_warehouses`) | `ART` |
| Bodega | `app_warehouses` | `BOD` |
| Ubicaciones | `app_warehouse_locations` | `UBI` |
| Lotes | `app_item_lots` | `LOT` |
| Series | `app_item_serials` | `SER` |
| Existencias | `app_item_stocks` (tabla derivada, sin `code`) | — |
| Kardex | `app_inventory_movements` | `MOV` |

**Depende de:** [Catálogo](catalogo.md) (categorías, unidades, impuestos, listas de precio).

---

## 1. Catálogo de artículos

Maestro de productos y servicios. Es el registro más referenciado del sistema.

**Tabla:** `app_items` — **Prefijo:** `ART`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `sku` | `string(60)` | No | | Código interno del artículo definido por el usuario. |
| `barcode` | `string(60)` | Sí | | Código de barras principal (EAN/UPC). |
| `name` | `string(200)` | No | | Nombre comercial. |
| `description` | `text` | Sí | | Descripción larga. |
| `type` | `enum` | No | `'product'` | `product` (inventariable), `service` (no afecta stock), `kit` (compuesto). |
| `category_id` | `uuid` | Sí | | FK → `app_categories.id` (`nullOnDelete`). |
| `measurement_unit_id` | `uuid` | No | | FK → `app_measurement_units.id` (`restrictOnDelete`). **Unidad base** del artículo; todo el stock se guarda en ella. |
| `purchase_unit_id` | `uuid` | Sí | | FK → `app_measurement_units.id`. Unidad sugerida en compras. |
| `sale_unit_id` | `uuid` | Sí | | FK → `app_measurement_units.id`. Unidad sugerida en ventas. |
| `sale_tax_id` | `uuid` | Sí | | FK → `app_taxes.id`. Impuesto por defecto en venta. |
| `purchase_tax_id` | `uuid` | Sí | | FK → `app_taxes.id`. Impuesto por defecto en compra. |
| `cost_method` | `enum` | No | `'average'` | `average` (promedio ponderado), `fifo`, `standard`. Método de valuación. |
| `standard_cost` | `decimal(18,6)` | No | `0` | Costo estándar (si `cost_method = standard`). |
| `average_cost` | `decimal(18,6)` | No | `0` | Costo promedio actual; lo recalcula cada entrada. Solo lectura para el usuario. |
| `last_purchase_cost` | `decimal(18,6)` | No | `0` | Último costo de compra registrado. |
| `base_price` | `decimal(18,6)` | No | `0` | Precio de venta base cuando no hay lista aplicable. |
| `min_price` | `decimal(18,6)` | No | `0` | Precio mínimo permitido; bloquea descuentos excesivos. |
| `is_inventoriable` | `enum` | No | `'yes'` | `no` para servicios: no genera movimientos de stock. |
| `is_purchasable` | `enum` | No | `'yes'` | Puede aparecer en documentos de compra. |
| `is_sellable` | `enum` | No | `'yes'` | Puede aparecer en documentos de venta. |
| `tracks_lot` | `enum` | No | `'no'` | Exige lote en cada movimiento. |
| `tracks_serial` | `enum` | No | `'no'` | Exige número de serie unitario. |
| `tracks_expiration` | `enum` | No | `'no'` | Exige fecha de vencimiento; implica `tracks_lot`. |
| `min_stock` | `decimal(18,4)` | No | `0` | Punto de reorden global. |
| `max_stock` | `decimal(18,4)` | No | `0` | Existencia máxima sugerida. |
| `reorder_quantity` | `decimal(18,4)` | No | `0` | Cantidad sugerida a comprar al llegar al mínimo. |
| `weight` | `decimal(18,4)` | No | `0` | Peso unitario en la unidad base de peso. |
| `volume` | `decimal(18,4)` | No | `0` | Volumen unitario. |
| `default_warehouse_id` | `uuid` | Sí | | FK → `app_warehouses.id` (`nullOnDelete`). Bodega sugerida. |
| `image_path` | `string(500)` | Sí | | Ruta de la imagen en storage. |
| `notes` | `text` | Sí | | |

**Índices:** `unique(company_id, sku)`, `unique(company_id, barcode)`, `index(name)`,
`index(category_id)`, `index(type)`, `index(is_sellable)`, `index(is_purchasable)`.

**Reglas**
- `sku` es obligatorio y único por empresa; `code` (`ART000001`) es el secuencial automático adicional.
- Cambiar `measurement_unit_id` está bloqueado si el artículo ya tiene movimientos.
- `average_cost` y `last_purchase_cost` **nunca** se editan a mano: los actualiza el proceso de entrada.
- Un artículo `type = service` fuerza `is_inventoriable = 'no'`.
- No se puede desactivar si tiene existencia distinta de cero o documentos abiertos.

### 1.1 Unidades alternativas — `app_item_units`

Permite comprar en caja y vender en unidad para un mismo artículo.

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `id` | `uuid` | No | | PK. |
| `company_id` | `uuid` | Sí | | FK → `app_companies.id`. Heredado del artículo. |
| `item_id` | `uuid` | No | | FK → `app_items.id` (`cascadeOnDelete`). |
| `measurement_unit_id` | `uuid` | No | | FK → `app_measurement_units.id`. |
| `conversion_factor` | `decimal(18,8)` | No | `1` | Unidades base que contiene 1 de esta unidad (p. ej. 1 `cja` = 12 `un`). |
| `barcode` | `string(60)` | Sí | | Código de barras propio del empaque. |
| `is_purchase_default` | `enum` | No | `'no'` | |
| `is_sale_default` | `enum` | No | `'no'` | |
| `status` | `enum` | No | `'active'` | `active` / `inactive`. |
| `created_at` / `updated_at` | `timestamp` | Sí | | |

**Índices:** `unique(item_id, measurement_unit_id)`, `index(barcode)`, `index(company_id)`, `index(status)`.

> La conversión vive aquí y no en `app_measurement_units`, porque el contenido de una caja cambia de
> producto en producto.

### 1.2 Impuestos adicionales — `app_item_taxes`

Pivot para artículos con más de un impuesto (p. ej. IVA + impuesto específico).

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | `uuid` | No | PK. |
| `company_id` | `uuid` | Sí | FK → `app_companies.id`. Heredado del artículo. |
| `item_id` | `uuid` | No | FK → `app_items.id` (`cascadeOnDelete`). |
| `tax_id` | `uuid` | No | FK → `app_taxes.id` (`restrictOnDelete`). |
| `scope` | `enum` | No | `sale`, `purchase`. |
| `order` | `integer` | No | Orden de aplicación (impuestos en cascada). |
| `status` | `enum` | No | `active` / `inactive`. |
| `created_at` / `updated_at` | `timestamp` | Sí | |

**Índices:** `unique(item_id, tax_id, scope)`, `index(company_id)`, `index(status)`.

### 1.3 Parámetros por bodega — `app_item_warehouses`

Mínimos y máximos específicos por bodega, que sobrescriben los del artículo.

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `id` | `uuid` | No | | PK. |
| `company_id` | `uuid` | Sí | | FK → `app_companies.id`. Heredado del artículo. |
| `item_id` | `uuid` | No | | FK → `app_items.id` (`cascadeOnDelete`). |
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id` (`cascadeOnDelete`). |
| `min_stock` | `decimal(18,4)` | No | `0` | |
| `max_stock` | `decimal(18,4)` | No | `0` | |
| `default_location_id` | `uuid` | Sí | | FK → `app_warehouse_locations.id`. Ubicación sugerida. |
| `status` | `enum` | No | `'active'` | `active` / `inactive`. |
| `created_at` / `updated_at` | `timestamp` | Sí | | |

**Índices:** `unique(item_id, warehouse_id)`, `index(company_id)`, `index(status)`.

---

## 2. Bodega

Lugares físicos o lógicos donde se almacena inventario.

**Tabla:** `app_warehouses` — **Prefijo:** `BOD`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(150)` | No | | Nombre de la bodega. |
| `type` | `enum` | No | `'main'` | `main` (principal), `branch` (sucursal), `transit` (tránsito entre bodegas), `quarantine` (cuarentena/mercancía retenida), `virtual` (consignación, en ruta). |
| `address` | `string(500)` | Sí | | |
| `phone` | `string(30)` | Sí | | |
| `city` | `string(100)` | Sí | | |
| `responsible_user_id` | `uuid` | Sí | | FK → `users.id` (`nullOnDelete`). Encargado de la bodega. |
| `is_default` | `enum` | No | `'no'` | Bodega sugerida en documentos. Solo una por empresa. |
| `allows_negative_stock` | `enum` | No | `'no'` | Permite salidas sin existencia suficiente. |
| `uses_locations` | `enum` | No | `'no'` | Habilita ubicaciones internas (pasillo/estante). |
| `is_sales_available` | `enum` | No | `'yes'` | Su stock cuenta como disponible para venta. |
| `notes` | `text` | Sí | | |

**Índices:** `unique(company_id, name)`, `index(type)`, `index(is_default)`, `index(responsible_user_id)`.

**Reglas**
- Las bodegas `transit` son destino temporal de los traslados en camino; su stock no está disponible para venta.
- No se puede desactivar una bodega con existencia distinta de cero o documentos pendientes.

### 2.1 Ubicaciones — `app_warehouse_locations` — Prefijo `UBI`

Solo aplica si la bodega tiene `uses_locations = 'yes'`. Es tabla de módulo: lleva las columnas base
(`company_id`, `code`, `status`, `created_by`, timestamps).

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id` (`restrictOnDelete`). |
| `parent_id` | `uuid` | Sí | | FK → `app_warehouse_locations.id`. Jerarquía zona → pasillo → estante → posición. |
| `name` | `string(100)` | No | | |
| `location_code` | `string(50)` | No | | Código físico rotulado en el estante (`A-01-03`). Distinto de `code` (`UBI000001`), que es el correlativo del sistema. |
| `type` | `enum` | No | `'shelf'` | `zone`, `aisle`, `shelf`, `bin`. |
| `capacity` | `decimal(18,4)` | No | `0` | Capacidad máxima. |

**Índices:** `unique(warehouse_id, location_code)`, `index(parent_id)`, `index(warehouse_id)`.

---

## 3. Existencias

Saldo actual por artículo/bodega. Es una tabla **derivada**: siempre debe cuadrar contra la suma del
kardex. No se edita manualmente, solo mediante Ajustes.

**Tabla:** `app_item_stocks` (sin `code`; no es un módulo de captura)

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `id` | `uuid` | No | | PK. |
| `company_id` | `uuid` | No | | FK → `app_companies.id`. |
| `item_id` | `uuid` | No | | FK → `app_items.id` (`restrictOnDelete`). |
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id` (`restrictOnDelete`). |
| `location_id` | `uuid` | Sí | | FK → `app_warehouse_locations.id`. |
| `lot_id` | `uuid` | Sí | | FK → `app_item_lots.id`. Segrega el saldo por lote. |
| `quantity` | `decimal(18,4)` | No | `0` | Existencia física en unidad base. |
| `reserved_quantity` | `decimal(18,4)` | No | `0` | Comprometida por órdenes de venta confirmadas. |
| `incoming_quantity` | `decimal(18,4)` | No | `0` | En camino por órdenes de compra o traslados. |
| `available_quantity` | `decimal(18,4)` | No | `0` | `quantity - reserved_quantity`. Columna calculada/mantenida. |
| `average_cost` | `decimal(18,6)` | No | `0` | Costo promedio en esa bodega. |
| `total_value` | `decimal(18,2)` | No | `0` | `quantity * average_cost`. |
| `last_movement_at` | `timestamp` | Sí | | Fecha del último movimiento. |
| `status` | `enum` | No | `'active'` | `active` / `inactive`. Un saldo en cero de un artículo retirado se desactiva, no se borra. |
| `created_at` / `updated_at` | `timestamp` | Sí | | |

**Índices:** `unique(company_id, item_id, warehouse_id, location_id, lot_id)`, `index(item_id)`,
`index(warehouse_id)`, `index(quantity)`.

**Reglas**
- Toda actualización ocurre dentro de la transacción del documento que la origina, con `lockForUpdate()`.
- Si `allows_negative_stock = 'no'` en la bodega, se rechaza cualquier salida que deje `quantity < 0`.

### 3.1 Lotes — `app_item_lots` — Prefijo `LOT`

Tabla de módulo: lleva las columnas base (`company_id`, `code`, `status`, `created_by`, timestamps).

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `item_id` | `uuid` | No | FK → `app_items.id` (`restrictOnDelete`). |
| `lot_number` | `string(60)` | No | Número de lote del proveedor o interno. |
| `manufactured_at` | `date` | Sí | Fecha de fabricación. |
| `expires_at` | `date` | Sí | Fecha de vencimiento. |
| `supplier_id` | `uuid` | Sí | FK → `app_suppliers.id`. Origen del lote. |
| `status` | `enum` | No | `active`, `blocked` (retenido), `expired`. |

**Índices:** `unique(company_id, item_id, lot_number)`, `index(expires_at)`, `index(status)`.

**Reglas:** la salida por defecto usa FEFO (vence primero, sale primero) para artículos con `tracks_expiration`.

### 3.2 Series — `app_item_serials` — Prefijo `SER`

Tabla de módulo: lleva las columnas base (`company_id`, `code`, `status`, `created_by`, timestamps).

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `item_id` | `uuid` | No | FK → `app_items.id` (`restrictOnDelete`). |
| `serial_number` | `string(100)` | No | Número de serie único. |
| `lot_id` | `uuid` | Sí | FK → `app_item_lots.id`. |
| `warehouse_id` | `uuid` | Sí | Bodega donde se encuentra. |
| `status` | `enum` | No | `available`, `reserved`, `sold`, `returned`, `scrapped`. |
| `sold_at` | `timestamp` | Sí | Fecha de salida definitiva. |

**Índices:** `unique(company_id, item_id, serial_number)`, `index(status)`, `index(warehouse_id)`.

---

## 4. Kardex — Movimientos de inventario

Libro mayor del inventario: **una fila por cada afectación de stock**. Es inmutable —
un error se corrige con un movimiento de contrapartida, nunca editando el original.

**Tabla:** `app_inventory_movements` — **Prefijo:** `MOV`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `movement_date` | `datetime` | No | | Fecha/hora contable del movimiento. |
| `type` | `enum` | No | | `in` (entrada), `out` (salida), `transfer_in`, `transfer_out`, `adjustment_in`, `adjustment_out`. |
| `origin_type` | `string(50)` | No | | Documento origen: `purchase_invoice`, `sales_invoice`, `dispatch`, `transfer`, `entry`, `adjustment`, `purchase_return`, `sales_return`. |
| `origin_id` | `uuid` | No | | Id del documento origen (relación polimórfica). |
| `origin_line_id` | `uuid` | Sí | | Id de la línea origen. |
| `item_id` | `uuid` | No | | FK → `app_items.id` (`restrictOnDelete`). |
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id` (`restrictOnDelete`). |
| `location_id` | `uuid` | Sí | | FK → `app_warehouse_locations.id`. |
| `lot_id` | `uuid` | Sí | | FK → `app_item_lots.id`. |
| `serial_id` | `uuid` | Sí | | FK → `app_item_serials.id`. |
| `quantity` | `decimal(18,4)` | No | | Siempre positiva; el signo lo determina `type`. |
| `unit_cost` | `decimal(18,6)` | No | `0` | Costo unitario del movimiento. |
| `total_cost` | `decimal(18,2)` | No | `0` | `quantity * unit_cost`. |
| `balance_quantity` | `decimal(18,4)` | No | `0` | Saldo del artículo/bodega **después** del movimiento. |
| `balance_cost` | `decimal(18,6)` | No | `0` | Costo promedio después del movimiento. |
| `balance_value` | `decimal(18,2)` | No | `0` | Valor del inventario después del movimiento. |
| `reversal_of_id` | `uuid` | Sí | | FK → `app_inventory_movements.id`. Movimiento que revierte (anulaciones). |
| `notes` | `string(500)` | Sí | | |

**Índices:** `index(item_id, warehouse_id, movement_date)`, `index(origin_type, origin_id)`,
`index(movement_date)`, `index(lot_id)`, `index(type)`.

**Reglas**
- Todo cambio de existencia **debe** producir un movimiento. No existe camino alterno a `app_item_stocks`.
- El movimiento y la actualización de `app_item_stocks` ocurren en la misma transacción.
- Anular un documento genera movimientos inversos con `reversal_of_id`, no borra los originales.
- `balance_*` se calcula en el momento del registro para que el kardex sea auditable sin recalcular.

---

## Diagrama de relaciones

```
app_categories        ──┐
app_measurement_units ──┼──> app_items ──┬──> app_item_units
app_taxes             ──┘                ├──> app_item_taxes
                                         ├──> app_item_prices ──> app_price_lists
                                         ├──> app_item_warehouses
                                         ├──> app_item_lots ──> app_item_serials
                                         ├──> app_item_stocks <── app_warehouses ──> app_warehouse_locations
                                         └──> app_inventory_movements
```
