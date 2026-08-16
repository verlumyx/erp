# Documentación de Módulos — ERP

Este directorio describe los módulos funcionales del ERP y el modelo de datos que soporta cada uno.
Cada archivo agrupa un conjunto de módulos relacionados.

| Archivo | Grupo | Módulos |
|---|---|---|
| [catalogo.md](catalogo.md) | Catálogo | Categorías, Listas de precio de venta, Unidades de medida, Impuestos, Tipo de proveedor, Tipo de cliente, Tasas |
| [inventario.md](inventario.md) | Inventario | Catálogo de artículos, Bodegas, Existencias, Kardex |
| [logistica.md](logistica.md) | Logística | Despachos, Traslados, Entradas, Rutas, Ajustes |
| [compras.md](compras.md) | Compras | Proveedores, Órdenes de compra, Facturas de compra, Notas de crédito a proveedor, Anticipos a proveedor, Pagos a proveedor, Devoluciones de compras |
| [ventas.md](ventas.md) | Ventas | Clientes, Órdenes de venta, Facturas de venta, Notas de crédito a cliente, Anticipos de clientes, Cobros a clientes, Devoluciones de ventas |
| [reportes.md](reportes.md) | Reportes | Propuesta inicial (pendiente de definir) |

El orden de implementación recomendado es: **Catálogo → Inventario → Compras → Ventas → Logística → Reportes**,
porque cada grupo depende de los anteriores.

---

## Convenciones transversales

Todas las tablas siguen las mismas reglas. En las fichas de cada módulo **no se repiten** estas columnas:
solo se listan las propias del módulo.

### Clasificación de tablas

Hay dos tipos de tabla, y de ahí depende qué columnas base lleva:

| Tipo | Qué es | Lleva |
|---|---|---|
| **Tabla de módulo** | Entidad con pantalla y CRUD propio: se lista, se crea y se edita por sí sola. | `id`, `company_id`, `code`, `status`, `created_by`, timestamps |
| **Tabla de detalle** | Filas que solo existen dentro de un padre: líneas de documento, contactos, direcciones, precios de una lista, aplicaciones de pago, saldos. Se editan desde la pantalla del padre. | `id`, `company_id`, `status`, timestamps (**sin `code`**) |

Las de detalle **no** llevan `code` porque no se numeran de forma independiente: se identifican por su
padre más su `line_number` o su combinación única. Sí llevan `company_id` (para filtrar y reportar sin
join contra el padre) y `status` (`active` / `inactive`), porque la política de no borrado también las
alcanza: una línea o un contacto que ya no aplica se desactiva, no se elimina.

> Los totales de un documento suman **solo** las líneas con `status = active`.

**Tablas de módulo** (llevan `code`):

| Grupo | Tablas |
|---|---|
| Catálogo | `app_categories`, `app_price_lists`, `app_measurement_units`, `app_taxes`, `app_supplier_types`, `app_client_types`, `app_exchange_rates` |
| Inventario | `app_items`, `app_warehouses`, `app_warehouse_locations`, `app_item_lots`, `app_item_serials`, `app_inventory_movements` |
| Logística | `app_dispatches`, `app_transfers`, `app_entries`, `app_routes`, `app_adjustments` |
| Compras | `app_suppliers`, `app_purchase_orders`, `app_purchase_invoices`, `app_purchase_credit_notes`, `app_supplier_advances`, `app_supplier_payments`, `app_purchase_returns` |
| Ventas | `app_clients`, `app_sales_orders`, `app_sales_invoices`, `app_sales_credit_notes`, `app_client_advances`, `app_client_collections`, `app_sales_returns` |

**Tablas de detalle** (sin `code`, con `company_id` + `status`):

`app_item_prices`, `app_item_units`, `app_item_stocks`,
`app_supplier_contacts`, `app_supplier_addresses`, `app_client_contacts`, `app_client_addresses`,
`app_route_stops`, `app_route_clients`, `app_supplier_payment_applications`,
`app_client_collection_applications` y todas las tablas `*_lines`.

`app_item_stocks` es un caso especial: es una tabla **derivada** (el saldo calculado del kardex).
No se captura ni se edita a mano, pero lleva `company_id` y `status` como el resto.

