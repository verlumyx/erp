# Logística

Movimiento físico de la mercancía. Todos los documentos de este grupo **afectan inventario** y generan movimientos en el
kardex (`app_inventory_movements`).

> Las columnas base (`id`, `company_id`, `code`, `status`, `created_by`, `created_at`, `updated_at`),
> los índices base, la estructura común de las tablas `*_lines`, los tipos numéricos y la política de
> no borrado están descritos en [README.md](README.md) y no se repiten aquí.

| Módulo    | Tabla                                                   | Prefijo | Efecto en kardex                   |
|-----------|---------------------------------------------------------|---------|------------------------------------|
| Despachos | `app_dispatches` + `app_dispatch_lines` (+ trazabilidad) | `DES`   | `out`, o `transfer_out` si sirve un traslado |
| Traslados | `app_transfers` + `app_transfer_lines`                  | `TRA`   | — (los escriben su despacho y su entrada) |
| Entradas  | `app_entries` + `app_entry_lines` (+ trazabilidad)       | `ENT`   | `in`, o `transfer_in` si recibe un traslado |
| Rutas     | `app_routes` (+ `app_route_stops`, `app_route_clients`) | `RUT`   | —                                  |
| Ajustes   | `app_adjustments` + `app_adjustment_lines` (+ lotes y series) | `AJU`   | `adjustment_in` / `adjustment_out` |

**Depende de:** [Inventario](inventario.md) (artículos, bodegas, existencias), [Ventas](ventas.md)
(órdenes de venta) y [Compras](compras.md) (órdenes de compra).

---

## 1. Despachos

Salida física de mercancía hacia el cliente. Descarga inventario y libera la reserva de la orden de venta.

### 1.1 Cabecera — `app_dispatches` — Prefijo `DES`

| Columna                  | Tipo            | Nulo | Default     | Descripción                                                                        |
|--------------------------|-----------------|------|-------------|------------------------------------------------------------------------------------|
| `recipient_type`         | `string(255)`   | Sí   |             | Alias del destinatario en el morph map: `client` o `warehouse`.                    |
| `recipient_id`           | `uuid`          | Sí   |             | Id del destinatario. Con `recipient_type` forma la relación `recipient`.           |
| `sourceable_type`        | `string(255)`   | Sí   |             | Alias del documento origen en el morph map: `sales_order` o `transfer`.            |
| `sourceable_id`          | `uuid`          | Sí   |             | ID del documento origen. Con `sourceable_type` forma la relación `sourceable`.     |
| `client_address_id`      | `uuid`          | Sí   |             | FK → `app_client_addresses.id`. Dirección de entrega.                              |
| `warehouse_id`           | `uuid`          | No   |             | FK → `app_warehouses.id` (`restrictOnDelete`). Bodega de origen.                   |
| `route_id`               | `uuid`          | Sí   |             | FK → `app_routes.id` (`nullOnDelete`). Ruta asignada.                              |
| `route_stop_id`          | `uuid`          | Sí   |             | FK → `app_route_stops.id`. Parada específica.                                      |
| `dispatch_date`          | `date`          | No   |             | Fecha de salida.                                                                   |
| `delivery_date`          | `date`          | Sí   |             | Fecha efectiva de entrega.                                                         |
| `driver_id`              | `uuid`          | Sí   |             | FK → `users.id` (`nullOnDelete`). Conductor.                                       |
| `vehicle_plate`          | `string(20)`    | Sí   |             | Placa del vehículo.                                                                |
| `carrier`                | `string(150)`   | Sí   |             | Transportista externo.                                                             |
| `tracking_number`        | `string(60)`    | Sí   |             | Guía de transporte.                                                                |
| `freight_amount`         | `decimal(18,2)` | No   | `0`         | Costo del flete.                                                                   |
| `total_quantity`         | `decimal(18,4)` | No   | `0`         | Suma de cantidades despachadas.                                                    |
| `total_weight`           | `decimal(18,4)` | No   | `0`         | Peso total; para planificación de carga.                                           |
| `total_volume`           | `decimal(18,4)` | No   | `0`         | Volumen total.                                                                     |
| `total_cost`             | `decimal(18,2)` | No   | `0`         | Costo de la mercancía despachada.                                                  |
| `delivery_status`        | `enum`          | No   | `'pending'` | `pending`, `in_transit`, `delivered`, `partial_delivered`, `rejected`, `returned`. |
| `received_by_name`       | `string(150)`   | Sí   |             | Nombre de quien recibe.                                                            |
| `received_by_document`   | `string(30)`    | Sí   |             | Identificación de quien recibe.                                                    |
| `signature_path`         | `string(500)`   | Sí   |             | Firma digital capturada en la entrega.                                             |
| `evidence_path`          | `string(500)`   | Sí   |             | Foto de la entrega.                                                                |
| `latitude` / `longitude` | `decimal(10,7)` | Sí   |             | Georreferencia de la entrega.                                                      |
| `rejection_reason`       | `string(500)`   | Sí   |             | Motivo si el cliente rechaza.                                                      |
| `cancelled_at`           | `timestamp`     | Sí   |             |                                                                                    |
| `notes`                  | `text`          | Sí   |             |                                                                                    |

**Estados (`status`):** `draft` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `index(recipient_type, recipient_id)`, `index(sourceable_type, sourceable_id)`, `index(dispatch_date)`,
`index(route_id)`, `index(driver_id)`, `index(delivery_status)`, `index(warehouse_id)`.

