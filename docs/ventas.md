# Ventas

Ciclo comercial completo: desde el registro del cliente hasta el cobro y las devoluciones.

> Las columnas base (`id`, `company_id`, `code`, `status`, `created_by`, `created_at`, `updated_at`),
> los índices base, la estructura común de las tablas `*_lines`, los tipos numéricos y la política de
> no borrado están descritos en [README.md](README.md) y no se repiten aquí.

| Módulo                     | Tabla                                                           | Prefijo |
|----------------------------|-----------------------------------------------------------------|---------|
| Clientes                   | `app_clients` (+ `app_client_contacts`, `app_client_addresses`) | `CLI`   |
| Órdenes de venta           | `app_sales_orders` + `app_sales_order_lines`                    | `OVE`   |
| Facturas de venta          | `app_sales_invoices` + `app_sales_invoice_lines`                | `FVE`   |
| Notas de crédito a cliente | `app_sales_credit_notes` + `app_sales_credit_note_lines`        | `NCC`   |
| Anticipos de clientes      | `app_client_advances`                                           | `ANC`   |
| Cobros a clientes          | `app_client_collections` + `app_client_collection_applications` | `COB`   |
| Devoluciones de ventas     | `app_sales_returns` + `app_sales_return_lines`                  | `DVV`   |

**Depende de:** [Catálogo](catalogo.md) (tipo de cliente, listas de precio, impuestos) e
[Inventario](inventario.md) (artículos, bodegas, existencias).

**Flujo:** Orden de venta → Despacho → Factura de venta → Cobro (con Anticipos, Notas de crédito y Devoluciones como
ramas del mismo ciclo).

---

## 1. Clientes

**La tabla `app_clients` ya existe** (módulo `app/Modules/Client`) con `id`, `company_id`, `code`,
`name`, `phone`, `email`, `status`, `notes`, `created_by` y timestamps. Este módulo la **extiende**
con la información comercial necesaria para facturar.

**Tabla:** `app_clients` — **Prefijo:** `CLI`

### Columnas actuales

| Columna | Tipo          | Nulo | Descripción         |
|---------|---------------|------|---------------------|
| `name`  | `string(150)` | No   | Nombre del cliente. |
| `phone` | `string(30)`  | Sí   |                     |
| `email` | `string(255)` | Sí   | Único por empresa.  |
| `notes` | `text`        | Sí   |                     |

### Columnas a agregar

| Columna             | Tipo            | Nulo | Default | Descripción                                                                                    |
|---------------------|-----------------|------|---------|------------------------------------------------------------------------------------------------|
| `client_type_id`    | `uuid`          | Sí   |         | FK → `app_client_types.id` (`nullOnDelete`). Solo clasificación; no afecta precios ni crédito. |
| `price_list_id`     | `uuid`          | Sí   |         | FK → `app_price_lists.id` (`nullOnDelete`). Lista asignada al cliente.                         |
| `legal_name`        | `string(200)`   | Sí   |         | Razón social para facturar.                                                                    |
| `document_type`     | `enum`          | No   | `'V'`   | Letra del RIF: `V`, `E`, `J`, `P`, `G`, `C`. Ver [README.md](README.md).                       |
| `document_number`   | `string(15)`    | No   |         | Dígitos del RIF/cédula, sin letra ni guiones.                                                  |
| `mobile`            | `string(30)`    | Sí   |         |                                                                                                |
| `address`           | `string(500)`   | Sí   |         | Dirección fiscal.                                                                              |
| `city`              | `string(100)`   | Sí   |         |                                                                                                |
| `state`             | `string(100)`   | Sí   |         |                                                                                                |
| `country`           | `string(100)`   | Sí   |         |                                                                                                |
| `payment_term_days` | `integer`       | No   | `0`     | Días de crédito. `0` = contado.                                                                |
| `credit_limit`      | `decimal(18,2)` | No   | `0`     | Cupo máximo. `0` = sin crédito.                                                                |
| `credit_blocked`    | `enum`          | No   | `'no'`  | Bloquea nuevas ventas a crédito.                                                               |
| `current_balance`   | `decimal(18,2)` | No   | `0`     | Saldo por cobrar. Derivado.                                                                    |
| `advance_balance`   | `decimal(18,2)` | No   | `0`     | Anticipos recibidos y no aplicados.                                                            |
| `discount_percent`  | `decimal(7,4)`  | No   | `0`     | Descuento fijo del cliente.                                                                    |
| `salesperson_id`    | `uuid`          | Sí   |         | FK → `users.id` (`nullOnDelete`). Vendedor asignado.                                           |
| `route_id`          | `uuid`          | Sí   |         | FK → `app_routes.id` (`nullOnDelete`). Ruta de entrega habitual.                               |
| `latitude`          | `decimal(10,7)` | Sí   |         | Georreferencia para rutas.                                                                     |
| `longitude`         | `decimal(10,7)` | Sí   |         |                                                                                                |

**Índices adicionales:** `unique(company_id, document_type, document_number)`, `index(client_type_id)`,
`index(price_list_id)`, `index(salesperson_id)`, `index(route_id)`, `index(current_balance)`,
`index(document_number)`.

**Reglas**