### Columnas base (presentes en toda tabla de módulo)

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | `uuid` | No | Primary key. UUID v7 generado en el cliente/servicio. |
| `company_id` | `uuid` | Sí | FK → `app_companies.id` (`nullOnDelete`). Toda consulta se filtra por la empresa activa. |
| `code` | `string(12)` | Sí | Código secuencial legible: prefijo de 3 letras + 6 dígitos (`CLI000001`). Generado en el Repository dentro de una transacción con `lockForUpdate()`. Único por empresa. |
| `status` | `enum` | No | Estado del registro. Ver *Estados* más abajo. |
| `created_by` | `uuid` | Sí | FK → `users.id` (`nullOnDelete`). Trazabilidad de quién creó el registro. |
| `created_at` | `timestamp` | Sí | |
| `updated_at` | `timestamp` | Sí | |

### Índices base

- `index(company_id)`
- `index(status)`
- `index(created_by)`
- `index(created_at)`
- `unique(company_id, code)`
- En maestros con nombre: `index(name)` y normalmente `unique(company_id, name)`.

### Estados

**Maestros** (artículos, bodegas, clientes, proveedores, catálogos):

| Valor | Significado |
|---|---|
| `active` | Disponible para usarse en documentos nuevos. |
| `inactive` | Oculto para documentos nuevos; se conserva el histórico. |

**Documentos** (órdenes, facturas, despachos, traslados, entradas, ajustes, pagos, cobros):

| Valor | Significado |
|---|---|
| `draft` | Borrador. Editable, no afecta inventario ni saldos. |
| `confirmed` | Confirmado/aprobado. Ya afecta inventario y/o cuentas por cobrar/pagar. |
| `partial` | Cumplido parcialmente (parcialmente despachado, facturado, pagado o recibido). |
| `completed` | Cumplido en su totalidad. |
| `cancelled` | Anulado. Revierte efectos con movimientos de contrapartida; el registro permanece. |

### Política de no borrado

**Ningún registro se elimina físicamente.** No hay `DELETE`, no hay botón de eliminar y no se usa
`softDeletes`. Los maestros se desactivan (`status = inactive`) y los documentos se anulan
(`status = cancelled`). Ver skill `no-delete-policy`.

### Campos sí/no

**No se usan columnas `boolean`.** Todo campo de dos valores se declara como
`enum('yes', 'no')` con default explícito:

```php
$table->enum('has_withholding', ['yes', 'no'])->default('no');
```

Motivo: al consultar la base directamente se lee `yes` / `no` en lugar de `1` / `0`, y si mañana el
campo necesita un tercer valor se agrega al enum sin cambiar el tipo de la columna.

- Los nombres conservan el prefijo habitual: `is_*`, `has_*`, `tracks_*`, `allows_*`, `uses_*`.
- La validación en el Request es `in:yes,no`.
- En las comparaciones de este documento el valor va entre comillas: `has_withholding = 'yes'`.

### Tipos numéricos

| Uso | Tipo |
|---|---|
| Cantidades | `decimal(18,4)` |
| Precios y costos unitarios | `decimal(18,6)` |
| Importes / totales | `decimal(18,2)` |
| Porcentajes (impuestos, descuentos) | `decimal(7,4)` |
| Tasa de cambio | `decimal(18,8)` |

Todos los importes se guardan en la **moneda del documento** más el campo `exchange_rate`, para poder
reexpresar en moneda base sin recalcular históricos. Ese `exchange_rate` se copia desde
`app_exchange_rates` (módulo Tasas, ver [catalogo.md](catalogo.md)) al confirmar el documento.

### Identificación fiscal (RIF / cédula)

Clientes y proveedores se identifican con el **RIF venezolano**, no con una clasificación genérica de
persona natural/jurídica. Son dos columnas, presentes con la misma definición en `app_clients` y en
`app_suppliers`:

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `document_type` | `enum` | No | `'V'` | Letra del RIF: `V`, `E`, `J`, `P`, `G`, `C`. |
| `document_number` | `string(15)` | No | | Solo dígitos, sin letra ni guiones. |

| Letra | Contribuyente | Naturaleza |
|---|---|---|
| `V` | Persona natural venezolana. Los dígitos son su cédula de identidad. | Natural |
| `E` | Persona natural extranjera con cédula de extranjero (residente). | Natural |
| `P` | Persona natural extranjera identificada con pasaporte (no residente). | Natural |
| `J` | Persona jurídica: compañías, firmas personales, asociaciones. | Jurídica |
| `G` | Entes gubernamentales y del sector público. | Jurídica |
| `C` | Consejos comunales, comunas y organizaciones del Poder Popular. | Jurídica |

**Reglas**
- El RIF completo es `letra + 8 dígitos + dígito verificador` (`J-12345678-9`). Para `V`/`E` los
  8 dígitos son la cédula, que se guarda **sin** rellenar con ceros y suele ir sin dígito verificador.
- Se guarda normalizado: letra en mayúscula en `document_type` y solo dígitos en `document_number`.
  El guion y el formato `J-12345678-9` son de presentación, no de almacenamiento.