**Documento origen (`sourceable`)**

El despacho no apunta al pedido con un FK directo: lo hace con una relación polimórfica `sourceable`
(`morphTo`), y la orden de venta lo expone con `morphMany`. Así el mismo par de columnas admite mañana otros
documentos de origen (contrato de suministro, traspaso a consignación) sin agregar una columna por cada uno.
Es el mismo mecanismo que usan las facturas de venta ([Ventas](ventas.md)) y las facturas de compra
([Compras](compras.md)).

- `sourceable_type` guarda el **alias del morph map**, no el FQCN de la clase. El mapa se registra con
  `Relation::enforceMorphMap()` en un service provider, de modo que renombrar o mover la clase no rompe los
  datos ya guardados.
- Tipos admitidos hoy: `sales_order` → `app_sales_orders` y `transfer` → `app_transfers`. Cualquier otro valor es
  inválido y se rechaza en el Request.
- Ambas columnas son nulas: un despacho directo (sin pedido previo) las deja vacías. Si una viene informada, la
  otra es obligatoria.
- El documento origen debe pertenecer a la misma empresa y al mismo destinatario que el despacho: al mismo cliente si
  viene de un pedido, o a la bodega de destino si viene de un traslado.

**Destinatario (`recipient`)**

El despacho no siempre va a un cliente: uno que sirve un traslado lleva la mercancía a otra bodega de la propia
empresa. Por eso el destinatario es polimórfico —`client` o `warehouse`— en lugar de un `client_id` obligatorio. Un
despacho dirigido a una bodega no se factura y no genera parada de ruta.
- Al no ser un FK, la integridad no la garantiza la base de datos: la valida el Service antes de guardar, y el
  origen se protege por la política de no borrado.
- `route_id` y `route_stop_id` **no** entran en el morph: siguen siendo FK directos, porque la ruta es la
  planificación del viaje, no el documento que origina el despacho.
- Las líneas repiten el par (`sourceable_type`, `sourceable_id`) apuntando a la línea del origen
  (`sales_order_line`). El origen de la línea debe pertenecer al mismo documento que el `sourceable` de la
  cabecera.

### 1.2 Líneas — `app_dispatch_lines`

Además de las columnas comunes de línea (los importes son informativos: el despacho no factura):

| Columna               | Tipo            | Nulo | Default | Descripción                                                      |
|-----------------------|-----------------|------|---------|------------------------------------------------------------------|
| `sourceable_type`     | `string(255)`   | Sí   |         | Alias de la línea origen (`sales_order_line`).                   |
| `sourceable_id`       | `uuid`          | Sí   |         | ID de la línea origen. Trazabilidad al pedido.                   |
| `location_id`         | `uuid`          | Sí   |         | Ubicación desde la que se toma.                                  |
| `delivered_quantity`  | `decimal(18,4)` | No   | `0`     | Cantidad efectivamente recibida por el cliente.                  |
| `returned_quantity`   | `decimal(18,4)` | No   | `0`     | Cantidad devuelta en el mismo viaje.                             |
| `unit_cost`           | `decimal(18,6)` | No   | `0`     | Costo unitario al momento de la salida.                          |

**El precio no se captura**

La pantalla del despacho solo pide **qué artículo sale, en qué unidad y cuánto**. `unit_price`,
`discount_percent`, `tax_id`, `tax_percent` y `withholding_percent` los escribe el backend
(`DispatchPricingService`): si la línea despacha una línea del pedido, se copian de ella; si el
despacho es suelto, `unit_price` sale del costo promedio del artículo convertido a la unidad de la
línea, y el resto queda en cero. Un precio enviado desde el cliente se ignora.

**Reglas**

- Al confirmar: genera movimientos `out`, descuenta `quantity` y libera `reserved_quantity`.
- Si `delivered_quantity < quantity`, la diferencia reingresa a bodega con un movimiento `in`
  y el despacho queda en `partial_delivered`. Con varios lotes, lo que vuelve se reparte entre ellos
  en proporción a lo que salió; con series, vuelven las últimas.
- Un despacho rechazado completo (`rejected`) reingresa toda la mercancía.
- Se factura después (`app_sales_invoices.dispatch_id`), con `affects_inventory = 'no'`.

### 1.3 Lotes de la línea — `app_dispatch_line_lots`

El lote dejó de ser una columna de la línea: una misma línea puede salir repartida en varias cajas.

| Columna            | Tipo            | Nulo | Default | Descripción                                             |
|--------------------|-----------------|------|---------|---------------------------------------------------------|
| `dispatch_line_id` | `uuid`          | No   |         | FK → `app_dispatch_lines.id` (`cascadeOnDelete`).       |
| `line_number`      | `integer`       | No   |         | Orden dentro de la línea. Único con `dispatch_line_id`. |
| `lot_id`           | `uuid`          | No   |         | FK → `app_item_lots.id` (`restrictOnDelete`).           |
| `quantity`         | `decimal(18,4)` | No   |         | Cuánto sale de ese lote, en la unidad de la línea.      |
| `base_quantity`    | `decimal(18,4)` | No   | `0`     | Convertida a la unidad base; la calcula el repositorio. |

**Reglas**

