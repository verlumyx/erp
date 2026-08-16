# Ventas

Ciclo comercial completo: desde el registro del cliente hasta el cobro y las devoluciones.

> Las columnas base (`id`, `company_id`, `code`, `status`, `created_by`, `created_at`, `updated_at`),
> los índices base, la estructura común de las tablas `*_lines`, los tipos numéricos y la política de
> no borrado están descritos en [README.md](README.md) y no se repiten aquí.

| Módulo | Tabla | Prefijo |
|---|---|---|
| Clientes | `app_clients` (+ `app_client_contacts`, `app_client_addresses`) | `CLI` |
| Órdenes de venta | `app_sales_orders` + `app_sales_order_lines` | `OVE` |
| Facturas de venta | `app_sales_invoices` + `app_sales_invoice_lines` | `FVE` |
| Notas de crédito a cliente | `app_sales_credit_notes` + `app_sales_credit_note_lines` | `NCC` |
| Anticipos de clientes | `app_client_advances` | `ANC` |
| Cobros a clientes | `app_client_collections` + `app_client_collection_applications` | `COB` |
| Devoluciones de ventas | `app_sales_returns` + `app_sales_return_lines` | `DVV` |

**Depende de:** [Catálogo](catalogo.md) (tipo de cliente, listas de precio, impuestos) e
[Inventario](inventario.md) (artículos, bodegas, existencias).

**Flujo:** Orden de venta → Despacho → Factura de venta → Cobro
(con Anticipos, Notas de crédito y Devoluciones como ramas del mismo ciclo).

---

## 1. Clientes

**La tabla `app_clients` ya existe** (módulo `app/Modules/Client`) con `id`, `company_id`, `code`,
`name`, `phone`, `email`, `status`, `notes`, `created_by` y timestamps. Este módulo la **extiende**
con la información comercial necesaria para facturar.

**Tabla:** `app_clients` — **Prefijo:** `CLI`

### Columnas actuales

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `name` | `string(150)` | No | Nombre del cliente. |
| `phone` | `string(30)` | Sí | |
| `email` | `string(255)` | Sí | Único por empresa. |
| `notes` | `text` | Sí | |

### Columnas a agregar

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `client_type_id` | `uuid` | Sí | | FK → `app_client_types.id` (`nullOnDelete`). Solo clasificación; no afecta precios ni crédito. |
| `price_list_id` | `uuid` | Sí | | FK → `app_price_lists.id` (`nullOnDelete`). Lista asignada al cliente. |
| `legal_name` | `string(200)` | Sí | | Razón social para facturar. |
| `tax_id` | `string(30)` | Sí | | Identificación fiscal. |
| `person_type` | `enum` | No | `'individual'` | `individual` o `company`. |
| `mobile` | `string(30)` | Sí | | |
| `address` | `string(500)` | Sí | | Dirección fiscal. |
| `city` | `string(100)` | Sí | | |
| `state` | `string(100)` | Sí | | |
| `country` | `string(100)` | Sí | | |
| `currency` | `string(3)` | No | `'USD'` | Moneda habitual de venta. |
| `payment_term_days` | `integer` | No | `0` | Días de crédito. `0` = contado. |
| `credit_limit` | `decimal(18,2)` | No | `0` | Cupo máximo. `0` = sin crédito. |
| `credit_blocked` | `enum` | No | `'no'` | Bloquea nuevas ventas a crédito. |
| `current_balance` | `decimal(18,2)` | No | `0` | Saldo por cobrar. Derivado. |
| `advance_balance` | `decimal(18,2)` | No | `0` | Anticipos recibidos y no aplicados. |
| `discount_percent` | `decimal(7,4)` | No | `0` | Descuento fijo del cliente. |
| `default_tax_id` | `uuid` | Sí | | FK → `app_taxes.id`. |
| `withholding_tax_id` | `uuid` | Sí | | FK → `app_taxes.id`. Retención que practica el cliente. |
| `salesperson_id` | `uuid` | Sí | | FK → `users.id` (`nullOnDelete`). Vendedor asignado. |
| `route_id` | `uuid` | Sí | | FK → `app_routes.id` (`nullOnDelete`). Ruta de entrega habitual. |
| `default_warehouse_id` | `uuid` | Sí | | FK → `app_warehouses.id`. Bodega desde la que se despacha. |
| `latitude` | `decimal(10,7)` | Sí | | Georreferencia para rutas. |
| `longitude` | `decimal(10,7)` | Sí | | |

