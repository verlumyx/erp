# Compras

Ciclo completo de abastecimiento: desde el registro del proveedor hasta el pago y las devoluciones.

> Las columnas base (`id`, `company_id`, `code`, `status`, `created_by`, `created_at`, `updated_at`),
> los índices base, la estructura común de las tablas `*_lines`, los tipos numéricos y la política de
> no borrado están descritos en [README.md](README.md) y no se repiten aquí.

| Módulo | Tabla | Prefijo |
|---|---|---|
| Proveedores | `app_suppliers` (+ `app_supplier_contacts`, `app_supplier_addresses`) | `PRO` |
| Órdenes de compra | `app_purchase_orders` + `app_purchase_order_lines` | `OCO` |
| Facturas de compra | `app_purchase_invoices` + `app_purchase_invoice_lines` | `FCO` |
| Notas de crédito a proveedor | `app_purchase_credit_notes` + `app_purchase_credit_note_lines` | `NCP` |
| Anticipos a proveedor | `app_supplier_advances` | `ANP` |
| Pagos a proveedor | `app_supplier_payments` + `app_supplier_payment_applications` | `PGP` |
| Devoluciones de compras | `app_purchase_returns` + `app_purchase_return_lines` | `DVC` |

**Depende de:** [Catálogo](catalogo.md) (impuestos, tipo de proveedor, unidades) e [Inventario](inventario.md) (artículos, bodegas).

**Flujo:** Orden de compra → Entrada de mercancía → Factura de compra → Pago
(con Anticipos, Notas de crédito y Devoluciones como ramas del mismo ciclo).

---

## 1. Proveedores

Maestro de proveedores y sus condiciones comerciales.

**Tabla:** `app_suppliers` — **Prefijo:** `PRO`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `supplier_type_id` | `uuid` | Sí | | FK → `app_supplier_types.id` (`nullOnDelete`). |
| `name` | `string(200)` | No | | Razón social o nombre comercial. |
| `legal_name` | `string(200)` | Sí | | Nombre legal si difiere del comercial. |
| `tax_id` | `string(30)` | Sí | | Identificación fiscal (RUC/NIT/RFC). |
| `person_type` | `enum` | No | `'company'` | `company` (jurídica) o `individual` (natural). |
| `email` | `string(255)` | Sí | | Correo principal. |
| `phone` | `string(30)` | Sí | | |
| `mobile` | `string(30)` | Sí | | |
| `website` | `string(255)` | Sí | | |
| `address` | `string(500)` | Sí | | Dirección fiscal. |
| `city` | `string(100)` | Sí | | |
| `state` | `string(100)` | Sí | | |
| `country` | `string(100)` | Sí | | |
| `currency` | `string(3)` | No | `'USD'` | Moneda habitual de compra. |
| `payment_term_days` | `integer` | No | `0` | Días de crédito. `0` = contado. |
| `credit_limit` | `decimal(18,2)` | No | `0` | Cupo máximo de deuda con el proveedor. |
| `current_balance` | `decimal(18,2)` | No | `0` | Saldo por pagar. Derivado; lo mantiene el sistema. |
| `advance_balance` | `decimal(18,2)` | No | `0` | Anticipos entregados y no aplicados. |
| `default_tax_id` | `uuid` | Sí | | FK → `app_taxes.id`. Impuesto sugerido en sus documentos. |
| `withholding_tax_id` | `uuid` | Sí | | FK → `app_taxes.id`. Retención aplicable al pagarle. |
| `lead_time_days` | `integer` | No | `0` | Días promedio de entrega; alimenta la sugerencia de reorden. |
| `bank_name` | `string(150)` | Sí | | Para transferencias. |
| `bank_account` | `string(60)` | Sí | | |
| `bank_account_type` | `string(30)` | Sí | | |
| `notes` | `text` | Sí | | |

**Índices:** `unique(company_id, tax_id)`, `unique(company_id, email)`, `index(name)`,
`index(supplier_type_id)`, `index(current_balance)`.

**Reglas**
- `current_balance` y `advance_balance` **nunca** se editan a mano; se recalculan al confirmar facturas,
  notas de crédito, anticipos y pagos.
- No se puede desactivar un proveedor con saldo distinto de cero o documentos abiertos.