- El despacho **consume** trazabilidad: el lote se elige del maestro y nunca nace aquí.
- El lote tiene que ser del artículo de la línea.
- Si hay filas de lote, sus cantidades **suman exactamente** la cantidad de la línea.
- Al confirmar, el kardex escribe **un movimiento `out` por lote**, no uno por línea. La existencia
  se comprueba por lote.

### 1.4 Series de la línea — `app_dispatch_line_serials`

| Columna                | Tipo      | Nulo | Descripción                                                  |
|------------------------|-----------|------|--------------------------------------------------------------|
| `dispatch_line_id`     | `uuid`    | No   | FK → `app_dispatch_lines.id` (`cascadeOnDelete`).            |
| `dispatch_line_lot_id` | `uuid`    | Sí   | FK → `app_dispatch_line_lots.id`. De qué lote sale la unidad. |
| `line_number`          | `integer` | No   | Orden dentro de la línea. Único con `dispatch_line_id`.      |
| `serial_id`            | `uuid`    | No   | FK → `app_item_serials.id` (`restrictOnDelete`).             |

**Reglas**

- Un artículo serializado necesita **una serie por cada unidad base que sale**. Ya no hace falta una
  línea por unidad: una línea de cinco laptops lleva sus cinco series.
- La serie tiene que ser del artículo de la línea y no puede repetirse en el despacho.
- Al confirmar, cada serie es un movimiento `out` de una unidad base.

---

## 2. Traslados

Movimiento de mercancía entre bodegas de la misma empresa. **No cambia el valor total del inventario**, solo su
ubicación.

El traslado **no toca el kardex por su cuenta**: es el documento que ordena el viaje. Confirmarlo genera el
**Despacho** que saca la mercancía del origen, y confirmar ese despacho genera la **Entrada** que la mete en el
destino. Los dos asientos los escriben esos documentos, no el traslado.

**Flujo:** Traslado → Despacho → Entrada.

### 2.1 Cabecera — `app_transfers` — Prefijo `TRA`

| Columna                    | Tipo            | Nulo | Default     | Descripción                                                                  |
|----------------------------|-----------------|------|-------------|------------------------------------------------------------------------------|
| `origin_warehouse_id`      | `uuid`          | No   |             | FK → `app_warehouses.id` (`restrictOnDelete`).                               |
| `destination_warehouse_id` | `uuid`          | No   |             | FK → `app_warehouses.id` (`restrictOnDelete`).                               |
| `transfer_date`            | `date`          | No   |             | Fecha de salida.                                                             |
| `expected_date`            | `date`          | Sí   |             | Fecha estimada de llegada.                                                   |
| `received_date`            | `date`          | Sí   |             | Fecha efectiva de llegada. La escribe la entrada al confirmarse.             |
| `reason`                   | `enum`          | No   | `'restock'` | `restock` (reabastecimiento), `rebalance`, `damaged`, `quarantine`, `other`. |
| `reason_detail`            | `string(500)`   | Sí   |             |                                                                              |
| `driver_id`                | `uuid`          | Sí   |             | FK → `users.id`.                                                             |
| `vehicle_plate`            | `string(20)`    | Sí   |             |                                                                              |
| `route_id`                 | `uuid`          | Sí   |             | FK → `app_routes.id`.                                                        |
| `total_quantity`           | `decimal(18,4)` | No   | `0`         |                                                                              |
| `total_cost`               | `decimal(18,2)` | No   | `0`         | Valor trasladado.                                                            |
| `transfer_status`          | `enum`          | No   | `'pending'` | `pending`, `in_transit`, `received`, `partial_received`. Lo mueven el despacho y la entrada. |
| `sent_by`                  | `uuid`          | Sí   |             | FK → `users.id`. Quién despachó desde origen.                                |
| `received_by`              | `uuid`          | Sí   |             | FK → `users.id`. Quién recibió en destino.                                   |
| `cancelled_at`             | `timestamp`     | Sí   |             |                                                                              |
| `notes`                    | `text`          | Sí   |             |                                                                              |

**Estados (`status`):** `draft` → `confirmed` (despacho generado) → `completed` (entrada confirmada), o `cancelled`.
Cerrarlo no es una decisión de la pantalla: lo cierra la entrada del destino.

**Índices:** `index(origin_warehouse_id)`, `index(destination_warehouse_id)`, `index(transfer_date)`,
`index(transfer_status)`, `index(driver_id)`.

### 2.2 Líneas — `app_transfer_lines`

La línea dice **qué** se mueve, en qué unidad y cuánto, y nada más. Además de las columnas comunes de línea:

| Columna     | Tipo            | Nulo | Default | Descripción                                                     |
|-------------|-----------------|------|---------|------------------------------------------------------------------|
| `unit_cost` | `decimal(18,6)` | No   | `0`     | Costo con el que viaja la mercancía, por unidad base.            |

La ubicación de origen y la de destino ya no viven en la línea: la cabecera dice de qué bodega a cuál va el viaje, y
el kardex toma la ubicación por defecto de cada una. El lote y la serie tampoco: se eligen **al despachar**, que es
cuando alguien tiene la mercancía delante y puede leer el número de la caja.

**Reglas**

- Al confirmar el traslado nace un despacho `DES` en **borrador**, colgado del traslado (`sourceable`), dirigido a la
  bodega de destino y con las líneas que mueven existencia. Un artículo `service` o `non_inventoried` no viaja.