**Índices adicionales:** `unique(company_id, tax_id)`, `index(client_type_id)`, `index(price_list_id)`,
`index(salesperson_id)`, `index(route_id)`, `index(current_balance)`.

**Reglas**
- Resolución del precio de venta: precio del artículo en `client.price_list_id` → `item.base_price`.
  El tipo de cliente no interviene.
- `current_balance` y `advance_balance` son derivados; los mantiene el sistema al confirmar documentos.
- Si `current_balance + total del pedido > credit_limit`, la venta a crédito se bloquea salvo
  autorización explícita (permiso `override_credit_limit`).
- No se puede desactivar un cliente con saldo distinto de cero o documentos abiertos.

### 1.1 Contactos — `app_client_contacts`

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | `uuid` | No | PK. |
| `company_id` | `uuid` | Sí | FK → `app_companies.id`. Heredado del cliente. |
| `client_id` | `uuid` | No | FK → `app_clients.id` (`cascadeOnDelete`). |
| `name` | `string(150)` | No | |
| `position` | `string(100)` | Sí | |
| `email` | `string(255)` | Sí | |
| `phone` | `string(30)` | Sí | |
| `is_primary` | `enum` | No | `yes` / `no`, default `'no'`. Contacto principal. |
| `status` | `enum` | No | `active` / `inactive`. |
| `created_at` / `updated_at` | `timestamp` | Sí | |

**Índices:** `index(client_id)`, `index(company_id)`, `index(status)`.

### 1.2 Direcciones — `app_client_addresses`

Un cliente puede tener varias direcciones de entrega (sucursales).

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | `uuid` | No | PK. |
| `company_id` | `uuid` | Sí | FK → `app_companies.id`. Heredado del cliente. |
| `client_id` | `uuid` | No | FK → `app_clients.id` (`cascadeOnDelete`). |
| `type` | `enum` | No | `billing`, `shipping`. |
| `name` | `string(150)` | No | Alias ("Sucursal Centro"). |
| `address` | `string(500)` | No | |
| `city` / `state` / `country` | `string(100)` | Sí | |
| `route_id` | `uuid` | Sí | FK → `app_routes.id`. Ruta de esta dirección. |
| `latitude` / `longitude` | `decimal(10,7)` | Sí | |
| `is_default` | `enum` | No | `yes` / `no`, default `'no'`. Dirección sugerida. |
| `status` | `enum` | No | `active` / `inactive`. |
| `created_at` / `updated_at` | `timestamp` | Sí | |

**Índices:** `index(client_id)`, `index(company_id)`, `index(route_id)`, `index(status)`.

---

## 2. Órdenes de venta

Pedido del cliente. **No descarga inventario**: lo reserva (`reserved_quantity`).