### 1.1 Contactos — `app_supplier_contacts`

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | `uuid` | No | PK. |
| `company_id` | `uuid` | Sí | FK → `app_companies.id`. Heredado del proveedor. |
| `supplier_id` | `uuid` | No | FK → `app_suppliers.id` (`cascadeOnDelete`). |
| `name` | `string(150)` | No | |
| `position` | `string(100)` | Sí | Cargo. |
| `email` | `string(255)` | Sí | |
| `phone` | `string(30)` | Sí | |
| `is_primary` | `enum` | No | `yes` / `no`, default `'no'`. Contacto principal. |
| `status` | `enum` | No | `active` / `inactive`. |
| `created_at` / `updated_at` | `timestamp` | Sí | |

**Índices:** `index(supplier_id)`, `index(company_id)`, `index(status)`.

### 1.2 Direcciones — `app_supplier_addresses`

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | `uuid` | No | PK. |
| `company_id` | `uuid` | Sí | FK → `app_companies.id`. Heredado del proveedor. |
| `supplier_id` | `uuid` | No | FK → `app_suppliers.id` (`cascadeOnDelete`). |
| `type` | `enum` | No | `billing`, `pickup`, `warehouse`. |
| `address` | `string(500)` | No | |
| `city` / `state` / `country` | `string(100)` | Sí | |
| `is_default` | `enum` | No | `yes` / `no`, default `'no'`. Dirección sugerida. |
| `status` | `enum` | No | `active` / `inactive`. |
| `created_at` / `updated_at` | `timestamp` | Sí | |

**Índices:** `index(supplier_id)`, `index(company_id)`, `index(status)`.

---

## 2. Órdenes de compra

Solicitud formal de mercancía al proveedor. **No afecta inventario**; solo reserva expectativa de entrada
(`incoming_quantity`).

### 2.1 Cabecera — `app_purchase_orders` — Prefijo `OCO`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `supplier_id` | `uuid` | No | | FK → `app_suppliers.id` (`restrictOnDelete`). |
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id`. Bodega de recepción prevista. |
| `order_date` | `date` | No | | Fecha de emisión. |
| `expected_date` | `date` | Sí | | Fecha estimada de entrega. |
| `supplier_reference` | `string(60)` | Sí | | Número de cotización o referencia del proveedor. |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | Tasa a moneda base al momento del documento. |
| `payment_term_days` | `integer` | No | `0` | Copiado del proveedor; editable. |
| `subtotal` | `decimal(18,2)` | No | `0` | Suma de líneas antes de impuestos. |
| `discount_amount` | `decimal(18,2)` | No | `0` | Descuento global. |
| `tax_amount` | `decimal(18,2)` | No | `0` | |
| `total` | `decimal(18,2)` | No | `0` | |
| `received_percent` | `decimal(7,4)` | No | `0` | Avance de recepción (0–100). |
| `invoiced_percent` | `decimal(7,4)` | No | `0` | Avance de facturación (0–100). |
| `approved_by` | `uuid` | Sí | | FK → `users.id`. Quién aprobó. |
| `approved_at` | `timestamp` | Sí | | |
| `cancelled_at` | `timestamp` | Sí | | |
| `cancellation_reason` | `string(500)` | Sí | | Obligatorio al anular. |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `partial` → `completed`, o `cancelled`.

**Índices:** `index(supplier_id)`, `index(order_date)`, `index(expected_date)`, `index(warehouse_id)`.

### 2.2 Líneas — `app_purchase_order_lines`

Además de las columnas comunes de línea:

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `received_quantity` | `decimal(18,4)` | No | `0` | Cantidad ya recibida vía Entradas. |
| `invoiced_quantity` | `decimal(18,4)` | No | `0` | Cantidad ya facturada. |
| `pending_quantity` | `decimal(18,4)` | No | `0` | `quantity - received_quantity`. |
| `expected_date` | `date` | Sí | | Fecha esperada por línea (entregas parciales). |

**Reglas**
- Al confirmar, se incrementa `incoming_quantity` en `app_item_stocks`.
- Solo se edita en `draft`. Confirmada, se modifica creando una nueva versión o anulando.
- Se cierra automáticamente (`completed`) cuando todas las líneas tienen `pending_quantity = 0`.
- Anular una orden con recepciones parciales exige anular primero las entradas asociadas.

---

## 3. Facturas de compra

Documento de deuda con el proveedor. Genera cuenta por pagar y, si no hubo entrada previa,
**también afecta inventario y costo**.

### 3.1 Cabecera — `app_purchase_invoices` — Prefijo `FCO`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `supplier_id` | `uuid` | No | | FK → `app_suppliers.id` (`restrictOnDelete`). |
| `purchase_order_id` | `uuid` | Sí | | FK → `app_purchase_orders.id`. Orden que origina la factura. |
| `entry_id` | `uuid` | Sí | | FK → `app_entries.id`. Entrada de mercancía asociada. |
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id`. |
| `supplier_invoice_number` | `string(60)` | No | | Número impreso de la factura del proveedor. |
| `supplier_invoice_series` | `string(20)` | Sí | | Serie fiscal. |
| `invoice_date` | `date` | No | | Fecha de emisión del proveedor. |
| `received_date` | `date` | Sí | | Fecha de recepción del documento. |
| `due_date` | `date` | No | | Vencimiento = `invoice_date + payment_term_days`. |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `affects_inventory` | `enum` | No | `'yes'` | `no` cuando el stock ya entró con una Entrada previa. |
| `subtotal` | `decimal(18,2)` | No | `0` | |
| `discount_amount` | `decimal(18,2)` | No | `0` | |
| `tax_amount` | `decimal(18,2)` | No | `0` | |
| `withholding_amount` | `decimal(18,2)` | No | `0` | Retenciones aplicadas. |
| `freight_amount` | `decimal(18,2)` | No | `0` | Flete prorrateado al costo de las líneas. |
| `other_charges` | `decimal(18,2)` | No | `0` | Otros gastos capitalizables al costo. |
| `total` | `decimal(18,2)` | No | `0` | |
| `paid_amount` | `decimal(18,2)` | No | `0` | Total aplicado por pagos, anticipos y notas de crédito. |
| `balance` | `decimal(18,2)` | No | `0` | `total - paid_amount`. Saldo pendiente. |
| `payment_status` | `enum` | No | `'pending'` | `pending`, `partial`, `paid`, `overdue`. |
| `cancelled_at` | `timestamp` | Sí | | |
| `cancellation_reason` | `string(500)` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `unique(company_id, supplier_id, supplier_invoice_number)`, `index(supplier_id)`,
`index(invoice_date)`, `index(due_date)`, `index(payment_status)`, `index(purchase_order_id)`.

