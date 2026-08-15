# Logística

Movimiento físico de la mercancía. Todos los documentos de este grupo **afectan inventario** y generan
movimientos en el kardex (`app_inventory_movements`).

> Las columnas base (`id`, `company_id`, `code`, `status`, `created_by`, `created_at`, `updated_at`),
> los índices base, la estructura común de las tablas `*_lines`, los tipos numéricos y la política de
> no borrado están descritos en [README.md](README.md) y no se repiten aquí.

| Módulo | Tabla | Prefijo | Efecto en kardex |
|---|---|---|---|
| Despachos | `app_dispatches` + `app_dispatch_lines` | `DES` | `out` |
| Traslados | `app_transfers` + `app_transfer_lines` | `TRA` | `transfer_out` / `transfer_in` |
| Entradas | `app_entries` + `app_entry_lines` | `ENT` | `in` |
| Rutas | `app_routes` (+ `app_route_stops`, `app_route_clients`) | `RUT` | — |
| Ajustes | `app_adjustments` + `app_adjustment_lines` | `AJU` | `adjustment_in` / `adjustment_out` |

**Depende de:** [Inventario](inventario.md) (artículos, bodegas, existencias), [Ventas](ventas.md)
(órdenes de venta) y [Compras](compras.md) (órdenes de compra).

---

## 1. Despachos

Salida física de mercancía hacia el cliente. Descarga inventario y libera la reserva de la orden de venta.

### 1.1 Cabecera — `app_dispatches` — Prefijo `DES`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `client_id` | `uuid` | No | | FK → `app_clients.id` (`restrictOnDelete`). |
| `sales_order_id` | `uuid` | Sí | | FK → `app_sales_orders.id`. Pedido que se despacha. |
| `client_address_id` | `uuid` | Sí | | FK → `app_client_addresses.id`. Dirección de entrega. |
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id` (`restrictOnDelete`). Bodega de origen. |
| `route_id` | `uuid` | Sí | | FK → `app_routes.id` (`nullOnDelete`). Ruta asignada. |
| `route_stop_id` | `uuid` | Sí | | FK → `app_route_stops.id`. Parada específica. |
| `dispatch_date` | `date` | No | | Fecha de salida. |
| `delivery_date` | `date` | Sí | | Fecha efectiva de entrega. |
| `driver_id` | `uuid` | Sí | | FK → `users.id` (`nullOnDelete`). Conductor. |
| `vehicle_plate` | `string(20)` | Sí | | Placa del vehículo. |
| `carrier` | `string(150)` | Sí | | Transportista externo. |
| `tracking_number` | `string(60)` | Sí | | Guía de transporte. |
| `freight_amount` | `decimal(18,2)` | No | `0` | Costo del flete. |
| `total_quantity` | `decimal(18,4)` | No | `0` | Suma de cantidades despachadas. |
| `total_weight` | `decimal(18,4)` | No | `0` | Peso total; para planificación de carga. |
| `total_volume` | `decimal(18,4)` | No | `0` | Volumen total. |
| `total_cost` | `decimal(18,2)` | No | `0` | Costo de la mercancía despachada. |
| `delivery_status` | `enum` | No | `'pending'` | `pending`, `in_transit`, `delivered`, `partial_delivered`, `rejected`, `returned`. |
| `received_by_name` | `string(150)` | Sí | | Nombre de quien recibe. |
| `received_by_document` | `string(30)` | Sí | | Identificación de quien recibe. |
| `signature_path` | `string(500)` | Sí | | Firma digital capturada en la entrega. |
| `evidence_path` | `string(500)` | Sí | | Foto de la entrega. |
| `latitude` / `longitude` | `decimal(10,7)` | Sí | | Georreferencia de la entrega. |
| `rejection_reason` | `string(500)` | Sí | | Motivo si el cliente rechaza. |
| `cancelled_at` | `timestamp` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `index(client_id)`, `index(sales_order_id)`, `index(dispatch_date)`, `index(route_id)`,
`index(driver_id)`, `index(delivery_status)`, `index(warehouse_id)`.

### 1.2 Líneas — `app_dispatch_lines`

Además de las columnas comunes de línea (los importes son informativos: el despacho no factura):

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `sales_order_line_id` | `uuid` | Sí | | FK → `app_sales_order_lines.id`. |
| `lot_id` | `uuid` | Sí | | FK → `app_item_lots.id`. Obligatorio si el artículo maneja lote. |
| `serial_id` | `uuid` | Sí | | FK → `app_item_serials.id`. |
| `location_id` | `uuid` | Sí | | Ubicación desde la que se toma. |
| `delivered_quantity` | `decimal(18,4)` | No | `0` | Cantidad efectivamente recibida por el cliente. |
| `returned_quantity` | `decimal(18,4)` | No | `0` | Cantidad devuelta en el mismo viaje. |
| `unit_cost` | `decimal(18,6)` | No | `0` | Costo unitario al momento de la salida. |

**Reglas**
- Al confirmar: genera movimientos `out`, descuenta `quantity` y libera `reserved_quantity`.
- Si `delivered_quantity < quantity`, la diferencia reingresa a bodega con un movimiento `in`
  y el despacho queda en `partial_delivered`.
- Un despacho rechazado completo (`rejected`) reingresa toda la mercancía.
- Se factura después (`app_sales_invoices.dispatch_id`), con `affects_inventory = false`.

---

## 2. Traslados

Movimiento de mercancía entre bodegas de la misma empresa. **No cambia el valor total del inventario**,
solo su ubicación.

### 2.1 Cabecera — `app_transfers` — Prefijo `TRA`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `origin_warehouse_id` | `uuid` | No | | FK → `app_warehouses.id` (`restrictOnDelete`). |
| `destination_warehouse_id` | `uuid` | No | | FK → `app_warehouses.id` (`restrictOnDelete`). |
| `transit_warehouse_id` | `uuid` | Sí | | FK → `app_warehouses.id`. Bodega de tránsito si el traslado no es inmediato. |
| `transfer_date` | `date` | No | | Fecha de salida. |
| `expected_date` | `date` | Sí | | Fecha estimada de llegada. |
| `received_date` | `date` | Sí | | Fecha efectiva de recepción. |
| `reason` | `enum` | No | `'restock'` | `restock` (reabastecimiento), `rebalance`, `damaged`, `quarantine`, `other`. |
| `reason_detail` | `string(500)` | Sí | | |
| `driver_id` | `uuid` | Sí | | FK → `users.id`. |
| `vehicle_plate` | `string(20)` | Sí | | |
| `route_id` | `uuid` | Sí | | FK → `app_routes.id`. |
| `total_quantity` | `decimal(18,4)` | No | `0` | |
| `total_cost` | `decimal(18,2)` | No | `0` | Valor trasladado. |
| `transfer_status` | `enum` | No | `'pending'` | `pending`, `in_transit`, `received`, `partial_received`. |
| `sent_by` | `uuid` | Sí | | FK → `users.id`. Quién despachó desde origen. |
| `received_by` | `uuid` | Sí | | FK → `users.id`. Quién recibió en destino. |
| `cancelled_at` | `timestamp` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` (salida) → `partial` → `completed` (recibido), o `cancelled`.