- `document_type` + `document_number` identifican al cliente: son obligatorios y únicos por empresa. La naturaleza del
  contribuyente se deriva de la letra (`V`/`E`/`P` natural, `J`/`G`/`C` jurídica).
- Resolución del precio de venta: precio del artículo en `client.price_list_id` → precio del artículo en la lista por
  defecto de la empresa. Ambos salen de `app_item_prices`. El tipo de cliente no interviene.
- `current_balance` y `advance_balance` son derivados; los mantiene el sistema al confirmar documentos.
- Si `current_balance + total del pedido > credit_limit`, la venta a crédito se bloquea salvo autorización explícita
  (permiso `override_credit_limit`).
- No se puede desactivar un cliente con saldo distinto de cero o documentos abiertos.

### 1.1 Contactos — `app_client_contacts`

| Columna                     | Tipo          | Nulo | Descripción                                       |
|-----------------------------|---------------|------|---------------------------------------------------|
| `id`                        | `uuid`        | No   | PK.                                               |
| `company_id`                | `uuid`        | Sí   | FK → `app_companies.id`. Heredado del cliente.    |
| `client_id`                 | `uuid`        | No   | FK → `app_clients.id` (`cascadeOnDelete`).        |
| `name`                      | `string(150)` | No   |                                                   |
| `position`                  | `string(100)` | Sí   |                                                   |
| `email`                     | `string(255)` | Sí   |                                                   |
| `phone`                     | `string(30)`  | Sí   |                                                   |
| `is_primary`                | `enum`        | No   | `yes` / `no`, default `'no'`. Contacto principal. |
| `status`                    | `enum`        | No   | `active` / `inactive`.                            |
| `created_at` / `updated_at` | `timestamp`   | Sí   |                                                   |

**Índices:** `index(client_id)`, `index(company_id)`, `index(status)`.

### 1.2 Direcciones — `app_client_addresses`

Un cliente puede tener varias direcciones de entrega (sucursales).

| Columna                      | Tipo            | Nulo | Descripción                                       |
|------------------------------|-----------------|------|---------------------------------------------------|
| `id`                         | `uuid`          | No   | PK.                                               |
| `company_id`                 | `uuid`          | Sí   | FK → `app_companies.id`. Heredado del cliente.    |
| `client_id`                  | `uuid`          | No   | FK → `app_clients.id` (`cascadeOnDelete`).        |
| `type`                       | `enum`          | No   | `billing`, `shipping`.                            |
| `name`                       | `string(150)`   | No   | Alias ("Sucursal Centro").                        |
| `address`                    | `string(500)`   | No   |                                                   |
| `city` / `state` / `country` | `string(100)`   | Sí   |                                                   |
| `route_id`                   | `uuid`          | Sí   | FK → `app_routes.id`. Ruta de esta dirección.     |
| `latitude` / `longitude`     | `decimal(10,7)` | Sí   |                                                   |
| `is_default`                 | `enum`          | No   | `yes` / `no`, default `'no'`. Dirección sugerida. |
| `status`                     | `enum`          | No   | `active` / `inactive`.                            |
| `created_at` / `updated_at`  | `timestamp`     | Sí   |                                                   |

**Índices:** `index(client_id)`, `index(company_id)`, `index(route_id)`, `index(status)`.

---

## 2. Órdenes de venta

Pedido del cliente. **No descarga inventario**: lo reserva (`reserved_quantity`).

### 2.1 Cabecera — `app_sales_orders` — Prefijo `OVE`

| Columna               | Tipo            | Nulo | Default | Descripción                                                |
|-----------------------|-----------------|------|---------|------------------------------------------------------------|
| `client_id`           | `uuid`          | No   |         | FK → `app_clients.id` (`restrictOnDelete`).                |
| `client_address_id`   | `uuid`          | Sí   |         | FK → `app_client_addresses.id`. Dirección de entrega.      |
| `warehouse_id`        | `uuid`          | No   |         | FK → `app_warehouses.id`. Bodega de despacho.              |
| `price_list_id`       | `uuid`          | Sí   |         | FK → `app_price_lists.id`. Lista aplicada al pedido.       |
| `salesperson_id`      | `uuid`          | Sí   |         | FK → `users.id`.                                           |
| `route_id`            | `uuid`          | Sí   |         | FK → `app_routes.id`.                                      |
| `order_date`          | `date`          | No   |         |                                                            |
| `expected_date`       | `date`          | Sí   |         | Fecha comprometida de entrega.                             |
| `client_reference`    | `string(60)`    | Sí   |         | Número de orden de compra del cliente.                     |
| `currency`            | `string(3)`     | No   | `'USD'` |                                                            |
| `exchange_rate`       | `decimal(18,8)` | No   | `1`     |                                                            |
| `payment_term_days`   | `integer`       | No   | `0`     |                                                            |
| `subtotal`            | `decimal(18,2)` | No   | `0`     |                                                            |
| `discount_amount`     | `decimal(18,2)` | No   | `0`     |                                                            |
| `tax_amount`          | `decimal(18,2)` | No   | `0`     |                                                            |
| `total`               | `decimal(18,2)` | No   | `0`     |                                                            |
| `dispatched_percent`  | `decimal(7,4)`  | No   | `0`     | Avance de despacho (0–100).                                |
| `invoiced_percent`    | `decimal(7,4)`  | No   | `0`     | Avance de facturación (0–100).                             |
| `approved_by`         | `uuid`          | Sí   |         | FK → `users.id`. Necesario si excede el límite de crédito. |
| `approved_at`         | `timestamp`     | Sí   |         |                                                            |
| `cancelled_at`        | `timestamp`     | Sí   |         |                                                            |
| `cancellation_reason` | `string(500)`   | Sí   |         |                                                            |
| `notes`               | `text`          | Sí   |         |                                                            |