### 2.1 Cabecera — `app_sales_orders` — Prefijo `OVE`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `client_id` | `uuid` | No | | FK → `app_clients.id` (`restrictOnDelete`). |
| `client_address_id` | `uuid` | Sí | | FK → `app_client_addresses.id`. Dirección de entrega. |
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id`. Bodega de despacho. |
| `price_list_id` | `uuid` | Sí | | FK → `app_price_lists.id`. Lista aplicada al pedido. |
| `salesperson_id` | `uuid` | Sí | | FK → `users.id`. |
| `route_id` | `uuid` | Sí | | FK → `app_routes.id`. |
| `order_date` | `date` | No | | |
| `expected_date` | `date` | Sí | | Fecha comprometida de entrega. |
| `client_reference` | `string(60)` | Sí | | Número de orden de compra del cliente. |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `payment_term_days` | `integer` | No | `0` | |
| `subtotal` | `decimal(18,2)` | No | `0` | |
| `discount_amount` | `decimal(18,2)` | No | `0` | |
| `tax_amount` | `decimal(18,2)` | No | `0` | |
| `total` | `decimal(18,2)` | No | `0` | |
| `dispatched_percent` | `decimal(7,4)` | No | `0` | Avance de despacho (0–100). |
| `invoiced_percent` | `decimal(7,4)` | No | `0` | Avance de facturación (0–100). |
| `approved_by` | `uuid` | Sí | | FK → `users.id`. Necesario si excede el límite de crédito. |
| `approved_at` | `timestamp` | Sí | | |
| `cancelled_at` | `timestamp` | Sí | | |
| `cancellation_reason` | `string(500)` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `partial` → `completed`, o `cancelled`.

**Índices:** `index(client_id)`, `index(order_date)`, `index(expected_date)`, `index(salesperson_id)`,
`index(route_id)`, `index(warehouse_id)`.

### 2.2 Líneas — `app_sales_order_lines`

Además de las columnas comunes de línea:

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `reserved_quantity` | `decimal(18,4)` | No | `0` | Cantidad reservada en stock. |
| `dispatched_quantity` | `decimal(18,4)` | No | `0` | Ya despachada. |
| `invoiced_quantity` | `decimal(18,4)` | No | `0` | Ya facturada. |
| `pending_quantity` | `decimal(18,4)` | No | `0` | `quantity - dispatched_quantity`. |
| `list_price` | `decimal(18,6)` | No | `0` | Precio de lista antes del descuento; para medir el descuento real. |

**Reglas**
- Al confirmar: valida disponibilidad (`available_quantity`) y crédito; incrementa `reserved_quantity`.
- La reserva se libera al despachar (pasa a salida real) o al anular la orden.
- El precio se congela en la línea: cambios posteriores en la lista no afectan el pedido.
- No se permite `unit_price < item.min_price` sin el permiso correspondiente.

---

## 3. Facturas de venta

Documento fiscal que genera la cuenta por cobrar y descarga inventario si no hubo despacho previo.

### 3.1 Cabecera — `app_sales_invoices` — Prefijo `FVE`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `client_id` | `uuid` | No | | FK → `app_clients.id` (`restrictOnDelete`). |
| `sales_order_id` | `uuid` | Sí | | FK → `app_sales_orders.id`. |
| `dispatch_id` | `uuid` | Sí | | FK → `app_dispatches.id`. Despacho asociado. |
| `client_address_id` | `uuid` | Sí | | FK → `app_client_addresses.id`. |
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id`. |
| `salesperson_id` | `uuid` | Sí | | FK → `users.id`. Base para comisiones. |
| `invoice_series` | `string(20)` | Sí | | Serie fiscal autorizada. |
| `invoice_number` | `string(30)` | No | | Número fiscal correlativo (distinto de `code`). |
| `invoice_date` | `date` | No | | |
| `due_date` | `date` | No | | `invoice_date + payment_term_days`. |
| `sale_type` | `enum` | No | `'credit'` | `cash` (contado) o `credit`. |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `affects_inventory` | `enum` | No | `'yes'` | `no` si el stock ya salió con un despacho. |
| `subtotal` | `decimal(18,2)` | No | `0` | |
| `discount_amount` | `decimal(18,2)` | No | `0` | |
| `tax_amount` | `decimal(18,2)` | No | `0` | |
| `withholding_amount` | `decimal(18,2)` | No | `0` | Retención practicada por el cliente. |
| `freight_amount` | `decimal(18,2)` | No | `0` | Flete cobrado. |
| `total` | `decimal(18,2)` | No | `0` | |
| `total_cost` | `decimal(18,2)` | No | `0` | Costo de la mercancía vendida; base del margen. |
| `paid_amount` | `decimal(18,2)` | No | `0` | Cobrado + anticipos + notas de crédito aplicadas. |
| `balance` | `decimal(18,2)` | No | `0` | `total - paid_amount`. |
| `payment_status` | `enum` | No | `'pending'` | `pending`, `partial`, `paid`, `overdue`. |
| `fiscal_status` | `enum` | Sí | | `pending`, `sent`, `accepted`, `rejected`. Para facturación electrónica. |
| `fiscal_uuid` | `string(100)` | Sí | | Folio/UUID devuelto por la autoridad fiscal. |
| `printed_at` | `timestamp` | Sí | | |
| `cancelled_at` | `timestamp` | Sí | | |
| `cancellation_reason` | `string(500)` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `unique(company_id, invoice_series, invoice_number)`, `index(client_id)`,
`index(invoice_date)`, `index(due_date)`, `index(payment_status)`, `index(salesperson_id)`,
`index(sales_order_id)`.