**Índices:** `index(origin_warehouse_id)`, `index(destination_warehouse_id)`, `index(transfer_date)`,
`index(transfer_status)`, `index(driver_id)`.

### 2.2 Líneas — `app_transfer_lines`

Además de las columnas comunes de línea:

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `origin_location_id` | `uuid` | Sí | | Ubicación de origen. |
| `destination_location_id` | `uuid` | Sí | | Ubicación de destino. |
| `lot_id` | `uuid` | Sí | | FK → `app_item_lots.id`. |
| `serial_id` | `uuid` | Sí | | FK → `app_item_serials.id`. |
| `sent_quantity` | `decimal(18,4)` | No | `0` | Cantidad enviada. |
| `received_quantity` | `decimal(18,4)` | No | `0` | Cantidad recibida en destino. |
| `difference_quantity` | `decimal(18,4)` | No | `0` | `sent_quantity - received_quantity`. Faltante en tránsito. |
| `unit_cost` | `decimal(18,6)` | No | `0` | Costo con el que viaja la mercancía. |

**Reglas**
- Traslado en **dos pasos**: al confirmar genera `transfer_out` de la bodega origen hacia tránsito;
  al recibir genera `transfer_in` en la bodega destino. En un solo paso, ambos movimientos son simultáneos.
- El costo unitario **viaja con la mercancía**: el destino recibe al costo del origen, no al suyo.
- Origen y destino no pueden ser la misma bodega.
- Una diferencia (`difference_quantity ≠ 0`) exige un Ajuste que la justifique antes de cerrar el traslado.