- Al confirmar ese despacho, el kardex escribe `transfer_out` en la bodega de origen —no `out`: la mercancía no se
  vendió, solo cambió de sitio— y nace la entrada `ENT` en borrador, en la bodega de destino, con `entry_type =
  'transfer'` y sin proveedor. Esa entrada cuelga del **traslado** (`sourceable`), igual que el despacho: los dos
  documentos apuntan a lo que originó el movimiento. El despacho que la trajo queda en el `sourceable` de cada
  línea (`dispatch_line`), que es de donde sale el costo con el que la mercancía viajó.
- Al confirmar esa entrada, el kardex escribe `transfer_in` en el destino y el traslado queda `completed`.
- El costo unitario **viaja con la mercancía**: el costo real de la salida se congela en la línea del traslado y es el
  que la entrada usa para valorar el ingreso. El destino recibe al costo del origen, no al suyo.
- Origen y destino no pueden ser la misma bodega.
- Un traslado cuyo despacho ya salió no se anula: primero se anula el despacho. Lo mismo entre despacho y entrada.

---

## 3. Entradas

Recepción física de mercancía en bodega. Su origen habitual es una orden de compra, pero también cubre entradas sin
documento previo (producción, donación, hallazgo).

### 3.1 Cabecera — `app_entries` — Prefijo `ENT`

| Columna             | Tipo            | Nulo | Default      | Descripción                                                                              |
|---------------------|-----------------|------|--------------|------------------------------------------------------------------------------------------|
| `supplier_id`       | `uuid`          | Sí   |              | FK → `app_suppliers.id` (`restrictOnDelete`). Nulo si no viene de un proveedor.          |
| `sourceable_type`   | `string(255)`   | Sí   |              | Alias del documento origen en el morph map: `purchase_order` o `transfer`.               |
| `sourceable_id`     | `uuid`          | Sí   |              | ID del documento origen. Con `sourceable_type` forma la relación `sourceable`.           |
| `warehouse_id`      | `uuid`          | No   |              | FK → `app_warehouses.id` (`restrictOnDelete`). Bodega de recepción.                      |
| `entry_date`        | `date`          | No   |              | Fecha de recepción.                                                                      |
| `entry_type`        | `enum`          | No   | `'purchase'` | `purchase`, `production`, `return`, `donation`, `initial`, `transfer`, `other`.          |
| `supplier_document` | `string(60)`    | Sí   |              | Remisión o guía del proveedor.                                                           |
| `carrier`           | `string(150)`   | Sí   |              | Transportista.                                                                           |
| `tracking_number`   | `string(60)`    | Sí   |              |                                                                                          |
| `received_by`       | `uuid`          | Sí   |              | FK → `users.id`. Quién recibió.                                                          |
| `inspected_by`      | `uuid`          | Sí   |              | FK → `users.id`. Quién hizo control de calidad.                                          |
| `inspection_status` | `enum`          | No   | `'pending'`  | `pending`, `approved`, `rejected`, `partial`.                                            |
| `currency`          | `string(3)`     | No   | `'USD'`      |                                                                                          |
| `exchange_rate`     | `decimal(18,8)` | No   | `1`          |                                                                                          |
| `total_quantity`    | `decimal(18,4)` | No   | `0`          |                                                                                          |
| `freight_amount`    | `decimal(18,2)` | No   | `0`          | Flete a prorratear al costo.                                                             |
| `other_charges`     | `decimal(18,2)` | No   | `0`          | Otros gastos capitalizables (aduana, seguro).                                            |
| `total_cost`        | `decimal(18,2)` | No   | `0`          | Valor total ingresado.                                                                   |
| `is_invoiced`       | `enum`          | No   | `'no'`       | `yes` cuando ya existe factura de compra asociada.                                       |
| `cancelled_at`      | `timestamp`     | Sí   |              |                                                                                          |
| `notes`             | `text`          | Sí   |              |                                                                                          |

**Estados (`status`):** `draft` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `index(supplier_id)`, `index(sourceable_type, sourceable_id)`, `index(entry_date)`,
`index(warehouse_id)`, `index(entry_type)`, `index(is_invoiced)`.

**Documento origen (`sourceable`)**

La entrada no apunta a la orden con un FK directo: lo hace con una relación polimórfica `sourceable`
(`morphTo`), y la orden de compra la expone con `morphMany`. Así el mismo par de columnas admite mañana otros
documentos de origen (orden de producción, devolución de cliente, contrato de suministro) sin agregar una
columna por cada uno. Es el mismo mecanismo que usan las facturas de compra ([Compras](compras.md)).

- `sourceable_type` guarda el **alias del morph map**, no el FQCN de la clase. El mapa se registra con
  `Relation::enforceMorphMap()` en un service provider, de modo que renombrar o mover la clase no rompe los
  datos ya guardados.
- Tipos admitidos hoy: `purchase_order` → `app_purchase_orders` y `transfer` → `app_transfers`. Cualquier otro valor
  es inválido y se rechaza en el Request.
- La entrada que recibe un traslado cuelga del **traslado**, no del despacho que la generó: lo que hay que poder
  reconocer al mirarla es qué originó el movimiento, y eso es el traslado. El despacho y el traslado son hermanos:
  los dos apuntan al mismo `sourceable`. El despacho queda trazado línea a línea (ver abajo), que es donde hace
  falta —de ahí sale el costo con el que la mercancía viajó—.
- Ambas columnas son nulas: las entradas sin documento previo (`production`, `donation`, `initial`) las dejan
  vacías. Si una viene informada, la otra es obligatoria.