### 3.2 Líneas — `app_purchase_invoice_lines`

Además de las columnas comunes de línea:

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `purchase_order_line_id` | `uuid` | Sí | | FK → `app_purchase_order_lines.id`. Trazabilidad al pedido. |
| `warehouse_id` | `uuid` | Sí | | Bodega de la línea si difiere de la cabecera. |
| `lot_id` | `uuid` | Sí | | FK → `app_item_lots.id`. Obligatorio si el artículo maneja lote. |
| `landed_cost` | `decimal(18,6)` | No | `0` | Costo unitario final incluyendo flete y gastos prorrateados. |
| `returned_quantity` | `decimal(18,4)` | No | `0` | Cantidad devuelta al proveedor. |

**Reglas**
- Al confirmar: si `affects_inventory = 'yes'`, genera movimientos `in` en el kardex y recalcula el
  costo promedio del artículo con `landed_cost`.
- Aumenta `current_balance` del proveedor por `total - withholding_amount`.
- `supplier_invoice_number` es único por proveedor y empresa: bloquea el registro duplicado.
- `payment_status = overdue` lo marca un job diario comparando `due_date` con la fecha actual.
- Anular genera movimientos de contrapartida y revierte el saldo del proveedor. No se puede anular
  si tiene pagos aplicados.

---

## 4. Notas de crédito a proveedor

Disminuye la deuda con el proveedor: descuentos posteriores, devoluciones o correcciones de precio.

### 4.1 Cabecera — `app_purchase_credit_notes` — Prefijo `NCP`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `supplier_id` | `uuid` | No | | FK → `app_suppliers.id` (`restrictOnDelete`). |
| `purchase_invoice_id` | `uuid` | Sí | | FK → `app_purchase_invoices.id`. Factura afectada. |
| `purchase_return_id` | `uuid` | Sí | | FK → `app_purchase_returns.id`. Devolución que la origina. |
| `supplier_document_number` | `string(60)` | Sí | | Número de la nota emitida por el proveedor. |
| `note_date` | `date` | No | | |
| `reason` | `enum` | No | `'return'` | `return` (devolución), `discount`, `price_correction`, `damaged`, `other`. |
| `reason_detail` | `string(500)` | Sí | | Obligatorio si `reason = other`. |
| `affects_inventory` | `enum` | No | `'no'` | `yes` cuando la nota implica salida física de mercancía. |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `subtotal` | `decimal(18,2)` | No | `0` | |
| `tax_amount` | `decimal(18,2)` | No | `0` | |
| `total` | `decimal(18,2)` | No | `0` | |
| `applied_amount` | `decimal(18,2)` | No | `0` | Monto ya aplicado a facturas. |
| `balance` | `decimal(18,2)` | No | `0` | Crédito disponible. |
| `cancelled_at` | `timestamp` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `completed` (totalmente aplicada), o `cancelled`.