---

## 3. Entradas

Recepción física de mercancía en bodega. Su origen habitual es una orden de compra, pero también
cubre entradas sin documento previo (producción, donación, hallazgo).

### 3.1 Cabecera — `app_entries` — Prefijo `ENT`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `supplier_id` | `uuid` | Sí | | FK → `app_suppliers.id` (`restrictOnDelete`). Nulo si no viene de un proveedor. |
| `purchase_order_id` | `uuid` | Sí | | FK → `app_purchase_orders.id`. Orden que se recibe. |
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id` (`restrictOnDelete`). Bodega de recepción. |
| `entry_date` | `date` | No | | Fecha de recepción. |
| `entry_type` | `enum` | No | `'purchase'` | `purchase`, `production`, `return`, `donation`, `initial` (inventario inicial), `other`. |
| `supplier_document` | `string(60)` | Sí | | Remisión o guía del proveedor. |
| `carrier` | `string(150)` | Sí | | Transportista. |
| `tracking_number` | `string(60)` | Sí | | |
| `received_by` | `uuid` | Sí | | FK → `users.id`. Quién recibió. |
| `inspected_by` | `uuid` | Sí | | FK → `users.id`. Quién hizo control de calidad. |
| `inspection_status` | `enum` | No | `'pending'` | `pending`, `approved`, `rejected`, `partial`. |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `total_quantity` | `decimal(18,4)` | No | `0` | |
| `freight_amount` | `decimal(18,2)` | No | `0` | Flete a prorratear al costo. |
| `other_charges` | `decimal(18,2)` | No | `0` | Otros gastos capitalizables (aduana, seguro). |
| `total_cost` | `decimal(18,2)` | No | `0` | Valor total ingresado. |
| `is_invoiced` | `boolean` | No | `false` | `true` cuando ya existe factura de compra asociada. |
| `cancelled_at` | `timestamp` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `index(supplier_id)`, `index(purchase_order_id)`, `index(entry_date)`,
`index(warehouse_id)`, `index(entry_type)`, `index(is_invoiced)`.

### 3.2 Líneas — `app_entry_lines`

Además de las columnas comunes de línea:

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `purchase_order_line_id` | `uuid` | Sí | | FK → `app_purchase_order_lines.id`. |
| `location_id` | `uuid` | Sí | | Ubicación donde se almacena. |
| `lot_number` | `string(60)` | Sí | | Lote del proveedor; crea `app_item_lots` si no existe. |
| `lot_id` | `uuid` | Sí | | FK → `app_item_lots.id`. |
| `expires_at` | `date` | Sí | | Vencimiento del lote recibido. |
| `serial_numbers` | `json` | Sí | | Series recibidas; crea `app_item_serials`. |
| `received_quantity` | `decimal(18,4)` | No | `0` | Cantidad aceptada. |
| `rejected_quantity` | `decimal(18,4)` | No | `0` | Cantidad rechazada en inspección. |
| `unit_cost` | `decimal(18,6)` | No | `0` | Costo antes de prorrateos. |
| `landed_cost` | `decimal(18,6)` | No | `0` | Costo final con flete y gastos prorrateados. |
| `rejection_reason` | `string(500)` | Sí | | |

**Reglas**
- Al confirmar genera movimientos `in` con `landed_cost` y recalcula el costo promedio del artículo.
- El prorrateo de `freight_amount` y `other_charges` se distribuye por valor de línea (por defecto)
  o por peso, según configuración.
- La cantidad recibida no puede superar lo pendiente de la orden de compra sin autorización
  (permiso `allow_over_receipt`).
- `entry_type = initial` es el mecanismo de carga del inventario inicial; solo se permite una vez
  por artículo/bodega.
- `rejected_quantity` no ingresa a stock: se registra para el reclamo al proveedor.

---

## 4. Rutas

Recorridos de entrega y cobro. Agrupan clientes y ordenan las paradas para despachos y cobranza.

### 4.1 Cabecera — `app_routes` — Prefijo `RUT`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(150)` | No | | Nombre de la ruta ("Zona Norte - Lunes"). |
| `description` | `text` | Sí | | |
| `type` | `enum` | No | `'delivery'` | `delivery` (entrega), `collection` (cobranza), `sales` (preventa), `mixed`. |
| `warehouse_id` | `uuid` | Sí | | FK → `app_warehouses.id`. Bodega de salida. |
| `driver_id` | `uuid` | Sí | | FK → `users.id` (`nullOnDelete`). Conductor asignado. |
| `salesperson_id` | `uuid` | Sí | | FK → `users.id`. Vendedor asignado. |
| `vehicle_plate` | `string(20)` | Sí | | |
| `vehicle_capacity_weight` | `decimal(18,4)` | No | `0` | Capacidad en peso. |
| `vehicle_capacity_volume` | `decimal(18,4)` | No | `0` | Capacidad en volumen. |
| `frequency` | `enum` | No | `'weekly'` | `daily`, `weekly`, `biweekly`, `monthly`, `on_demand`. |
| `weekdays` | `json` | Sí | | Días de ejecución (`["mon","wed","fri"]`). |
| `zone` | `string(100)` | Sí | | Zona geográfica. |
| `city` | `string(100)` | Sí | | |
| `estimated_duration_minutes` | `integer` | No | `0` | Duración estimada del recorrido. |
| `estimated_distance_km` | `decimal(10,2)` | No | `0` | Distancia estimada. |
| `notes` | `text` | Sí | | |