**Estados (`status`):** `draft` → `confirmed` → `partial` → `completed`, o `cancelled`.

El usuario solo decide dos de esos estados: **confirmar** y **anular**. `partial` y `completed` no se declaran
desde la pantalla —no hay botón para ellos—: son la lectura de lo que las líneas ya dicen, y los escribe
`SalesOrderSettleStatusService` cada vez que un Despacho o una Factura de venta se confirma o se anula. Por eso
el estado también **retrocede**: anular el despacho que lo había cerrado devuelve el pedido a `partial`, y
anular el último documento que lo tocaba, a `confirmed`. Es el mismo mecanismo que en la orden de compra
([compras.md § 2.1](compras.md)).

**Índices:** `index(client_id)`, `index(order_date)`, `index(expected_date)`, `index(salesperson_id)`,
`index(route_id)`, `index(warehouse_id)`.

### 2.2 Líneas — `app_sales_order_lines`

Además de las columnas comunes de línea:

| Columna               | Tipo            | Nulo | Default | Descripción                                                        |
|-----------------------|-----------------|------|---------|--------------------------------------------------------------------|
| `reserved_quantity`   | `decimal(18,4)` | No   | `0`     | Cantidad reservada en stock.                                       |
| `dispatched_quantity` | `decimal(18,4)` | No   | `0`     | Ya despachada.                                                     |
| `invoiced_quantity`   | `decimal(18,4)` | No   | `0`     | Ya facturada.                                                      |
| `pending_quantity`    | `decimal(18,4)` | No   | `0`     | `quantity - dispatched_quantity`.                                  |
| `list_price`          | `decimal(18,6)` | No   | `0`     | Precio de lista antes del descuento; para medir el descuento real. |

**Reglas**

- Al confirmar: valida disponibilidad (`available_quantity`) y crédito; incrementa `reserved_quantity`.
- El saldo de una línea son **dos cuentas distintas**, una por cada camino que cumple el pedido
  (la regla completa de lo que un documento arrastra del que lo origina está en
  [documentos-origen.md](documentos-origen.md)):
  `quantity - dispatched_quantity` es lo que falta por salir y `quantity - invoiced_quantity` lo que falta por
  facturar. Ninguna tiene columna propia: la columna `pending_quantity` es solo la primera, y no sirve para la
  segunda. Cada una tiene su ruta —`GET /{company}/sales-orders/{id}/dispatchable-lines` para el Despacho y
  `.../invoiceable-lines` para la Factura de venta—, y la pantalla la pide en cuanto se elige el pedido para
  armar sus líneas. Ambas admiten `?ids=` para que vuelvan también las líneas que el documento ya tenía atadas
  aunque su saldo esté en cero.
- **Las dos cuentas no miden sobre lo mismo.** Lo facturado se mide contra todas las líneas —un servicio se
  factura igual que un tornillo—, pero lo despachado **solo contra las líneas que llevan existencia**
  (`Item::movesStock()`). Un servicio o un artículo no inventariado no sale nunca en un Despacho: el despacho
  espejo ni siquiera los incluye, y `dispatchable-lines` los da por saldados. Contarlos en
  `dispatched_percent` dejaría el avance por debajo del 100 % con toda la mercancía ya entregada, y el pedido
  no cerraría jamás. Un pedido que solo vende servicios nace con `dispatched_percent = 100`: no hay nada que
  sacar.
- Se cierra automáticamente (`completed`) cuando **las dos** cuentas de todas las líneas llegaron a lo pedido:
  lo despachado y lo facturado. Un pedido entregado pero sin facturar sigue abierto, porque sigue teniendo algo
  pendiente con el cliente. Con avance en cualquiera de las dos pero sin cerrar las dos, queda en `partial`.
  Cada cuenta se topa **por línea**: un exceso en una no tapa lo que falta en otra.
- La reserva se libera al despachar (pasa a salida real) o al anular la orden.
- El precio se congela en la línea: cambios posteriores en la lista no afectan el pedido.
- No se permite `unit_price < item.min_price` sin el permiso correspondiente.

---

## 3. Facturas de venta

Documento fiscal que genera la cuenta por cobrar. **No descarga inventario**: la mercancía sale con su Despacho, y de
ese movimiento la factura lee el costo que congela. Ver [inventario.md § 4.1](inventario.md).

### 3.1 Cabecera — `app_sales_invoices` — Prefijo `FVE`