**Índices:** `index(supplier_id)`, `index(purchase_invoice_id)`, `index(note_date)`, `index(balance)`.

### 4.2 Líneas — `app_purchase_credit_note_lines`

Además de las columnas comunes de línea:

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `purchase_invoice_line_id` | `uuid` | Sí | FK → línea de la factura original. |
| `warehouse_id` | `uuid` | Sí | Bodega desde la que sale la mercancía si `affects_inventory`. |
| `lot_id` | `uuid` | Sí | FK → `app_item_lots.id`. |

**Reglas**
- Al confirmar, disminuye `current_balance` del proveedor y, si `affects_inventory`, genera movimientos `out`.
- La cantidad acreditada no puede superar la facturada menos la ya acreditada.
- Se aplica a facturas mediante `app_supplier_payment_applications` (tipo `credit_note`).

---

## 5. Anticipos a proveedor

Pagos entregados antes de recibir la factura. Quedan como saldo a favor aplicable a facturas futuras.

**Tabla:** `app_supplier_advances` — **Prefijo:** `ANP` (sin líneas)

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `supplier_id` | `uuid` | No | | FK → `app_suppliers.id` (`restrictOnDelete`). |
| `purchase_order_id` | `uuid` | Sí | | FK → `app_purchase_orders.id`. Orden que motiva el anticipo. |
| `advance_date` | `date` | No | | |
| `payment_method` | `enum` | No | `'transfer'` | `cash`, `transfer`, `check`, `card`, `other`. |
| `reference` | `string(60)` | Sí | | Número de transferencia o cheque. |
| `bank_account` | `string(60)` | Sí | | Cuenta desde la que se pagó. |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `amount` | `decimal(18,2)` | No | `0` | Monto entregado. |
| `applied_amount` | `decimal(18,2)` | No | `0` | Monto ya aplicado a facturas. |
| `balance` | `decimal(18,2)` | No | `0` | `amount - applied_amount`. Disponible. |
| `cancelled_at` | `timestamp` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `partial` → `completed` (agotado), o `cancelled`.

**Índices:** `index(supplier_id)`, `index(advance_date)`, `index(balance)`, `index(purchase_order_id)`.

**Reglas**
- Al confirmar, aumenta `advance_balance` del proveedor.
- Se aplica a facturas mediante `app_supplier_payment_applications` (tipo `advance`).
- No se puede anular si tiene `applied_amount > 0`.

---

## 6. Pagos a proveedor

Salida de dinero que cancela una o varias facturas. Un pago puede combinar efectivo, anticipos y
notas de crédito.

### 6.1 Cabecera — `app_supplier_payments` — Prefijo `PGP`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `supplier_id` | `uuid` | No | | FK → `app_suppliers.id` (`restrictOnDelete`). |
| `payment_date` | `date` | No | | |
| `payment_method` | `enum` | No | `'transfer'` | `cash`, `transfer`, `check`, `card`, `advance`, `credit_note`, `other`. |
| `reference` | `string(60)` | Sí | | Número de cheque, transferencia o comprobante. |
| `bank_account` | `string(60)` | Sí | | |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `amount` | `decimal(18,2)` | No | `0` | Monto bruto del pago. |
| `withholding_amount` | `decimal(18,2)` | No | `0` | Retenciones practicadas al pagar. |
| `applied_amount` | `decimal(18,2)` | No | `0` | Suma de aplicaciones a facturas. |
| `unapplied_amount` | `decimal(18,2)` | No | `0` | Excedente; se convierte en anticipo. |
| `cancelled_at` | `timestamp` | Sí | | |
| `cancellation_reason` | `string(500)` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `completed`, o `cancelled`.

**Índices:** `index(supplier_id)`, `index(payment_date)`, `index(payment_method)`.

### 6.2 Aplicaciones — `app_supplier_payment_applications`