**Índices:** `unique(company_id, name)`, `index(type)`, `index(driver_id)`, `index(warehouse_id)`, `index(zone)`.

### 4.2 Paradas — `app_route_stops`

Ejecución de la ruta en una fecha concreta.

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `id` | `uuid` | No | | PK. |
| `company_id` | `uuid` | No | | FK → `app_companies.id`. |
| `route_id` | `uuid` | No | | FK → `app_routes.id` (`cascadeOnDelete`). |
| `client_id` | `uuid` | No | | FK → `app_clients.id` (`restrictOnDelete`). |
| `client_address_id` | `uuid` | Sí | | FK → `app_client_addresses.id`. |
| `stop_date` | `date` | No | | Fecha planificada de la parada. |
| `sequence` | `integer` | No | `0` | Orden de visita. |
| `estimated_arrival` | `time` | Sí | | Hora estimada de llegada. |
| `actual_arrival` | `datetime` | Sí | | Hora real registrada. |
| `actual_departure` | `datetime` | Sí | | |
| `stop_status` | `enum` | No | `'pending'` | `pending`, `arrived`, `completed`, `skipped`, `failed`. |
| `skip_reason` | `string(500)` | Sí | | Motivo si no se visitó (cerrado, no recibió). |
| `latitude` / `longitude` | `decimal(10,7)` | Sí | | Ubicación al registrar la visita. |
| `status` | `enum` | No | `'active'` | |
| `created_at` / `updated_at` | `timestamp` | Sí | | |

**Índices:** `unique(route_id, stop_date, client_id)`, `index(stop_date)`, `index(client_id)`,
`index(stop_status)`, `index(sequence)`.

### 4.3 Clientes de la ruta — `app_route_clients`

Asignación fija de clientes a una ruta (plantilla desde la que se generan las paradas).

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | `uuid` | No | PK. |
| `route_id` | `uuid` | No | FK → `app_routes.id` (`cascadeOnDelete`). |
| `client_id` | `uuid` | No | FK → `app_clients.id` (`cascadeOnDelete`). |
| `client_address_id` | `uuid` | Sí | FK → `app_client_addresses.id`. |
| `sequence` | `integer` | No | Orden habitual de visita. |
| `status` | `enum` | No | `active` / `inactive`. |

**Índices:** `unique(route_id, client_id, client_address_id)`.

**Reglas**
- Las paradas de una fecha se generan a partir de `app_route_clients` más los despachos pendientes
  asignados a la ruta.
- Al planificar se valida que el peso y volumen de los despachos no superen la capacidad del vehículo.
- No se puede desactivar una ruta con paradas o despachos pendientes.

---

## 5. Ajustes

Corrección de existencias por conteo físico, merma, daño, vencimiento o error de captura.
Es el **único** módulo que modifica stock sin un documento comercial detrás, y por eso exige motivo
y autorización.