| Columna               | Tipo            | Nulo | Default     | Descripción                                                              |
|-----------------------|-----------------|------|-------------|--------------------------------------------------------------------------|
| `client_id`           | `uuid`          | No   |             | FK → `app_clients.id` (`restrictOnDelete`).                              |
| `sourceable_type`     | `string(255)`   | Sí   |             | Alias del documento origen en el morph map. Hoy solo `sales_order`.      |
| `sourceable_id`       | `uuid`          | Sí   |             | ID del documento origen. Con `sourceable_type` forma la relación `sourceable`. |
| `dispatch_id`         | `uuid`          | Sí   |             | FK → `app_dispatches.id`. Despacho asociado.                             |
| `client_address_id`   | `uuid`          | Sí   |             | FK → `app_client_addresses.id`.                                          |
| `warehouse_id`        | `uuid`          | No   |             | FK → `app_warehouses.id`.                                                |
| `salesperson_id`      | `uuid`          | Sí   |             | FK → `users.id`. Base para comisiones.                                   |
| `invoice_series`      | `string(20)`    | Sí   |             | Serie fiscal autorizada.                                                 |
| `invoice_number`      | `string(30)`    | No   |             | Número fiscal correlativo (distinto de `code`).                          |
| `invoice_date`        | `date`          | No   |             |                                                                          |
| `due_date`            | `date`          | No   |             | `invoice_date + payment_term_days`.                                      |
| `sale_type`           | `enum`          | No   | `'credit'`  | `cash` (contado) o `credit`.                                             |
| `currency`            | `string(3)`     | No   | `'USD'`     |                                                                          |
| `exchange_rate`       | `decimal(18,8)` | No   | `1`         |                                                                          |
| `subtotal`            | `decimal(18,2)` | No   | `0`         |                                                                          |
| `discount_amount`     | `decimal(18,2)` | No   | `0`         |                                                                          |
| `tax_amount`          | `decimal(18,2)` | No   | `0`         |                                                                          |
| `withholding_amount`  | `decimal(18,2)` | No   | `0`         | Retención practicada por el cliente.                                     |
| `total`               | `decimal(18,2)` | No   | `0`         |                                                                          |
| `total_cost`          | `decimal(18,2)` | No   | `0`         | Costo de la mercancía vendida; base del margen.                          |
| `paid_amount`         | `decimal(18,2)` | No   | `0`         | Cobrado + anticipos + notas de crédito aplicadas.                        |
| `balance`             | `decimal(18,2)` | No   | `0`         | `total - paid_amount`.                                                   |
| `payment_status`      | `enum`          | No   | `'pending'` | `pending`, `partial`, `paid`, `overdue`.                                 |
| `fiscal_status`       | `enum`          | Sí   |             | `pending`, `sent`, `accepted`, `rejected`. Para facturación electrónica. |
| `fiscal_uuid`         | `string(100)`   | Sí   |             | Folio/UUID devuelto por la autoridad fiscal.                             |
| `printed_at`          | `timestamp`     | Sí   |             |                                                                          |
| `cancelled_at`        | `timestamp`     | Sí   |             |                                                                          |
| `cancellation_reason` | `string(500)`   | Sí   |             |                                                                          |
| `notes`               | `text`          | Sí   |             |                                                                          |

**Estados (`status`):** `draft` → `confirmed` → `completed`, o `cancelled`.

`completed` significa **cobrada**, y no se declara desde la pantalla: lo escribe
`SalesInvoiceSettleStatusService` en cuanto `payment_status` llega a `paid`, es decir cuando un cobro, un
anticipo o una nota de crédito deja el `balance` en cero. Revertir esa aplicación devuelve la factura a
`confirmed`, porque vuelve a ser una cuenta por cobrar. Al usuario le quedan **emitir** y **anular**.

**Índices:** `unique(company_id, invoice_series, invoice_number)`, `index(client_id)`,
`index(invoice_date)`, `index(due_date)`, `index(payment_status)`, `index(salesperson_id)`,
`index(sourceable_type, sourceable_id)`.

**Documento origen (`sourceable`)**

La factura no apunta a la orden con un FK directo: lo hace con una relación polimórfica `sourceable`
(`morphTo`), y la orden de venta la expone con `morphMany`. Así el mismo par de columnas admite mañana otros
documentos de origen (cotización, contrato, ticket de punto de venta) sin agregar una columna por cada uno.

- `sourceable_type` guarda el **alias del morph map**, no el FQCN de la clase. El mapa se registra con
  `Relation::enforceMorphMap()` en un service provider, de modo que renombrar o mover la clase no rompe los
  datos ya guardados.
- Tipos admitidos hoy: `sales_order` → `app_sales_orders`. Cualquier otro valor es inválido y se rechaza en el
  Request.
- Ambas columnas son nulas: una factura directa (sin orden previa) las deja vacías. Si una viene informada, la
  otra es obligatoria.
- El documento origen debe pertenecer a la misma empresa y al mismo cliente que la factura.
- Al no ser un FK, la integridad no la garantiza la base de datos: la valida el Service antes de guardar, y el
  origen se protege por la política de no borrado.
- `dispatch_id` **no** entra en el morph: sigue siendo un FK directo, porque el despacho es un documento
  paralelo (la logística de entrega), no el documento que origina la factura.

### 3.2 Líneas — `app_sales_invoice_lines`

Además de las columnas comunes de línea:

| Columna               | Tipo            | Nulo | Default | Descripción                                        |
|-----------------------|-----------------|------|---------|----------------------------------------------------|
| `sourceable_type`     | `string(255)`   | Sí   |         | Alias de la línea origen (`sales_order_line`).     |
| `sourceable_id`       | `uuid`          | Sí   |         | ID de la línea origen.                             |
| `warehouse_id`        | `uuid`          | Sí   |         | Bodega de la línea si difiere.                     |
| `lot_id`              | `uuid`          | Sí   |         | FK → `app_item_lots.id`.                           |
| `serial_id`           | `uuid`          | Sí   |         | FK → `app_item_serials.id`.                        |
| `unit_cost`           | `decimal(18,6)` | No   | `0`     | Costo unitario al momento de la venta (congelado). |
| `total_cost`          | `decimal(18,2)` | No   | `0`     | `base_quantity * unit_cost`.                       |
| `margin_amount`       | `decimal(18,2)` | No   | `0`     | `subtotal - total_cost`.                           |
| `returned_quantity`   | `decimal(18,4)` | No   | `0`     | Cantidad devuelta por el cliente.                  |

**Reglas**

- La cantidad de una línea con origen no puede superar lo que a la línea del pedido le queda por facturar
  (`quantity - invoiced_quantity`). El tope se mide contra `invoiced_quantity`, que solo se mueve al confirmar: un
  borrador todavía no consume saldo.
- Al confirmar no toca el kardex: la mercancía sale con su Despacho. Lo que sí hace es congelar `unit_cost` contra el
  movimiento de ese despacho, y sin despacho conserva el costo que la captura le puso. Aumenta `current_balance` del
  cliente.
- Un flete cobrado al cliente va como **una línea más**, con un artículo de tipo `service`. La cabecera ya no
  lleva importe de flete: el total es la suma de sus líneas más el impuesto.
- `invoice_number` se asigna al confirmar, nunca en borrador, y es correlativo por serie.
- Una factura confirmada **no se edita**: se anula y se emite una nueva, o se corrige con nota de crédito.
- Anular exige que no tenga cobros aplicados. No revierte movimientos de kardex: la factura nunca los escribió.

---

## 4. Notas de crédito a clientes

Disminuye la cuenta por cobrar: devoluciones, descuentos posteriores o correcciones. **No mueve inventario**: la
mercancía que reingresa entra con su Entrada. Ver [inventario.md § 4.1](inventario.md).

### 4.1 Cabecera — `app_sales_credit_notes` — Prefijo `NCC`

| Columna             | Tipo            | Nulo | Default    | Descripción                                                                   |
|---------------------|-----------------|------|------------|-------------------------------------------------------------------------------|
| `client_id`         | `uuid`          | No   |            | FK → `app_clients.id` (`restrictOnDelete`).                                   |
| `sales_invoice_id`  | `uuid`          | Sí   |            | FK → `app_sales_invoices.id`. Factura afectada.                               |
| `sales_return_id`   | `uuid`          | Sí   |            | FK → `app_sales_returns.id`. Devolución que la origina.                       |
| `note_series`       | `string(20)`    | Sí   |            | Serie fiscal.                                                                 |
| `note_number`       | `string(30)`    | No   |            | Correlativo fiscal.                                                           |
| `note_date`         | `date`          | No   |            |                                                                               |
| `reason`            | `enum`          | No   | `'return'` | `return`, `discount`, `price_correction`, `damaged`, `cancellation`, `other`. |
| `reason_detail`     | `string(500)`   | Sí   |            |                                                                               |
| `currency`          | `string(3)`     | No   | `'USD'`    |                                                                               |
| `exchange_rate`     | `decimal(18,8)` | No   | `1`        |                                                                               |
| `subtotal`          | `decimal(18,2)` | No   | `0`        |                                                                               |
| `tax_amount`        | `decimal(18,2)` | No   | `0`        |                                                                               |
| `total`             | `decimal(18,2)` | No   | `0`        |                                                                               |
| `applied_amount`    | `decimal(18,2)` | No   | `0`        | Ya aplicado a facturas.                                                       |
| `balance`           | `decimal(18,2)` | No   | `0`        | Crédito disponible.                                                           |
| `fiscal_status`     | `enum`          | Sí   |            | `pending`, `sent`, `accepted`, `rejected`.                                    |
| `fiscal_uuid`       | `string(100)`   | Sí   |            |                                                                               |
| `cancelled_at`      | `timestamp`     | Sí   |            |                                                                               |
| `notes`             | `text`          | Sí   |            |                                                                               |

**Estados (`status`):** `draft` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `unique(company_id, note_series, note_number)`, `index(client_id)`,
`index(sales_invoice_id)`, `index(note_date)`, `index(balance)`.

### 4.2 Líneas — `app_sales_credit_note_lines`

Además de las columnas comunes de línea:

| Columna                 | Tipo            | Nulo | Descripción                                    |
|-------------------------|-----------------|------|------------------------------------------------|
| `sales_invoice_line_id` | `uuid`          | Sí   | FK → línea facturada.                          |
| `warehouse_id`          | `uuid`          | Sí   | Bodega a la que se refiere la línea. Informativa. |
| `lot_id`                | `uuid`          | Sí   | FK → `app_item_lots.id`.                       |
| `unit_cost`             | `decimal(18,6)` | No   | Costo al que reingresa (el mismo de la venta). |

**Reglas**