### 3.2 Líneas — `app_sales_invoice_lines`

Además de las columnas comunes de línea:

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `sales_order_line_id` | `uuid` | Sí | | FK → `app_sales_order_lines.id`. |
| `warehouse_id` | `uuid` | Sí | | Bodega de la línea si difiere. |
| `lot_id` | `uuid` | Sí | | FK → `app_item_lots.id`. |
| `serial_id` | `uuid` | Sí | | FK → `app_item_serials.id`. |
| `unit_cost` | `decimal(18,6)` | No | `0` | Costo unitario al momento de la venta (congelado). |
| `total_cost` | `decimal(18,2)` | No | `0` | `base_quantity * unit_cost`. |
| `margin_amount` | `decimal(18,2)` | No | `0` | `subtotal - total_cost`. |
| `returned_quantity` | `decimal(18,4)` | No | `0` | Cantidad devuelta por el cliente. |

**Reglas**
- Al confirmar: si `affects_inventory = 'yes'`, genera movimientos `out` y congela `unit_cost` con el
  costo vigente del artículo. Aumenta `current_balance` del cliente.
- `invoice_number` se asigna al confirmar, nunca en borrador, y es correlativo por serie.
- Una factura confirmada **no se edita**: se anula y se emite una nueva, o se corrige con nota de crédito.
- Anular exige que no tenga cobros aplicados y genera movimientos de contrapartida.

---

## 4. Notas de crédito a clientes

Disminuye la cuenta por cobrar: devoluciones, descuentos posteriores o correcciones.

### 4.1 Cabecera — `app_sales_credit_notes` — Prefijo `NCC`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `client_id` | `uuid` | No | | FK → `app_clients.id` (`restrictOnDelete`). |
| `sales_invoice_id` | `uuid` | Sí | | FK → `app_sales_invoices.id`. Factura afectada. |
| `sales_return_id` | `uuid` | Sí | | FK → `app_sales_returns.id`. Devolución que la origina. |
| `note_series` | `string(20)` | Sí | | Serie fiscal. |
| `note_number` | `string(30)` | No | | Correlativo fiscal. |
| `note_date` | `date` | No | | |
| `reason` | `enum` | No | `'return'` | `return`, `discount`, `price_correction`, `damaged`, `cancellation`, `other`. |
| `reason_detail` | `string(500)` | Sí | | |
| `affects_inventory` | `enum` | No | `'no'` | `yes` si reingresa mercancía. |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `subtotal` | `decimal(18,2)` | No | `0` | |
| `tax_amount` | `decimal(18,2)` | No | `0` | |
| `total` | `decimal(18,2)` | No | `0` | |
| `applied_amount` | `decimal(18,2)` | No | `0` | Ya aplicado a facturas. |
| `balance` | `decimal(18,2)` | No | `0` | Crédito disponible. |
| `fiscal_status` | `enum` | Sí | | `pending`, `sent`, `accepted`, `rejected`. |
| `fiscal_uuid` | `string(100)` | Sí | | |
| `cancelled_at` | `timestamp` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `unique(company_id, note_series, note_number)`, `index(client_id)`,
`index(sales_invoice_id)`, `index(note_date)`, `index(balance)`.

### 4.2 Líneas — `app_sales_credit_note_lines`

Además de las columnas comunes de línea:

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `sales_invoice_line_id` | `uuid` | Sí | FK → línea facturada. |
| `warehouse_id` | `uuid` | Sí | Bodega de reingreso si `affects_inventory`. |
| `lot_id` | `uuid` | Sí | FK → `app_item_lots.id`. |
| `unit_cost` | `decimal(18,6)` | No | Costo al que reingresa (el mismo de la venta). |

**Reglas**
- Al confirmar disminuye `current_balance` del cliente y, si `affects_inventory`, genera movimientos `in`
  al costo original de la venta.
- El monto no puede superar el saldo pendiente de la factura más lo ya cobrado.
- Se aplica a facturas mediante `app_client_collection_applications` (tipo `credit_note`).