- Único por empresa: `unique(company_id, document_type, document_number)`.
- La naturaleza del contribuyente (natural o jurídica) **se deriva de la letra**; no se guarda en otra
  columna. `V`, `E` y `P` son naturales; `J`, `G` y `C` son jurídicas.
- La retención de IVA/ISLR y el formato de la factura dependen de esta letra, por eso es obligatoria.

### Líneas de documento

Toda tabla `*_lines` comparte esta estructura (no se repite en cada ficha; solo se documentan sus
particularidades):

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | `uuid` | No | PK. |
| `company_id` | `uuid` | Sí | FK → `app_companies.id`. Heredado del documento padre. |
| `{document}_id` | `uuid` | No | FK → documento padre, `cascadeOnDelete`. |
| `line_number` | `integer` | No | Orden de la línea dentro del documento. |
| `item_id` | `uuid` | No | FK → `app_items.id`, `restrictOnDelete`. |
| `measurement_unit_id` | `uuid` | No | FK → `app_measurement_units.id`. Unidad usada en la línea. |
| `quantity` | `decimal(18,4)` | No | Cantidad en la unidad de la línea. |
| `base_quantity` | `decimal(18,4)` | No | Cantidad convertida a la unidad base del artículo. |
| `unit_price` | `decimal(18,6)` | No | Precio/costo unitario. |
| `discount_percent` | `decimal(7,4)` | No | Default `0`. |
| `discount_amount` | `decimal(18,2)` | No | Default `0`. |
| `tax_id` | `uuid` | Sí | FK → `app_taxes.id`. |
| `tax_percent` | `decimal(7,4)` | No | Copiado de `app_taxes.percentage` al momento del documento. |
| `tax_amount` | `decimal(18,2)` | No | Default `0`. |
| `withholding_percent` | `decimal(7,4)` | No | Copiado de `app_taxes.withholding_percentage`. Default `0`. |
| `withholding_amount` | `decimal(18,2)` | No | Default `0`. Retención de la línea. |
| `subtotal` | `decimal(18,2)` | No | `quantity * unit_price - discount_amount`. |
| `total` | `decimal(18,2)` | No | `subtotal + tax_amount`. |
| `status` | `enum` | No | `active` / `inactive`. Una línea no se borra: se desactiva. |
| `notes` | `string(500)` | Sí | |
| `created_at` / `updated_at` | `timestamp` | Sí | |

Índices: `index({document}_id)`, `index(item_id)`, `index(company_id)`, `index(status)`,
`unique({document}_id, line_number)`.

### Multiempresa

Todo registro pertenece a una empresa (`company_id`). Los secuenciales (`code`) son independientes por
empresa: dos empresas pueden tener cada una su `FVE000001`.

### Prefijos de código por módulo

| Prefijo | Módulo | Prefijo | Módulo |
|---|---|---|---|
| `CAT` | Categorías | `ART` | Artículos |
| `PRL` | Lista de precio de venta | `BOD` | Bodegas |
| `UOM` | Unidad de medida | `MOV` | Movimiento de inventario |
| `IMP` | Impuesto | `DES` | Despachos |
| `TPR` | Tipo de proveedor | `TRA` | Traslados |
| `TCL` | Tipo de cliente | `ENT` | Entradas |
| `PRO` | Proveedores | `RUT` | Rutas |
| `OCO` | Órdenes de compra | `AJU` | Ajustes |
| `FCO` | Facturas de compra | `CLI` | Clientes |
| `NCP` | Nota de crédito a proveedor | `OVE` | Órdenes de venta |
| `ANP` | Anticipo a proveedor | `FVE` | Facturas de venta |
| `PGP` | Pago a proveedor | `NCC` | Nota de crédito a cliente |
| `DVC` | Devolución de compra | `ANC` | Anticipo de cliente |
| `UBI` | Ubicación de bodega | `COB` | Cobro a cliente |
| `LOT` | Lote | `DVV` | Devolución de venta |
| `SER` | Número de serie | `TAS` | Tasa de cambio |

### Arquitectura

Cada módulo se implementa con la estructura modular del proyecto
(`app/Modules/{Modulo}/` con `Controllers`, `Requests`, `Resources`, `Services`, `Repositories`,
`Commands`, `Models`, `Exceptions`, `Providers`, `routes.php`). Ver skill `laravel-modular-architecture`.
Cada módulo se registra además en `app_modules` con sus permisos en `app_permissions`
(`view`, `create`, `update`, `update_status`, y los específicos como `confirm` o `cancel`).