Tabla puente que registra **qué documento paga qué factura**. La usan pagos, anticipos y notas de crédito.
Es tabla de detalle: lleva `company_id` y `status`, pero no `code` (se identifica por la factura y su origen).

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `id` | `uuid` | No | | PK. |
| `company_id` | `uuid` | No | | FK → `app_companies.id`. |
| `purchase_invoice_id` | `uuid` | No | | FK → `app_purchase_invoices.id` (`restrictOnDelete`). Factura que se abona. |
| `source_type` | `enum` | No | | `payment`, `advance`, `credit_note`. Origen del crédito. |
| `source_id` | `uuid` | No | | Id del pago, anticipo o nota de crédito. |
| `applied_amount` | `decimal(18,2)` | No | `0` | Monto aplicado a esa factura. |
| `applied_at` | `datetime` | No | | Fecha de aplicación. |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `exchange_difference` | `decimal(18,2)` | No | `0` | Diferencial cambiario entre emisión y pago. |
| `status` | `enum` | No | `'active'` | `active`, `reversed`. |
| `created_by` | `uuid` | Sí | | FK → `users.id`. |
| `created_at` / `updated_at` | `timestamp` | Sí | | |

**Índices:** `index(purchase_invoice_id)`, `index(source_type, source_id)`, `index(applied_at)`,
`unique(purchase_invoice_id, source_type, source_id)`.

**Reglas**
- La suma de aplicaciones activas de una factura no puede superar su `total`.
- Cada aplicación actualiza `paid_amount`, `balance` y `payment_status` de la factura, y el saldo del origen.
- Revertir una aplicación cambia su `status` a `reversed` y recalcula ambos saldos: no se borra.

---

## 7. Devoluciones de compras

Salida física de mercancía hacia el proveedor por defectos, exceso o error de despacho.
Normalmente deriva en una nota de crédito.

### 7.1 Cabecera — `app_purchase_returns` — Prefijo `DVC`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `supplier_id` | `uuid` | No | | FK → `app_suppliers.id` (`restrictOnDelete`). |
| `purchase_invoice_id` | `uuid` | Sí | | FK → `app_purchase_invoices.id`. Factura de origen. |
| `entry_id` | `uuid` | Sí | | FK → `app_entries.id`. Entrada de origen. |
| `warehouse_id` | `uuid` | No | | FK → `app_warehouses.id`. Bodega desde la que sale. |
| `return_date` | `date` | No | | |
| `reason` | `enum` | No | `'damaged'` | `damaged`, `wrong_item`, `expired`, `excess`, `quality`, `other`. |
| `reason_detail` | `string(500)` | Sí | | |
| `currency` | `string(3)` | No | `'USD'` | |
| `exchange_rate` | `decimal(18,8)` | No | `1` | |
| `subtotal` | `decimal(18,2)` | No | `0` | |
| `tax_amount` | `decimal(18,2)` | No | `0` | |
| `total` | `decimal(18,2)` | No | `0` | |
| `credit_note_id` | `uuid` | Sí | | FK → `app_purchase_credit_notes.id`. Nota generada. |
| `carrier` | `string(150)` | Sí | | Transportista que retira. |
| `tracking_number` | `string(60)` | Sí | | Guía de retorno. |
| `cancelled_at` | `timestamp` | Sí | | |
| `notes` | `text` | Sí | | |

**Estados (`status`):** `draft` → `confirmed` → `completed` (acreditada), o `cancelled`.

**Índices:** `index(supplier_id)`, `index(purchase_invoice_id)`, `index(return_date)`, `index(warehouse_id)`.

### 7.2 Líneas — `app_purchase_return_lines`

Además de las columnas comunes de línea:

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `purchase_invoice_line_id` | `uuid` | Sí | FK → línea facturada. |
| `lot_id` | `uuid` | Sí | FK → `app_item_lots.id`. Lote devuelto. |
| `serial_id` | `uuid` | Sí | FK → `app_item_serials.id`. |
| `location_id` | `uuid` | Sí | Ubicación desde la que se toma. |
| `reason` | `enum` | Sí | Motivo específico de la línea. |

**Reglas**
- Al confirmar genera movimientos `out` en el kardex al costo de la compra original (no al promedio actual).
- La cantidad devuelta no puede superar `quantity - returned_quantity` de la línea de factura.
- Si el artículo maneja lote o serie, se devuelve exactamente el lote/serie recibido.

---

## Diagrama del ciclo

```
app_suppliers
     │
     ├──> app_purchase_orders ──> app_purchase_order_lines
     │            │
     │            └──> app_entries (Logística) ──> kardex (in)
     │                       │
     ├──> app_purchase_invoices ──> app_purchase_invoice_lines
     │            │
     │            ├──< app_supplier_payment_applications >── app_supplier_payments
     │            │                                       ├── app_supplier_advances
     │            │                                       └── app_purchase_credit_notes
     │            │
     └──> app_purchase_returns ──> app_purchase_credit_notes ──> kardex (out)
```