- Una entrada que sale de una **orden de compra** debe apuntar a una de la misma empresa y del mismo proveedor. Sin
  `supplier_id` no puede salir de una orden. La que recibe un **traslado** no tiene proveedor —la mercancía ya era
  de la empresa— y no se comprueba contra ninguna orden: lo que puede llegar ya lo comprobó el despacho al sacarlo.
- Al no ser un FK, la integridad no la garantiza la base de datos: la valida el Service antes de guardar, y el
  origen se protege por la política de no borrado.
- Las líneas repiten el par (`sourceable_type`, `sourceable_id`) apuntando a la línea del origen:
  `purchase_order_line` en una entrada de compra, `dispatch_line` en la que recibe un traslado. En una entrada de
  compra el origen de la línea debe pertenecer al mismo documento que el `sourceable` de la cabecera.

### 3.2 Líneas — `app_entry_lines`

Además de las columnas comunes de línea:

| Columna                  | Tipo            | Nulo | Default | Descripción                                            |
|--------------------------|-----------------|------|---------|--------------------------------------------------------|
| `sourceable_type`        | `string(255)`   | Sí   |         | Alias de la línea origen (`purchase_order_line` o `dispatch_line`). |
| `sourceable_id`          | `uuid`          | Sí   |         | ID de la línea origen. Trazabilidad a la orden.        |
| `location_id`            | `uuid`          | Sí   |         | Ubicación donde se almacena.                           |
| `received_quantity`      | `decimal(18,4)` | No   | `0`     | Cantidad aceptada.                                     |
| `rejected_quantity`      | `decimal(18,4)` | No   | `0`     | Cantidad rechazada en inspección.                      |
| `unit_cost`              | `decimal(18,6)` | No   | `0`     | Costo antes de prorrateos.                             |
| `landed_cost`            | `decimal(18,6)` | No   | `0`     | Costo final con flete y gastos prorrateados.           |
| `rejection_reason`       | `string(500)`   | Sí   |         |                                                        |

**El costo no se captura**

La pantalla de la entrada solo pide **qué artículo llega, en qué unidad y cuánto se recibe**.
`unit_price`, `discount_percent`, `tax_id`, `tax_percent` y `withholding_percent` los escribe el
backend (`EntryPricingService`): si la línea recibe una línea de la orden de compra, se copian de
ella; si la entrada es suelta —producción, donación, inventario inicial—, `unit_price` sale del
costo promedio del artículo convertido a la unidad de la línea, y el resto queda en cero. Un costo
enviado desde el cliente se ignora.

> Un artículo estrenado todavía no tiene promedio, así que un inventario inicial entra a costo cero.
> Se corrige después con un ajuste de revaluación: el costo no vuelve a la pantalla de la entrada
> solo por ese caso.

**Reglas**

- Al confirmar genera movimientos `in` con `landed_cost` y recalcula el costo promedio del artículo.
- El prorrateo de `freight_amount` y `other_charges` se distribuye por valor de línea (por defecto)
  o por peso, según configuración.
- La cantidad recibida no puede superar lo pendiente de la orden de compra sin autorización (permiso
  `allow_over_receipt`).
- `entry_type = initial` es el mecanismo de carga del inventario inicial; solo se permite una vez por artículo/bodega.
- `rejected_quantity` no ingresa a stock: se registra para el reclamo al proveedor.

### 3.3 Lotes de la línea — `app_entry_line_lots`

| Columna         | Tipo            | Nulo | Default | Descripción                                                   |
|-----------------|-----------------|------|---------|---------------------------------------------------------------|
| `entry_line_id` | `uuid`          | No   |         | FK → `app_entry_lines.id` (`cascadeOnDelete`).                |
| `line_number`   | `integer`       | No   |         | Orden dentro de la línea. Único con `entry_line_id`.          |
| `lot_number`    | `string(60)`    | No   |         | El número impreso en la caja. Es lo único que se captura.     |
| `lot_id`        | `uuid`          | Sí   |         | FK → `app_item_lots.id` (`nullOnDelete`). Se resuelve al confirmar. |
| `expires_at`    | `date`          | Sí   |         | Vencimiento de **ese** lote.                                  |
| `quantity`      | `decimal(18,4)` | No   |         | Cuánto llegó en ese lote, en la unidad de la línea.           |
| `base_quantity` | `decimal(18,4)` | No   | `0`     | Convertida a la unidad base; la calcula el repositorio.       |

**Reglas**

- La entrada **crea** trazabilidad: el número se busca en `app_item_lots` al confirmar y se da de
  alta si no existía, por `ItemLotCreateService`. En borrador `lot_id` está vacío.
- Si hay filas de lote, sus cantidades **suman exactamente** la cantidad que se recibe en la línea.
- Al confirmar, el kardex escribe **un movimiento `in` por lote**. Lo que la inspección rechaza se
  reparte entre los lotes en proporción a lo que trajo cada uno: nadie decidió de qué caja salía lo
  malo.

### 3.4 Series de la línea — `app_entry_line_serials`

| Columna             | Tipo          | Nulo | Descripción                                                |
|---------------------|---------------|------|------------------------------------------------------------|
| `entry_line_id`     | `uuid`        | No   | FK → `app_entry_lines.id` (`cascadeOnDelete`).             |
| `entry_line_lot_id` | `uuid`        | Sí   | FK → `app_entry_line_lots.id`. De qué lote es la unidad.   |
| `line_number`       | `integer`     | No   | Orden dentro de la línea. Único con `entry_line_id`.       |
| `serial_number`     | `string(100)` | No   | El número impreso en la unidad.                            |
| `serial_id`         | `uuid`        | Sí   | FK → `app_item_serials.id`. Se resuelve al confirmar.      |

