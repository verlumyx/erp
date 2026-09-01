# Pendientes

Flujos que las fichas de módulo describen y que el código todavía no ejecuta. **Todos los módulos, tablas y pantallas
existen**: lo que falta aquí es comportamiento, no estructura.

> Cada pendiente cita el documento que lo especifica y el archivo donde hoy se corta el camino. Al cerrarlo, tacha la
> fila del índice y borra su sección.

| #   | Pendiente                                            | Grupo             | Bloquea la operación |
|-----|------------------------------------------------------|-------------------|----------------------|
| [1](#1-aplicar-el-crédito-a-favor-a-facturas) | Aplicar el crédito a favor a facturas | Ventas / Compras  | Sí                   |
| [2](#2-el-excedente-del-cobro-o-del-pago-no-genera-anticipo) | El excedente del cobro no genera anticipo | Ventas / Compras  | Sí                   |
| [3](#3-confirmar-una-factura-de-compra-no-hace-nada) | Confirmar una factura de compra no hace nada | Compras           | Sí                   |
| [4](#4-la-factura-de-venta-directa-no-toca-el-kardex) | La factura de venta directa no toca el kardex | Ventas            | Sí                   |
| [5](#5-reserva-y-mercancía-en-tránsito)  | Reserva y mercancía en tránsito       | Ventas / Compras  | No                   |
| [6](#6-control-de-crédito-del-cliente)   | Control de crédito del cliente        | Ventas            | No                   |
| [7](#7-vencimiento-diario-de-facturas)   | Vencimiento diario de facturas        | Ventas / Compras  | No                   |
| [8](#8-menores)                          | Menores                               | Transversal       | No                   |

**Orden sugerido:** 3 → 1 → 2 → 4. El 3 deja las cuentas por pagar permanentemente en cero —nada las carga—, así que el
módulo de pagos está operando sobre un saldo que nunca sube. El 1 y el 2 completan el ciclo de cobranza. El 5 y el 6 son
control, no operación.

---

## 1. Aplicar el crédito a favor a facturas

**Especificado en:** [ventas.md](ventas.md) §4, §5, §6.3 · [compras.md](compras.md) §4, §5, §6.3

Las dos tablas puente admiten tres orígenes, pero solo se escribe uno.

```php
// app/Modules/ClientCollection/Models/ClientCollectionApplication.php:33
public const SOURCE_TYPES = ['collection', 'advance', 'credit_note'];
```

```php
// app/Modules/ClientCollection/Repositories/ClientCollectionRepository.php:212
'source_type' => ClientCollection::APPLICATION_SOURCE,   // siempre 'collection'
```

El espejo exacto está en `SupplierPaymentRepository.php:198`.

**Qué se rompe**

- `applied_amount` y `balance` de `ANC`, `ANP`, `NCC` y `NCP` **nunca se incrementan**: no existe una sola línea que los
  mueva. Un anticipo confirmado queda disponible para siempre.
- La nota de crédito de venta baja el `current_balance` del cliente (`SalesCreditNoteRepository.php:433`) pero no abona
  ninguna factura: el `paid_amount`, `balance` y `payment_status` de la `FVE` quedan intactos. El saldo del cliente y la
  suma de sus facturas abiertas dejan de cuadrar.
- La nota de crédito de compra no hace ni siquiera eso: no toca el saldo del proveedor (ver [3](#3-confirmar-una-factura-de-compra-no-hace-nada)).
- La pantalla de cobro ya calcula y muestra el indicador «Crédito a favor»
  (`resources/js/pages/client-collections/hooks/useClientCollectionForm.ts:359`), pero no hay camino para gastarlo.

**Qué falta construir**

- Escribir aplicaciones con `source_type = 'advance'` y `source_type = 'credit_note'`, respetando
  `unique(sales_invoice_id, source_type, source_id)`.
- Mover `applied_amount` / `balance` del origen y avanzar su `status` a `partial` / `completed` cuando se agota.
- Reusar `SalesInvoiceApplyCollectionService` y `PurchaseInvoiceApplyPaymentService` para el lado de la factura: ya
  saben recalcular `paid_amount`, `balance` y `payment_status`.
- Ofrecer los anticipos y las notas con saldo en el formulario de cobro y de pago, con `payment_method` `advance` y
  `credit_note` —ambos ya están en `PAYMENT_METHODS`—.
- Aplicar solo desde `confirmed`, y no permitir anular un origen con `applied_amount > 0`.

---

## 2. El excedente del cobro o del pago no genera anticipo

**Especificado en:** [ventas.md](ventas.md) §6.2 · [compras.md](compras.md) §6.2

Ya está marcado en el propio código:

```php
// app/Modules/ClientCollection/Services/ClientCollectionPostingService.php:33
 * Pendiente: con `origin_type` `client` o `invoice`, el `unapplied_amount` que
 * quede al confirmar debe generar un anticipo `ANC` ya confirmado por ese
 * excedente (`docs/ventas.md` §6.2).
```

Igual en `SupplierPaymentPostingService.php:33`.

El `unapplied_amount` se calcula y se guarda (`ClientCollectionRepository.php:252`), pero se queda ahí: el dinero entró
y no queda registrado como saldo a favor del cliente.

**Qué falta construir**

- Al confirmar un cobro con `origin_type` `client` o `invoice` y `unapplied_amount > 0`, crear un `ANC` **ya en
  `confirmed`** por ese excedente —no vuelve a pedir aprobación ni genera otro `COB`, el dinero ya entró con este—,
  copiando cliente, fecha, método, referencia, moneda y tasa.
- Sumar ese monto al `advance_balance` del cliente vía `ClientApplyBalanceService`.
- Dejar **exento** el cobro con `origin_type = 'advance'`: ahí `unapplied_amount = amount` por definición y el anticipo
  ya existe.
- Anular el cobro debe anular el anticipo que generó.
- El espejo completo para `PGP` → `ANP`.

---

## 3. Confirmar una factura de compra no hace nada

**Especificado en:** [compras.md](compras.md) §3.2, §4.2

```php
// app/Modules/PurchaseInvoice/Repositories/PurchaseInvoiceRepository.php:123
public function updateStatus(PurchaseInvoice $model, UpdateStatusPurchaseInvoiceCommand $command): void
{
    $attributes = ['status' => $command->status];
    // ... y nada más
}
```

Confirmar una `FCO` solo cambia una columna. La factura de venta sí hace su trabajo
(`SalesInvoiceRepository::confirm()`), así que la asimetría entre los dos ciclos es la mejor referencia de lo que falta.

**Qué falta construir**

| Regla del doc                                                           | Estado |
|-------------------------------------------------------------------------|--------|
| Aumenta `current_balance` del proveedor por `total - withholding_amount` | Falta  |
| Con `affects_inventory = 'yes'` genera movimientos `in` en el kardex     | Falta  |
| Recalcula el costo promedio del artículo con `landed_cost`               | Falta  |
| Avanza la orden de compra que la originó (`sourceable`)                  | Falta  |
| `supplier_invoice_number` único por proveedor y empresa                  | Revisar |
| Anular genera contrapartida y revierte el saldo; no si tiene pagos       | Falta  |

Las piezas ya existen: `SupplierApplyBalanceService`, `InventoryMovementRegisterService`,
`InventoryMovementReverseService` y `ItemApplyAverageCostService`. Falta un `PurchaseInvoicePostingService` que las
orqueste, con la forma de `EntryPostingService`.

**La nota de crédito a proveedor arrastra el mismo hueco:** al confirmar debe disminuir el `current_balance` del
proveedor y, si `affects_inventory`, generar movimientos `out`. Hoy `PurchaseCreditNote` no toca ningún saldo.

---

## 4. La factura de venta directa no toca el kardex

**Especificado en:** [ventas.md](ventas.md) §3.2

```php
// app/Modules/SalesInvoice/Repositories/SalesInvoiceRepository.php:203
 * Los movimientos de inventario que exige `affects_inventory = 'yes'`
 * quedan pendientes del módulo de Movimientos, que aún no existe.
```

El comentario quedó viejo: `InventoryMovement` ya existe y lo usan `Dispatch`, `Entry`, `SalesCreditNote`,
`SalesReturn`, `PurchaseReturn`, `Transfer` y `Adjustment`.

Con `affects_inventory = 'yes'` —la factura directa, sin despacho previo— la mercancía se factura pero no sale del
stock. El `freezeCosts()` de la línea 431 tiene el mismo hueco por el otro lado: con `affects_inventory = 'no'` el costo
lo debe fijar el movimiento del despacho.

**Qué falta construir**

- Un `SalesInvoicePostingService` con la forma de `SalesCreditNotePostingService`: movimientos `out` por línea al
  confirmar, contrapartida al anular.
- Congelar `unit_cost` desde el movimiento generado y recalcular `total_cost` y `margin_amount` de la línea.

---

## 5. Reserva y mercancía en tránsito

**Especificado en:** [ventas.md](ventas.md) §2, §2.2 · [compras.md](compras.md) §2.2 · [inventario.md](inventario.md) §3

`ApplyItemStockMovementCommand` ya declara los dos deltas y `ItemStockApplyMovementService` ya sabe aplicarlos:

```php
// app/Modules/ItemStock/Commands/ApplyItemStockMovementCommand.php:23
public readonly float $reservedDelta = 0,
public readonly float $incomingDelta = 0,
```

**Nadie los pasa distintos de cero.** `reserved_quantity` e `incoming_quantity` son siempre `0`.

**Qué falta construir**

- **Órdenes de venta:** confirmar debe validar `available_quantity` e incrementar `reserved_quantity`; despachar y
  anular deben liberarla. Hoy `SalesOrderUpdateStatusService` es un cambio de estado pelado, sin validación ni efecto.
- **Órdenes de compra:** confirmar debe incrementar `incoming_quantity`; la entrada debe consumirlo.
- **Traslados:** el tránsito debe sumar `incoming_quantity` en la bodega destino.

---

## 6. Control de crédito del cliente

**Especificado en:** [ventas.md](ventas.md) §1, §2.2

Las columnas se guardan y nunca se leen: `credit_limit`, `credit_blocked`, y `approved_by` / `approved_at` de la orden
de venta.

**Qué falta construir**

- Bloquear la venta a crédito cuando `current_balance + total del pedido > credit_limit`, o cuando
  `credit_blocked = 'yes'`.
- Crear el permiso `override_credit_limit` —hoy no existe en el sistema— y usarlo como la autorización explícita que
  levanta el bloqueo, sellando `approved_by` / `approved_at` en la orden.
- Bloquear `unit_price < item.min_price` sin el permiso correspondiente.

---

## 7. Vencimiento diario de facturas

**Especificado en:** [compras.md](compras.md) §3.2

`payment_status = overdue` solo se recalcula cuando entra un cobro o un pago
(`SalesInvoiceApplyCollectionService:70`, `PurchaseInvoiceApplyPaymentService:70`). Una factura que vence sin que nadie
la toque se queda en `pending` para siempre.

**Qué falta construir**

- Un comando artesanal diario que marque `overdue` comparando `due_date` con la fecha actual, sobre `FVE` y `FCO` con
  `balance > 0`. `app/Console/Commands/` todavía no existe.
- **Además, hay un agendado roto:** `routes/console.php:12` programa `Schedule::command('sales:expire')->dailyAt('00:30')`
  y ese comando no existe en el proyecto. Es residuo de una plantilla: hay que borrarlo o reemplazarlo por el comando de
  vencimiento.

---

## 8. Menores

| Pendiente                                                                                                                                                          | Doc                                    |
|--------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------|
| Desactivar una **bodega** con existencia distinta de cero o documentos pendientes no se valida (`UpdateStatusWarehouseRequest` no tiene la comprobación).           | [inventario.md](inventario.md) §2      |
| Desactivar un **artículo** con existencia o documentos abiertos tampoco se valida (`UpdateStatusItemRequest`). Cliente y proveedor sí lo hacen, y son la referencia. | [inventario.md](inventario.md) §1      |
| `route_id` del cobro sin cablear: `ClientCollectionFormOptionsService:34` dice *"cuando exista el módulo de Rutas"* — ya existe.                                     | [ventas.md](ventas.md) §6.2            |
| `monedas.md` §9 marca «Facturas de venta y pagos con `_ves` congelados» como pendiente, pero las columnas y el congelado ya están. **El doc está desactualizado.**   | [monedas.md](monedas.md) §9            |