- Al confirmar disminuye `current_balance` del cliente. No genera movimientos de kardex: la mercancía que reingresa
  entra con su Entrada.
- El monto no puede superar el saldo pendiente de la factura más lo ya cobrado.
- Se aplica a facturas mediante `app_client_collection_applications` (tipo `credit_note`).

---

## 5. Anticipos de clientes

Dinero recibido antes de facturar. Queda como saldo a favor del cliente.

**Tabla:** `app_client_advances` — **Prefijo:** `ANC` (sin líneas)

| Columna           | Tipo            | Nulo | Default      | Descripción                                                |
|-------------------|-----------------|------|--------------|------------------------------------------------------------|
| `client_id`       | `uuid`          | No   |              | FK → `app_clients.id` (`restrictOnDelete`).                |
| `sales_order_id`  | `uuid`          | Sí   |              | FK → `app_sales_orders.id`. Pedido que motiva el anticipo. |
| `advance_date`    | `date`          | No   |              |                                                            |
| `payment_method`  | `enum`          | No   | `'transfer'` | `cash`, `transfer`, `check`, `card`, `other`.              |
| `reference`       | `string(60)`    | Sí   |              | Referencia bancaria o de comprobante.                      |
| `bank_account`    | `string(60)`    | Sí   |              | Cuenta receptora.                                          |
| `currency`        | `string(3)`     | No   | `'USD'`      |                                                            |
| `exchange_rate`   | `decimal(18,8)` | No   | `1`          |                                                            |
| `amount`          | `decimal(18,2)` | No   | `0`          | Monto recibido.                                            |
| `applied_amount`  | `decimal(18,2)` | No   | `0`          | Ya aplicado a facturas.                                    |
| `balance`         | `decimal(18,2)` | No   | `0`          | Disponible.                                                |
| `refunded_amount` | `decimal(18,2)` | No   | `0`          | Monto devuelto al cliente.                                 |
| `cancelled_at`    | `timestamp`     | Sí   |              |                                                            |
| `notes`           | `text`          | Sí   |              |                                                            |

**Estados (`status`):** `draft` → `pending_confirmation` → `confirmed` → `partial` → `completed`, o `cancelled` desde
`draft` / `pending_confirmation`.

**Índices:** `index(client_id)`, `index(advance_date)`, `index(balance)`, `index(sales_order_id)`.

### 5.1 Aprobación y cobro espejo

El anticipo no mueve dinero por sí solo: lo mueve el cobro que genera. Aprobar un anticipo lo deja *comprometido*, no
*recibido*, y esa distinción es la que sostiene el estado intermedio.

1. **`draft`** — se captura el anticipo. Todavía es editable y no afecta ningún saldo.
2. **Aprobar** — pasa a `pending_confirmation` y se crea automáticamente un cobro `COB` en `draft` con
   `origin_type = 'advance'` y `origin_id` = id del anticipo, copiando `client_id`, `advance_date` →
   `collection_date`, `payment_method`, `reference`, `bank_account`, `currency`, `exchange_rate` y `amount`. El cobro
   nace **sin aplicaciones a facturas**.
3. **Confirmar el cobro** — el anticipo pasa a `confirmed` y solo entonces aumenta el `advance_balance` del cliente y
   queda disponible para aplicarse a facturas.
4. **Anular el cobro** — el anticipo vuelve a `draft`. Puede corregirse y aprobarse de nuevo, lo que genera un cobro
   nuevo; conserva su `code` `ANC`.

**Reglas**

- En `pending_confirmation` el anticipo no suma al `advance_balance` del cliente ni puede aplicarse a facturas.
- Un anticipo no puede tener más de un cobro asociado en estado distinto de `cancelled`.
- El cobro espejo no es editable: solo se confirma o se anula. Monto, moneda, tasa, cliente y método son propiedad del
  anticipo y se corrigen allí, con el anticipo de vuelta en `draft`.
- Si el cobro espejo se pagó con cheque y este rebota (`check_status = bounced`), el anticipo vuelve a `draft` igual que
  al anularse: el dinero nunca entró.
- Se aplica a facturas mediante `app_client_collection_applications` (tipo `advance`), solo desde `confirmed`.
- No se puede anular con `applied_amount > 0`.

---

## 6. Cobros a clientes

Entrada de dinero que cancela una o varias facturas. Puede combinar efectivo, anticipos y notas de crédito.

### 6.1 Origen del cobro

El formulario arranca con un selector **«Aplicar cobro a»** que decide de dónde sale el trabajo y qué se ofrece en el
resto de la pantalla. Un segundo select, dependiente del primero, elige el registro concreto.