**Reglas**

- Un artículo serializado necesita **una serie por cada unidad base aceptada**.
- Una serie no puede repetirse dentro de la entrada.
- Al confirmar, cada serie es un movimiento `in` de una unidad base, y la serie nace en el maestro si
  el proveedor la estrena.

---

## 4. Rutas

Recorridos de entrega y cobro. Agrupan clientes y ordenan las paradas para despachos y cobranza.

### 4.1 Cabecera — `app_routes` — Prefijo `RUT`

| Columna                      | Tipo            | Nulo | Default      | Descripción                                                                 |
|------------------------------|-----------------|------|--------------|-----------------------------------------------------------------------------|
| `name`                       | `string(150)`   | No   |              | Nombre de la ruta ("Zona Norte - Lunes").                                   |
| `description`                | `text`          | Sí   |              |                                                                             |
| `type`                       | `enum`          | No   | `'delivery'` | `delivery` (entrega), `collection` (cobranza), `sales` (preventa), `mixed`. |
| `warehouse_id`               | `uuid`          | Sí   |              | FK → `app_warehouses.id`. Bodega de salida.                                 |
| `driver_id`                  | `uuid`          | Sí   |              | FK → `users.id` (`nullOnDelete`). Conductor asignado.                       |
| `salesperson_id`             | `uuid`          | Sí   |              | FK → `users.id`. Vendedor asignado.                                         |
| `vehicle_plate`              | `string(20)`    | Sí   |              |                                                                             |
| `vehicle_capacity_weight`    | `decimal(18,4)` | No   | `0`          | Capacidad en peso.                                                          |
| `vehicle_capacity_volume`    | `decimal(18,4)` | No   | `0`          | Capacidad en volumen.                                                       |
| `frequency`                  | `enum`          | No   | `'weekly'`   | `daily`, `weekly`, `biweekly`, `monthly`, `on_demand`.                      |
| `weekdays`                   | `json`          | Sí   |              | Días de ejecución (`["mon","wed","fri"]`).                                  |
| `zone`                       | `string(100)`   | Sí   |              | Zona geográfica.                                                            |
| `city`                       | `string(100)`   | Sí   |              |                                                                             |
| `estimated_duration_minutes` | `integer`       | No   | `0`          | Duración estimada del recorrido.                                            |
| `estimated_distance_km`      | `decimal(10,2)` | No   | `0`          | Distancia estimada.                                                         |
| `notes`                      | `text`          | Sí   |              |                                                                             |

**Índices:** `unique(company_id, name)`, `index(type)`, `index(driver_id)`, `index(warehouse_id)`, `index(zone)`.

### 4.2 Paradas — `app_route_stops`

Ejecución de la ruta en una fecha concreta.

| Columna                     | Tipo            | Nulo | Default     | Descripción                                             |
|-----------------------------|-----------------|------|-------------|---------------------------------------------------------|
| `id`                        | `uuid`          | No   |             | PK.                                                     |
| `company_id`                | `uuid`          | No   |             | FK → `app_companies.id`.                                |
| `route_id`                  | `uuid`          | No   |             | FK → `app_routes.id` (`cascadeOnDelete`).               |
| `client_id`                 | `uuid`          | No   |             | FK → `app_clients.id` (`restrictOnDelete`).             |
| `client_address_id`         | `uuid`          | Sí   |             | FK → `app_client_addresses.id`.                         |
| `stop_date`                 | `date`          | No   |             | Fecha planificada de la parada.                         |
| `sequence`                  | `integer`       | No   | `0`         | Orden de visita.                                        |
| `estimated_arrival`         | `time`          | Sí   |             | Hora estimada de llegada.                               |
| `actual_arrival`            | `datetime`      | Sí   |             | Hora real registrada.                                   |
| `actual_departure`          | `datetime`      | Sí   |             |                                                         |
| `stop_status`               | `enum`          | No   | `'pending'` | `pending`, `arrived`, `completed`, `skipped`, `failed`. |
| `skip_reason`               | `string(500)`   | Sí   |             | Motivo si no se visitó (cerrado, no recibió).           |
| `latitude` / `longitude`    | `decimal(10,7)` | Sí   |             | Ubicación al registrar la visita.                       |
| `status`                    | `enum`          | No   | `'active'`  |                                                         |
| `created_at` / `updated_at` | `timestamp`     | Sí   |             |                                                         |

**Índices:** `unique(route_id, stop_date, client_id)`, `index(stop_date)`, `index(client_id)`,
`index(stop_status)`, `index(sequence)`.

### 4.3 Clientes de la ruta — `app_route_clients`

Asignación fija de clientes a una ruta (plantilla desde la que se generan las paradas).