---

## 5. Anticipos de clientes

Dinero recibido antes de facturar. Queda como saldo a favor del cliente.

**Tabla:** `app_client_advances` — **Prefijo:** `ANC` (sin líneas)

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `client_id` | `uuid` | No | | FK → `app_clients.id` (`restrictOnDelete`). |
| `sales_order_id` | `uuid` | Sí | | FK → `app_sales_orders.id`. Pedido que motiva el anticipo. |
| `advance_date` | `date` | No | | |
| `payment_method` | `enum` | No | `'transfer'` | `cash`, `transfer`, `check`, `card`, `other`. |
| `reference` | `string(60)` | Sí | | Referencia bancaria o de comprobante. |
| `bank_account` | `string(60)` | Sí | | Cuenta receptora. |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `amount` | `decimal(18,2)` | No | `0` | Monto recibido. |
| `applied_amount` | `decimal(18,2)` | No | `0` | Ya aplicado a facturas. |
| `balance` | `decimal(18,2)` | No | `0` | Disponible. |
| `refunded_amount` | `decimal(18,2)` | No | `0` | Monto devuelto al cliente. |
| `cancelled_at` | `timestamp` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `partial` → `completed`, o `cancelled`.

**Índices:** `index(client_id)`, `index(advance_date)`, `index(balance)`, `index(sales_order_id)`.

**Reglas**
- Al confirmar, aumenta `advance_balance` del cliente.
- Se aplica a facturas mediante `app_client_collection_applications` (tipo `advance`).
- No se puede anular con `applied_amount > 0`.

---

## 6. Cobros a clientes

Entrada de dinero que cancela una o varias facturas. Puede combinar efectivo, anticipos y notas de crédito.

### 6.1 Cabecera — `app_client_collections` — Prefijo `COB`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `client_id` | `uuid` | No | | FK → `app_clients.id` (`restrictOnDelete`). |
| `collection_date` | `date` | No | | |
| `payment_method` | `enum` | No | `'cash'` | `cash`, `transfer`, `check`, `card`, `advance`, `credit_note`, `other`. |
| `reference` | `string(60)` | Sí | | Número de cheque, voucher o transferencia. |
| `bank_account` | `string(60)` | Sí | | Cuenta receptora. |
| `collected_by` | `uuid` | Sí | | FK → `users.id`. Cobrador o vendedor que recibió. |
| `route_id` | `uuid` | Sí | | FK → `app_routes.id`. Ruta en la que se cobró. |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `amount` | `decimal(18,2)` | No | `0` | Monto recibido. |
| `withholding_amount` | `decimal(18,2)` | No | `0` | Retención soportada por el cliente. |
| `applied_amount` | `decimal(18,2)` | No | `0` | Suma de aplicaciones. |
| `unapplied_amount` | `decimal(18,2)` | No | `0` | Excedente; se convierte en anticipo. |
| `check_number` | `string(30)` | Sí | | |
| `check_date` | `date` | Sí | | Fecha de cobro de cheques posfechados. |
| `check_status` | `enum` | Sí | | `pending`, `deposited`, `cleared`, `bounced`. |
| `cancelled_at` | `timestamp` | Sí | | |
| `cancellation_reason` | `string(500)` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `index(client_id)`, `index(collection_date)`, `index(payment_method)`,
`index(collected_by)`, `index(check_status)`.

### 6.2 Aplicaciones — `app_client_collection_applications`

Tabla puente que registra **qué documento abona qué factura**. La usan cobros, anticipos y notas de crédito.
Es tabla de detalle: lleva `company_id` y `status`, pero no `code` (se identifica por la factura y su origen).

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `id` | `uuid` | No | | PK. |
| `company_id` | `uuid` | No | | FK → `app_companies.id`. |
| `sales_invoice_id` | `uuid` | No | | FK → `app_sales_invoices.id` (`restrictOnDelete`). |
| `source_type` | `enum` | No | | `collection`, `advance`, `credit_note`. |
| `source_id` | `uuid` | No | | Id del cobro, anticipo o nota de crédito. |
| `applied_amount` | `decimal(18,2)` | No | `0` | Monto aplicado. |
| `applied_at` | `datetime` | No | | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `exchange_difference` | `decimal(18,2)` | No | `0` | Diferencial cambiario. |
| `status` | `enum` | No | `'active'` | `active`, `reversed`. |
| `created_by` | `uuid` | Sí | | FK → `users.id`. |
| `created_at` / `updated_at` | `timestamp` | Sí | | |