| «Aplicar cobro a» | Segundo select                                         | Qué hace                                                                                           |
|-------------------|--------------------------------------------------------|----------------------------------------------------------------------------------------------------|
| **Cliente**       | Clientes con `current_balance > 0`                     | Lista **todas** las facturas del cliente con `balance > 0` para repartir el monto entre ellas.     |
| **Factura**       | Facturas `FVE` con `balance > 0`, de cualquier cliente | Fija el cliente y precarga esa factura en las aplicaciones. Pueden sumarse otras del mismo.        |
| **Anticipo**      | —                                                      | Solo de lectura: identifica un cobro espejo ya generado. Ver [5.1](#51-aprobación-y-cobro-espejo). |

Al crear, el selector ofrece únicamente **Cliente** y **Factura**. **Anticipo** es el tercer valor posible de
`origin_type`, pero no un camino de creación: esos cobros nacen al aprobar un `ANC` y el formulario los muestra con el
origen fijo y en modo lectura.

Junto al selector se muestran dos indicadores del cliente elegido, que se recalculan al cambiarlo:

- **Saldo por cobrar** — suma de `balance` de sus facturas `FVE` abiertas; equivale a su `current_balance`.
- **Crédito a favor** — `advance_balance` del cliente más el saldo de sus notas de crédito `NCC`. Es lo aplicable sin
  recibir dinero.

**Reglas**

- Solo se ofrecen facturas con `balance > 0` que no estén anuladas, y siempre de un único cliente: un `COB` no cruza
  clientes.
- El origen se congela en la cabecera (`origin_type` / `origin_id`) al crear el cobro; no se cambia después.
- Con origen `advance` el cobro se crea desde el módulo de anticipos, nunca desde esta pantalla.

### 6.2 Cabecera — `app_client_collections` — Prefijo `COB`

| Columna               | Tipo            | Nulo | Default    | Descripción                                                             |
|-----------------------|-----------------|------|------------|-------------------------------------------------------------------------|
| `client_id`           | `uuid`          | No   |            | FK → `app_clients.id` (`restrictOnDelete`).                             |
| `origin_type`         | `enum`          | No   | `'client'` | `client`, `invoice`, `advance`. Desde dónde se inició el cobro.         |
| `origin_id`           | `uuid`          | Sí   |            | Id de la factura o del anticipo. Nulo cuando `origin_type = client`.    |
| `collection_date`     | `date`          | No   |            |                                                                         |
| `payment_method`      | `enum`          | No   | `'cash'`   | `cash`, `transfer`, `check`, `card`, `advance`, `credit_note`, `other`. |
| `reference`           | `string(60)`    | Sí   |            | Número de cheque, voucher o transferencia.                              |
| `bank_account`        | `string(60)`    | Sí   |            | Cuenta receptora.                                                       |
| `collected_by`        | `uuid`          | Sí   |            | FK → `users.id`. Cobrador o vendedor que recibió.                       |
| `route_id`            | `uuid`          | Sí   |            | FK → `app_routes.id`. Ruta en la que se cobró.                          |
| `currency`            | `string(3)`     | No   | `'USD'`    |                                                                         |
| `exchange_rate`       | `decimal(18,8)` | No   | `1`        |                                                                         |
| `amount`              | `decimal(18,2)` | No   | `0`        | Monto recibido.                                                         |
| `withholding_amount`  | `decimal(18,2)` | No   | `0`        | Retención soportada por el cliente.                                     |
| `applied_amount`      | `decimal(18,2)` | No   | `0`        | Suma de aplicaciones.                                                   |
| `unapplied_amount`    | `decimal(18,2)` | No   | `0`        | Excedente sin aplicar. Ver reglas.                                      |
| `check_number`        | `string(30)`    | Sí   |            |                                                                         |
| `check_date`          | `date`          | Sí   |            | Fecha de cobro de cheques posfechados.                                  |
| `check_status`        | `enum`          | Sí   |            | `pending`, `deposited`, `cleared`, `bounced`.                           |
| `cancelled_at`        | `timestamp`     | Sí   |            |                                                                         |
| `cancellation_reason` | `string(500)`   | Sí   |            |                                                                         |
| `notes`               | `text`          | Sí   |            |                                                                         |

**Estados (`status`):** `draft` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `index(client_id)`, `index(collection_date)`, `index(payment_method)`,
`index(collected_by)`, `index(check_status)`, `index(origin_type, origin_id)`.

**Reglas**

- Con `origin_type` `client` o `invoice`, el `unapplied_amount` que quede al confirmar genera un anticipo `ANC` por ese
  excedente, ya en `confirmed` (el dinero entró con este cobro; no vuelve a pedir aprobación ni genera otro `COB`).
- Con `origin_type = 'advance'` el cobro es el espejo del anticipo: `applied_amount = 0` y
  `unapplied_amount = amount` por definición, y queda **exento** de la regla anterior — el anticipo ya existe. Sus
  únicas acciones son confirmar y anular.

### 6.3 Aplicaciones — `app_client_collection_applications`

Tabla puente que registra **qué documento abona qué factura**. La usan cobros, anticipos y notas de crédito. Es tabla de
detalle: lleva `company_id` y `status`, pero no `code` (se identifica por la factura y su origen).

| Columna                     | Tipo            | Nulo | Default    | Descripción                                        |
|-----------------------------|-----------------|------|------------|----------------------------------------------------|
| `id`                        | `uuid`          | No   |            | PK.                                                |
| `company_id`                | `uuid`          | No   |            | FK → `app_companies.id`.                           |
| `sales_invoice_id`          | `uuid`          | No   |            | FK → `app_sales_invoices.id` (`restrictOnDelete`). |
| `source_type`               | `enum`          | No   |            | `collection`, `advance`, `credit_note`.            |
| `source_id`                 | `uuid`          | No   |            | Id del cobro, anticipo o nota de crédito.          |
| `applied_amount`            | `decimal(18,2)` | No   | `0`        | Monto aplicado.                                    |
| `applied_at`                | `datetime`      | No   |            |                                                    |
| `exchange_rate`             | `decimal(18,8)` | No   | `1`        |                                                    |
| `exchange_difference`       | `decimal(18,2)` | No   | `0`        | Diferencial cambiario.                             |
| `status`                    | `enum`          | No   | `'active'` | `active`, `reversed`.                              |
| `created_by`                | `uuid`          | Sí   |            | FK → `users.id`.                                   |
| `created_at` / `updated_at` | `timestamp`     | Sí   |            |                                                    |

**Índices:** `index(sales_invoice_id)`, `index(source_type, source_id)`, `index(applied_at)`,
`unique(sales_invoice_id, source_type, source_id)`.

**Reglas**

- La suma de aplicaciones activas no puede superar el `total` de la factura.
- Cada aplicación actualiza `paid_amount`, `balance` y `payment_status` de la factura y el saldo del cliente.
- Un cheque rebotado (`check_status = bounced`) revierte automáticamente sus aplicaciones y las marca como `reversed`,
  restaurando el saldo del cliente.

---

## 7. Devoluciones de ventas

Acuerdo de reingreso de mercancía desde el cliente. Deriva normalmente en una nota de crédito. **No mueve
inventario**: el reingreso físico lo asienta la Entrada. Ver [inventario.md § 4.1](inventario.md).

### 7.1 Cabecera — `app_sales_returns` — Prefijo `DVV`

| Columna            | Tipo            | Nulo | Default       | Descripción                                                                                    |
|--------------------|-----------------|------|---------------|------------------------------------------------------------------------------------------------|
| `client_id`        | `uuid`          | No   |               | FK → `app_clients.id` (`restrictOnDelete`).                                                    |
| `sales_invoice_id` | `uuid`          | Sí   |               | FK → `app_sales_invoices.id`.                                                                  |
| `dispatch_id`      | `uuid`          | Sí   |               | FK → `app_dispatches.id`. Despacho de origen.                                                  |
| `warehouse_id`     | `uuid`          | No   |               | FK → `app_warehouses.id`. Bodega de reingreso.                                                 |
| `return_date`      | `date`          | No   |               |                                                                                                |
| `reason`           | `enum`          | No   | `'damaged'`   | `damaged`, `wrong_item`, `expired`, `excess`, `quality`, `client_cancellation`, `other`.       |
| `reason_detail`    | `string(500)`   | Sí   |               |                                                                                                |
| `condition`        | `enum`          | No   | `'resalable'` | `resalable` (reingresa a bodega de venta), `damaged` (va a cuarentena), `scrap` (se destruye). |
| `currency`         | `string(3)`     | No   | `'USD'`       |                                                                                                |
| `exchange_rate`    | `decimal(18,8)` | No   | `1`           |                                                                                                |
| `subtotal`         | `decimal(18,2)` | No   | `0`           |                                                                                                |
| `tax_amount`       | `decimal(18,2)` | No   | `0`           |                                                                                                |
| `total`            | `decimal(18,2)` | No   | `0`           |                                                                                                |
| `credit_note_id`   | `uuid`          | Sí   |               | FK → `app_sales_credit_notes.id`. Nota generada.                                               |
| `received_by`      | `uuid`          | Sí   |               | FK → `users.id`. Quién recibió la mercancía.                                                   |
| `cancelled_at`     | `timestamp`     | Sí   |               |                                                                                                |
| `notes`            | `text`          | Sí   |               |                                                                                                |

**Estados (`status`):** `draft` → `confirmed` → `completed` (acreditada), o `cancelled`.

**Índices:** `index(client_id)`, `index(sales_invoice_id)`, `index(return_date)`, `index(warehouse_id)`.

### 7.2 Líneas — `app_sales_return_lines`

Además de las columnas comunes de línea:

| Columna                 | Tipo            | Nulo | Descripción                                      |
|-------------------------|-----------------|------|--------------------------------------------------|
| `sales_invoice_line_id` | `uuid`          | Sí   | FK → línea facturada.                            |
| `lot_id`                | `uuid`          | Sí   | FK → `app_item_lots.id`.                         |
| `serial_id`             | `uuid`          | Sí   | FK → `app_item_serials.id`.                      |
| `location_id`           | `uuid`          | Sí   | Ubicación de reingreso.                          |
| `unit_cost`             | `decimal(18,6)` | No   | Costo al que reingresa: el de la venta original. |
| `condition`             | `enum`          | Sí   | Condición específica de la línea.                |

**Reglas**

- Al confirmar apunta lo devuelto en la línea de la factura de origen y habilita la nota de crédito. No toca el kardex:
  el reingreso físico lo asienta la Entrada, al costo original de la venta.
- `condition = damaged` reingresa a una bodega `quarantine`; `condition = scrap` no reingresa stock y se registra como
  pérdida vía Ajuste.
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
     │            │                                        │        ▲
     │            │                                        │        │ (aprobar → cobro espejo)
     │            │                                        ├── app_client_advances
     │            │                                        └── app_sales_credit_notes
     │            │
     └──> app_sales_returns ──> app_sales_credit_notes ──> kardex (in)
```