| Columna                     | Tipo        | Nulo | Descripción                                   |
|-----------------------------|-------------|------|-----------------------------------------------|
| `id`                        | `uuid`      | No   | PK.                                           |
| `company_id`                | `uuid`      | Sí   | FK → `app_companies.id`. Heredado de la ruta. |
| `route_id`                  | `uuid`      | No   | FK → `app_routes.id` (`cascadeOnDelete`).     |
| `client_id`                 | `uuid`      | No   | FK → `app_clients.id` (`cascadeOnDelete`).    |
| `client_address_id`         | `uuid`      | Sí   | FK → `app_client_addresses.id`.               |
| `sequence`                  | `integer`   | No   | Orden habitual de visita.                     |
| `status`                    | `enum`      | No   | `active` / `inactive`.                        |
| `created_at` / `updated_at` | `timestamp` | Sí   |                                               |

**Índices:** `unique(route_id, client_id, client_address_id)`, `index(company_id)`, `index(status)`.

**Reglas**

- Las paradas de una fecha se generan a partir de `app_route_clients` más los despachos pendientes asignados a la ruta.
- Al planificar se valida que el peso y volumen de los despachos no superen la capacidad del vehículo.
- No se puede desactivar una ruta con paradas o despachos pendientes.

---

## 5. Ajustes

Corrección de existencias por conteo físico, merma, daño, vencimiento o error de captura. Es el **único** módulo que
modifica stock sin un documento comercial detrás, y por eso exige motivo y autorización.

### 5.1 Cabecera — `app_adjustments` — Prefijo `AJU`

| Columna               | Tipo            | Nulo | Default            | Descripción                                                                                                                           |
|-----------------------|-----------------|------|--------------------|---------------------------------------------------------------------------------------------------------------------------------------|
| `warehouse_id`        | `uuid`          | No   |                    | FK → `app_warehouses.id` (`restrictOnDelete`).                                                                                        |
| `adjustment_date`     | `date`          | No   |                    |                                                                                                                                       |
| `type`                | `enum`          | No   | `'physical_count'` | `physical_count` (conteo físico), `loss` (merma), `damage`, `expiration`, `theft`, `correction`, `revaluation` (solo costo), `other`. |
| `direction`           | `enum`          | No   | `'mixed'`          | `in` (solo aumentos), `out` (solo disminuciones), `mixed`.                                                                            |
| `reason`              | `string(500)`   | No   |                    | Justificación obligatoria.                                                                                                            |
| `count_id`            | `string(60)`    | Sí   |                    | Identificador del conteo físico asociado.                                                                                             |
| `total_quantity_in`   | `decimal(18,4)` | No   | `0`                | Suma de aumentos.                                                                                                                     |
| `total_quantity_out`  | `decimal(18,4)` | No   | `0`                | Suma de disminuciones.                                                                                                                |
| `total_cost_in`       | `decimal(18,2)` | No   | `0`                | Valor de los aumentos.                                                                                                                |
| `total_cost_out`      | `decimal(18,2)` | No   | `0`                | Valor de las disminuciones.                                                                                                           |
| `net_cost`            | `decimal(18,2)` | No   | `0`                | `total_cost_in - total_cost_out`. Impacto en el valor del inventario.                                                                 |
| `approved_by`         | `uuid`          | Sí   |                    | FK → `users.id`. Obligatorio para confirmar.                                                                                          |
| `approved_at`         | `timestamp`     | Sí   |                    |                                                                                                                                       |
| `cancelled_at`        | `timestamp`     | Sí   |                    |                                                                                                                                       |
| `cancellation_reason` | `string(500)`   | Sí   |                    |                                                                                                                                       |
| `attachment_path`     | `string(500)`   | Sí   |                    | Acta de conteo o evidencia.                                                                                                           |
| `notes`               | `text`          | Sí   |                    |                                                                                                                                       |

**Estados (`status`):** `draft` → `pending_approval` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `index(warehouse_id)`, `index(adjustment_date)`, `index(type)`, `index(approved_by)`.

### 5.2 Líneas — `app_adjustment_lines`

| Columna                     | Tipo            | Nulo | Default    | Descripción                                                   |
|-----------------------------|-----------------|------|------------|---------------------------------------------------------------|
| `id`                        | `uuid`          | No   |            | PK.                                                           |
| `company_id`                | `uuid`          | Sí   |            | FK → `app_companies.id`. Heredado del ajuste.                 |
| `adjustment_id`             | `uuid`          | No   |            | FK → `app_adjustments.id` (`cascadeOnDelete`).                |
| `line_number`               | `integer`       | No   |            |                                                               |
| `item_id`                   | `uuid`          | No   |            | FK → `app_items.id` (`restrictOnDelete`).                     |
| `measurement_unit_id`       | `uuid`          | No   |            | FK → `app_measurement_units.id`.                              |
| `location_id`               | `uuid`          | Sí   |            | FK → `app_warehouse_locations.id`.                            |
| `system_quantity`           | `decimal(18,4)` | No   | `0`        | Existencia según el sistema al momento del conteo.            |
| `counted_quantity`          | `decimal(18,4)` | No   | `0`        | Existencia física contada.                                    |
| `difference_quantity`       | `decimal(18,4)` | No   | `0`        | `counted_quantity - system_quantity`. Positivo = sobrante.    |
| `base_quantity`             | `decimal(18,4)` | No   | `0`        | Diferencia convertida a unidad base.                          |
| `movement_type`             | `enum`          | No   |            | `adjustment_in` o `adjustment_out`, derivado del signo.       |
| `unit_cost`                 | `decimal(18,6)` | No   | `0`        | Costo aplicado al ajuste.                                     |
| `total_cost`                | `decimal(18,2)` | No   | `0`        | `abs(base_quantity) * unit_cost`.                             |
| `reason`                    | `string(500)`   | Sí   |            | Motivo específico de la línea.                                |
| `counted_by`                | `uuid`          | Sí   |            | FK → `users.id`. Quién contó.                                 |
| `status`                    | `enum`          | No   | `'active'` | `active` / `inactive`. Los totales suman solo líneas activas. |
| `notes`                     | `string(500)`   | Sí   |            |                                                               |
| `created_at` / `updated_at` | `timestamp`     | Sí   |            |                                                               |

