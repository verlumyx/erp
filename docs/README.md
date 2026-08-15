# Documentación de Módulos — ERP

Este directorio describe los módulos funcionales del ERP y el modelo de datos que soporta cada uno.
Cada archivo agrupa un conjunto de módulos relacionados.

| Archivo | Grupo | Módulos |
|---|---|---|
| [catalogo.md](catalogo.md) | Catálogo | Categorías, Listas de precio de venta, Unidades de medida, Impuestos, Tipo de proveedor, Tipo de cliente |
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

### Columnas base (presentes en toda tabla maestra y de documento)

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

### Tipos numéricos

| Uso | Tipo |
|---|---|
| Cantidades | `decimal(18,4)` |
| Precios y costos unitarios | `decimal(18,6)` |
| Importes / totales | `decimal(18,2)` |
| Porcentajes (impuestos, descuentos) | `decimal(7,4)` |
| Tasa de cambio | `decimal(18,8)` |

Todos los importes se guardan en la **moneda del documento** más el campo `exchange_rate`, para poder
reexpresar en moneda base sin recalcular históricos.

### Líneas de documento

Toda tabla `*_lines` comparte esta estructura (no se repite en cada ficha; solo se documentan sus
particularidades):

| Columna | Tipo | Nulo | Descripción |
|---|---|---|---|
| `id` | `uuid` | No | PK. |
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
| `tax_percent` | `decimal(7,4)` | No | Copiado del impuesto al momento del documento. |
| `tax_amount` | `decimal(18,2)` | No | Default `0`. |
| `subtotal` | `decimal(18,2)` | No | `quantity * unit_price - discount_amount`. |
| `total` | `decimal(18,2)` | No | `subtotal + tax_amount`. |
| `notes` | `string(500)` | Sí | |
| `created_at` / `updated_at` | `timestamp` | Sí | |

Índices: `index({document}_id)`, `index(item_id)`, `unique({document}_id, line_number)`.

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
| | | `COB` | Cobro a cliente |
| | | `DVV` | Devolución de venta |

### Arquitectura

Cada módulo se implementa con la estructura modular del proyecto
(`app/Modules/{Modulo}/` con `Controllers`, `Requests`, `Resources`, `Services`, `Repositories`,
`Commands`, `Models`, `Exceptions`, `Providers`, `routes.php`). Ver skill `laravel-modular-architecture`.
Cada módulo se registra además en `app_modules` con sus permisos en `app_permissions`
(`view`, `create`, `update`, `update_status`, y los específicos como `confirm` o `cancel`).
