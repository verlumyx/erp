# Catálogo

Maestros transversales que alimentan al resto del sistema. Son los primeros módulos a implementar
porque Inventario, Compras y Ventas dependen de ellos.

> Las columnas base (`id`, `company_id`, `code`, `status`, `created_by`, `created_at`, `updated_at`),
> los índices base y la política de no borrado están descritos en [README.md](README.md) y no se repiten aquí.

| Módulo | Tabla | Prefijo | Usado por |
|---|---|---|---|
| Categorías | `app_categories` | `CAT` | Inventario |
| Lista de precio de venta | `app_price_lists` | `PRL` | Inventario / Ventas |
| Unidades de medida | `app_measurement_units` | `UOM` | Inventario |
| Impuestos | `app_taxes` | `IMP` | Todo el sistema |
| Tipo de proveedor | `app_supplier_types` | `TPR` | Compras |
| Tipo de cliente | `app_client_types` | `TCL` | Ventas |
| Tasas | `app_exchange_rates` | `TAS` | Compras / Ventas |

---

## 1. Categorías

Clasificación de los artículos. **Un solo nivel**: no hay subcategorías ni jerarquía.

**Tabla:** `app_categories` — **Prefijo:** `CAT`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(150)` | No | | Nombre de la categoría. |
| `description` | `text` | Sí | | |
| `order` | `integer` | No | `0` | Orden de presentación en listados y selectores. |

**Índices:** `unique(company_id, name)`, `index(name)`, `index(order)`.

**Reglas**  
- No se puede desactivar una categoría con artículos activos asociados.

**Relaciones:** `hasMany(Item)`.

---

## 2. Lista de precio de venta

Catálogo simple de listas de precio de venta ("Mayorista", "Detalle", "Promoción Enero"). La lista
solo **nombra el conjunto**: no guarda precios ni condiciones.

**Tabla:** `app_price_lists` — **Prefijo:** `PRL`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(150)` | No | | Nombre de la lista (p. ej. "Mayorista"). |
| `description` | `text` | Sí | | |

**Índices:** `unique(company_id, name)`, `index(name)`.

**Reglas**
- La lista **no** guarda precio, moneda, vigencia ni impuesto. Todo eso se configura **por artículo**,
  dentro del artículo, indicando a qué lista pertenece cada precio.
- Los precios se **copian** a la línea del documento al confirmarlo; cambiar un precio después no
  altera documentos ya emitidos.

---

## 3. Unidades de medida

Catálogo simple de unidades (Unidad, Caja, Kilogramo, Litro).

**Tabla:** `app_measurement_units` — **Prefijo:** `UOM`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(100)` | No | | Nombre (p. ej. "Kilogramo"). |
| `description` | `text` | Sí | | |
| `abbreviation` | `string(10)` | No | | Símbolo (`kg`, `un`, `cja`). |

**Índices:** `unique(company_id, name)`, `unique(company_id, abbreviation)`, `index(name)`.

**Reglas**
- La unidad **no** guarda factores de conversión. La equivalencia se define **por artículo** en
  `app_item_units` (p. ej. para el artículo X, 1 `cja` = 12 `un`), porque el contenido de una caja
  cambia de producto en producto.
- Cada artículo tiene una unidad base (`app_items.measurement_unit_id`); todo el stock se guarda en ella.
- No se puede desactivar una unidad usada como unidad base de algún artículo activo.

---

## 4. Impuestos

Impuestos aplicables a líneas de compra y de venta.

**Tabla:** `app_taxes` — **Prefijo:** `IMP`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(100)` | No | | Nombre (p. ej. "IVA 15%"). |
| `description` | `text` | Sí | | |
| `percentage` | `decimal(7,4)` | No | `0` | Porcentaje del impuesto (`15.0000`). |
| `has_withholding` | `enum` | No | `'no'` | `yes` = además se practica retención. |
| `withholding_percentage` | `decimal(7,4)` | No | `0` | Porcentaje de retención. Solo aplica si `has_withholding = 'yes'`. |

**Índices:** `unique(company_id, name)`, `index(has_withholding)`.

**Reglas**
- `percentage` y `withholding_percentage` se **copian** a la línea del documento
  (`tax_percent`, `withholding_percent`) al confirmarlo. Cambiar la tasa no recalcula documentos ya emitidos.
- Un impuesto con `percentage = 0` sirve para artículos exentos y permite declararlos correctamente.
- Si `has_withholding = 'no'`, `withholding_percentage` se fuerza a `0`.
- No se puede desactivar si está asignado a algún artículo, cliente o proveedor activo.

---

## 5. Tipo de proveedor

Clasificación de proveedores para reportes y filtros de Compras.

**Tabla:** `app_supplier_types` — **Prefijo:** `TPR`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(100)` | No | | Nombre (p. ej. "Nacional", "Importador", "Servicios"). |
| `description` | `text` | Sí | | |

**Índices:** `unique(company_id, name)`, `index(name)`.

**Reglas:** no se puede desactivar si tiene proveedores activos asociados.

---

## 6. Tipo de cliente

Clasificación de clientes para reportes y filtros de Ventas.

**Tabla:** `app_client_types` — **Prefijo:** `TCL`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(100)` | No | | Nombre (p. ej. "Mayorista", "Detalle", "Corporativo"). |
| `description` | `text` | Sí | | |

**Índices:** `unique(company_id, name)`, `index(name)`.

**Reglas**
- El orden de resolución del precio en una venta es:
  precio del artículo en la lista del cliente (`app_clients.price_list_id`) → precio base del
  artículo (`app_items.base_price`).
  El tipo de cliente **no** interviene en el precio: es solo clasificación.
- No se puede desactivar si tiene clientes activos asociados.

---

## 7. Tasas

Valor de la tasa de cambio del dólar y el euro por fecha. Es la fuente única desde la que los
documentos toman su `exchange_rate`.

**Tabla:** `app_exchange_rates` — **Prefijo:** `TAS`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `currency` | `string(3)` | No | | Moneda ISO 4217: `USD`, `EUR`. |
| `rate_date` | `date` | No | | Fecha de vigencia de la tasa. |
| `rate` | `decimal(18,8)` | No | `0` | Valor de la tasa: cuántas unidades de la moneda base equivale **1** unidad de `currency`. |
| `type` | `enum` | No | `'legal'` | `legal` (tasa oficial publicada) o `manual` (tasa interna cargada a mano). |
| `source` | `string(150)` | Sí | | De dónde salió la tasa (banco central, boletín, carga manual). |
| `description` | `text` | Sí | | |

**Índices:** `unique(company_id, currency, rate_date, type)`, `index(currency)`, `index(rate_date)`,
`index(type)`.

**Reglas**
- Una sola tasa por moneda, fecha y tipo. Si se carga de nuevo la misma combinación, se actualiza el
  registro existente en lugar de crear otro.
- **Búsqueda de la tasa de un documento:** se busca la del `type = legal` cuya `rate_date` sea igual a
  la fecha del documento; si no existe, se toma la **más reciente anterior** a esa fecha. Si tampoco
  hay, el documento no se puede confirmar en moneda extranjera.
- La tasa se **copia** al documento (`exchange_rate`) al confirmarlo. Cargar después una tasa distinta
  no recalcula documentos ya emitidos: eso es lo que permite calcular el diferencial cambiario entre
  la emisión y el pago (`exchange_difference` en las tablas de aplicación).
- Una tasa cargada con error no se borra: se desactiva (`status = inactive`) y se registra la correcta.
  Los documentos ya confirmados con ella conservan su `exchange_rate`.
- La moneda base de la empresa no se registra aquí: su tasa es siempre `1`.