**Índices:** `index(adjustment_id)`, `index(item_id)`, `unique(adjustment_id, line_number)`,
`index(company_id)`, `index(status)`.

### 5.3 Lotes de la línea — `app_adjustment_line_lots`

El lote no cabe en la línea: un mismo artículo se cuenta repartido en varias cajas, y cada una tiene
su propia existencia —el kardex guarda el saldo por lote—. Cada fila es una mini-línea con su propio
cálculo.

| Columna                     | Tipo            | Nulo | Default    | Descripción                                                    |
|-----------------------------|-----------------|------|------------|----------------------------------------------------------------|
| `id`                        | `uuid`          | No   |            | PK.                                                            |
| `company_id`                | `uuid`          | Sí   |            | FK → `app_companies.id`. Heredado de la línea.                 |
| `adjustment_line_id`        | `uuid`          | No   |            | FK → `app_adjustment_lines.id` (`cascadeOnDelete`).            |
| `line_number`               | `integer`       | No   |            |                                                                |
| `lot_id`                    | `uuid`          | No   |            | FK → `app_item_lots.id` (`restrictOnDelete`). Del maestro.     |
| `counted_quantity`          | `decimal(18,4)` | No   | `0`        | Lo que se encontró de ese lote, en la unidad de la línea.      |
| `system_quantity`           | `decimal(18,4)` | No   | `0`        | Lo que el sistema decía de ese lote.                           |
| `difference_quantity`       | `decimal(18,4)` | No   | `0`        | `counted - system` del lote.                                   |
| `base_quantity`             | `decimal(18,4)` | No   | `0`        | Esa diferencia en unidad base.                                 |
| `movement_type`             | `enum`          | No   |            | `adjustment_in` o `adjustment_out`, derivado del signo.        |
| `unit_cost`                 | `decimal(18,6)` | No   | `0`        | Costo con el que se valora ese lote.                           |
| `total_cost`                | `decimal(18,2)` | No   | `0`        | `abs(base_quantity) * unit_cost`.                              |
| `status`                    | `enum`          | No   | `'active'` | `active` / `inactive`.                                         |
| `notes`                     | `string(500)`   | Sí   |            |                                                                |
| `created_at` / `updated_at` | `timestamp`     | Sí   |            |                                                                |

**Índices:** `index(adjustment_line_id)`, `index(lot_id)`, `index(company_id)`, `index(status)`,
`unique(adjustment_line_id, line_number)`.

### 5.4 Series de la línea — `app_adjustment_line_serials`

Sin cantidad: una serie **es** una unidad. La fila solo nombra qué unidad entra en el conteo.

| Columna                     | Tipo        | Nulo | Default    | Descripción                                                      |
|-----------------------------|-------------|------|------------|------------------------------------------------------------------|
| `id`                        | `uuid`      | No   |            | PK.                                                              |
| `company_id`                | `uuid`      | Sí   |            | FK → `app_companies.id`.                                         |
| `adjustment_line_id`        | `uuid`      | No   |            | FK → `app_adjustment_lines.id` (`cascadeOnDelete`).              |
| `adjustment_line_lot_id`    | `uuid`      | Sí   |            | FK → `app_adjustment_line_lots.id`. De qué lote sale la unidad.  |
| `line_number`               | `integer`   | No   |            |                                                                  |
| `serial_id`                 | `uuid`      | No   |            | FK → `app_item_serials.id` (`restrictOnDelete`). Del maestro.    |
| `status`                    | `enum`      | No   | `'active'` | `active` / `inactive`.                                           |
| `created_at` / `updated_at` | `timestamp` | Sí   |            |                                                                  |

**Índices:** `index(adjustment_line_id)`, `index(adjustment_line_lot_id)`, `index(serial_id)`,
`index(company_id)`, `index(status)`, `unique(adjustment_line_id, line_number)`.

**Reglas**

- `system_quantity` se captura al **crear** la línea y se revalida al confirmar: si el stock cambió entre ambos
  momentos, el sistema avisa y exige recontar. Con lotes, la revalidación es **por lote**.
- Si la línea trae filas de lote, lo contado en ellas suma **exactamente** lo contado en la línea, y
  la línea es la suma de sus lotes: su `system_quantity` no es el saldo de toda la ubicación, sino el
  de los lotes que cuenta.
- El kardex escribe **un movimiento por lote**, cada uno con su propia diferencia y su propio costo.
  Sin lotes, un solo movimiento por la línea. El asiento se identifica con una serie solo cuando no
  hay ambigüedad: mueve una unidad y hay una sola serie a la que pueda referirse.
- Las series nombran unidades enteras —las que se contaron o las que faltan—, así que su número tiene
  que ser lo contado o la diferencia, ambos en unidad base.
- Dos líneas activas no pueden contar la misma clave `(artículo, ubicación)`: contar el mismo artículo
  en varios lotes se hace con sus filas de lote, no con otra línea.
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