### 5.1 Cabecera — `app_adjustments` — Prefijo `AJU`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id` (`restrictOnDelete`). |
| `adjustment_date` | `date` | No | | |
| `type` | `enum` | No | `'physical_count'` | `physical_count` (conteo físico), `loss` (merma), `damage`, `expiration`, `theft`, `correction`, `revaluation` (solo costo), `other`. |
| `direction` | `enum` | No | `'mixed'` | `in` (solo aumentos), `out` (solo disminuciones), `mixed`. |
| `reason` | `string(500)` | No | | Justificación obligatoria. |
| `count_id` | `string(60)` | Sí | | Identificador del conteo físico asociado. |
| `total_quantity_in` | `decimal(18,4)` | No | `0` | Suma de aumentos. |
| `total_quantity_out` | `decimal(18,4)` | No | `0` | Suma de disminuciones. |
| `total_cost_in` | `decimal(18,2)` | No | `0` | Valor de los aumentos. |
| `total_cost_out` | `decimal(18,2)` | No | `0` | Valor de las disminuciones. |
| `net_cost` | `decimal(18,2)` | No | `0` | `total_cost_in - total_cost_out`. Impacto en el valor del inventario. |
| `approved_by` | `uuid` | Sí | | FK → `users.id`. Obligatorio para confirmar. |
| `approved_at` | `timestamp` | Sí | | |
| `cancelled_at` | `timestamp` | Sí | | |
| `cancellation_reason` | `string(500)` | Sí | | |
| `attachment_path` | `string(500)` | Sí | | Acta de conteo o evidencia. |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `pending_approval` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `index(warehouse_id)`, `index(adjustment_date)`, `index(type)`, `index(approved_by)`.

### 5.2 Líneas — `app_adjustment_lines`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `id` | `uuid` | No | | PK. |
| `adjustment_id` | `uuid` | No | | FK → `app_adjustments.id` (`cascadeOnDelete`). |
| `line_number` | `integer` | No | | |
| `item_id` | `uuid` | No | | FK → `app_items.id` (`restrictOnDelete`). |
| `measurement_unit_id` | `uuid` | No | | FK → `app_measurement_units.id`. |
| `location_id` | `uuid` | Sí | | FK → `app_warehouse_locations.id`. |
| `lot_id` | `uuid` | Sí | | FK → `app_item_lots.id`. |
| `serial_id` | `uuid` | Sí | | FK → `app_item_serials.id`. |
| `system_quantity` | `decimal(18,4)` | No | `0` | Existencia según el sistema al momento del conteo. |
| `counted_quantity` | `decimal(18,4)` | No | `0` | Existencia física contada. |
| `difference_quantity` | `decimal(18,4)` | No | `0` | `counted_quantity - system_quantity`. Positivo = sobrante. |
| `base_quantity` | `decimal(18,4)` | No | `0` | Diferencia convertida a unidad base. |
| `movement_type` | `enum` | No | | `adjustment_in` o `adjustment_out`, derivado del signo. |
| `unit_cost` | `decimal(18,6)` | No | `0` | Costo aplicado al ajuste. |
| `total_cost` | `decimal(18,2)` | No | `0` | `abs(base_quantity) * unit_cost`. |
| `reason` | `string(500)` | Sí | | Motivo específico de la línea. |
| `counted_by` | `uuid` | Sí | | FK → `users.id`. Quién contó. |
| `notes` | `string(500)` | Sí | | |
| `created_at` / `updated_at` | `timestamp` | Sí | | |

**Índices:** `index(adjustment_id)`, `index(item_id)`, `unique(adjustment_id, line_number)`,
`index(lot_id)`.

**Reglas**
- `system_quantity` se captura al **crear** la línea y se revalida al confirmar: si el stock cambió
  entre ambos momentos, el sistema avisa y exige recontar.
- Las salidas usan el costo promedio actual; las entradas por sobrante también, salvo en
  `type = revaluation`, donde el usuario define el nuevo costo.
- Un ajuste confirmado **no se edita**: se anula (genera movimientos inversos) y se crea uno nuevo.
- Requiere aprobación de un usuario con permiso `approve` distinto del creador cuando `net_cost`
  supera el umbral configurado.

---

## Diagrama del grupo

```
Compras                      Logística                     Inventario
─────────                    ─────────                     ──────────
app_purchase_orders ──> app_entries ──────────────┐
                                                  │
Ventas                                            ├──> app_inventory_movements
app_sales_orders ─────> app_dispatches ───────────┤         (kardex)
                             │                    │              │
app_routes ──> app_route_stops                    │              v
       └────> app_route_clients                   │      app_item_stocks
                                                  │
                        app_transfers ────────────┤
                        app_adjustments ──────────┘
```