**Índices:** `index(sales_invoice_id)`, `index(source_type, source_id)`, `index(applied_at)`,
`unique(sales_invoice_id, source_type, source_id)`.

**Reglas**
- La suma de aplicaciones activas no puede superar el `total` de la factura.
- Cada aplicación actualiza `paid_amount`, `balance` y `payment_status` de la factura y el saldo del cliente.
- Un cheque rebotado (`check_status = bounced`) revierte automáticamente sus aplicaciones y las marca
  como `reversed`, restaurando el saldo del cliente.

---

## 7. Devoluciones de ventas

Reingreso físico de mercancía desde el cliente. Deriva normalmente en una nota de crédito.

### 7.1 Cabecera — `app_sales_returns` — Prefijo `DVV`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `client_id` | `uuid` | No | | FK → `app_clients.id` (`restrictOnDelete`). |
| `sales_invoice_id` | `uuid` | Sí | | FK → `app_sales_invoices.id`. |
| `dispatch_id` | `uuid` | Sí | | FK → `app_dispatches.id`. Despacho de origen. |
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id`. Bodega de reingreso. |
| `return_date` | `date` | No | | |
| `reason` | `enum` | No | `'damaged'` | `damaged`, `wrong_item`, `expired`, `excess`, `quality`, `client_cancellation`, `other`. |
| `reason_detail` | `string(500)` | Sí | | |
| `condition` | `enum` | No | `'resalable'` | `resalable` (reingresa a bodega de venta), `damaged` (va a cuarentena), `scrap` (se destruye). |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `subtotal` | `decimal(18,2)` | No | `0` | |
| `tax_amount` | `decimal(18,2)` | No | `0` | |
| `total` | `decimal(18,2)` | No | `0` | |
| `credit_note_id` | `uuid` | Sí | | FK → `app_sales_credit_notes.id`. Nota generada. |
| `received_by` | `uuid` | Sí | | FK → `users.id`. Quién recibió la mercancía. |
| `cancelled_at` | `timestamp` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `completed` (acreditada), o `cancelled`.

**Índices:** `index(client_id)`, `index(sales_invoice_id)`, `index(return_date)`, `index(warehouse_id)`.

### 7.2 Líneas — `app_sales_return_lines`

Además de las columnas comunes de línea:

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `sales_invoice_line_id` | `uuid` | Sí | FK → línea facturada. |
| `lot_id` | `uuid` | Sí | FK → `app_item_lots.id`. |
| `serial_id` | `uuid` | Sí | FK → `app_item_serials.id`. |
| `location_id` | `uuid` | Sí | Ubicación de reingreso. |
| `unit_cost` | `decimal(18,6)` | No | Costo al que reingresa: el de la venta original. |
| `condition` | `enum` | Sí | Condición específica de la línea. |

**Reglas**
- Al confirmar genera movimientos `in` al costo original de la venta, **no** al promedio actual:
  así no se distorsiona la valuación.
- `condition = damaged` reingresa a una bodega `quarantine`; `condition = scrap` no reingresa stock
  y se registra como pérdida vía Ajuste.
- La cantidad devuelta no puede superar `quantity - returned_quantity` de la línea de factura.

---

## Diagrama del ciclo

```
app_client_types ──┐
                   ├──> app_clients ──┬──> app_client_contacts
app_price_lists ───┘                  ├──> app_client_addresses
                                      │
     ┌────────────────────────────────┤
     ├──> app_sales_orders ──> app_sales_order_lines
     │            │
     │            └──> app_dispatches (Logística) ──> kardex (out)
     │                       │
     ├──> app_sales_invoices ──> app_sales_invoice_lines
     │            │
     │            ├──< app_client_collection_applications >── app_client_collections
     │            │                                        ├── app_client_advances
     │            │                                        └── app_sales_credit_notes
     │            │
     └──> app_sales_returns ──> app_sales_credit_notes ──> kardex (in)
```
