# Catálogo

Maestros transversales que alimentan al resto del sistema. Son los primeros módulos a implementar
porque Inventario, Compras y Ventas dependen de ellos.

> Las columnas base (`id`, `company_id`, `code`, `status`, `created_by`, `created_at`, `updated_at`),
> los índices base y la política de no borrado están descritos en [README.md](README.md) y no se repiten aquí.

| Módulo | Tabla | Prefijo | Usado por |
|---|---|---|---|
| Categorías | `app_categories` | `CAT` | Inventario |
| Lista de precio de venta | `app_price_lists` + `app_price_list_items` | `PRL` | Inventario / Ventas |
| Unidades de medida | `app_measurement_units` | `UOM` | Inventario |
| Impuestos | `app_taxes` | `IMP` | Todo el sistema |
| Tipo de proveedor | `app_supplier_types` | `TPR` | Compras |
| Tipo de cliente | `app_client_types` | `TCL` | Ventas |

---

## 1. Categorías

Clasificación jerárquica de los artículos. Soporta árbol de N niveles mediante autorreferencia.

**Tabla:** `app_categories` — **Prefijo:** `CAT`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `parent_id` | `uuid` | Sí | | FK → `app_categories.id` (`nullOnDelete`). Categoría padre; `null` = raíz. |
| `name` | `string(150)` | No | | Nombre de la categoría. |
| `description` | `text` | Sí | | |
| `level` | `smallInteger` | No | `0` | Profundidad en el árbol; se calcula al guardar. |
| `path` | `string(500)` | Sí | | Ruta materializada de ids (`uuid/uuid/uuid`) para consultas descendentes rápidas. |
| `order` | `integer` | No | `0` | Orden de presentación entre hermanas. |

**Índices:** `unique(company_id, name, parent_id)`, `index(parent_id)`, `index(level)`, `index(order)`.

**Reglas**
- Una categoría no puede ser su propia ancestra (validar ciclo antes de guardar).
- Desactivar una categoría desactiva en cascada lógica a sus descendientes (no se borran).
- No se puede desactivar si tiene artículos activos asociados.

**Relaciones:** `hasMany(Category, parent_id)` (hijas), `belongsTo(Category, parent_id)` (padre), `hasMany(Item)`.

---

## 2. Lista de precio de venta

Conjuntos de precios de venta por artículo. Un cliente puede tener una lista asignada; si no la tiene,
se usa la lista marcada como predeterminada.

### 2.1 Cabecera — `app_price_lists` — Prefijo `PRL`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(150)` | No | | Nombre de la lista (p. ej. "Mayorista"). |
| `description` | `text` | Sí | | |
| `currency` | `string(3)` | No | `'USD'` | Moneda ISO 4217 de los precios. |
| `is_default` | `boolean` | No | `false` | Lista aplicada cuando el cliente no tiene una asignada. Solo una por empresa. |
| `includes_tax` | `boolean` | No | `false` | `true` = los precios ya incluyen impuesto. |
| `valid_from` | `date` | Sí | | Inicio de vigencia. |
| `valid_to` | `date` | Sí | | Fin de vigencia; `null` = sin vencimiento. |
| `priority` | `integer` | No | `0` | Desempate cuando varias listas aplican. |

**Índices:** `unique(company_id, name)`, `index(is_default)`, `index(valid_from, valid_to)`.

### 2.2 Detalle — `app_price_list_items`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `id` | `uuid` | No | | PK. |
| `price_list_id` | `uuid` | No | | FK → `app_price_lists.id` (`cascadeOnDelete`). |
| `item_id` | `uuid` | No | | FK → `app_items.id` (`restrictOnDelete`). |
| `measurement_unit_id` | `uuid` | Sí | | FK → `app_measurement_units.id`. Unidad a la que aplica el precio; `null` = unidad base. |
| `price` | `decimal(18,6)` | No | | Precio unitario de venta. |
| `min_quantity` | `decimal(18,4)` | No | `0` | Cantidad mínima para que aplique este precio (escalas por volumen). |
| `discount_percent` | `decimal(7,4)` | No | `0` | Descuento máximo permitido sobre este precio. |
| `created_at` / `updated_at` | `timestamp` | Sí | | |

**Índices:** `unique(price_list_id, item_id, measurement_unit_id, min_quantity)`, `index(item_id)`.

**Reglas**
- Al cambiar `is_default` a `true`, se pone en `false` la lista predeterminada anterior de la empresa.
- Los precios de un documento se **copian** a la línea al confirmarlo; cambiar la lista después no altera históricos.

---

## 3. Unidades de medida

Unidades con factor de conversión hacia una unidad base por familia (peso, volumen, longitud, unidad).

**Tabla:** `app_measurement_units` — **Prefijo:** `UOM`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(100)` | No | | Nombre (p. ej. "Kilogramo"). |
| `abbreviation` | `string(10)` | No | | Símbolo (`kg`, `un`, `cja`). |
| `type` | `enum` | No | `'unit'` | `unit`, `weight`, `volume`, `length`, `area`, `time`. Solo se convierten unidades del mismo tipo. |
| `is_base` | `boolean` | No | `false` | Unidad base de su tipo. Una sola por `type` y empresa. |
| `conversion_factor` | `decimal(18,8)` | No | `1` | Cuántas unidades base equivale 1 de esta unidad. |
| `decimals` | `smallInteger` | No | `2` | Decimales admitidos al capturar cantidades. |
| `description` | `text` | Sí | | |

**Índices:** `unique(company_id, abbreviation)`, `unique(company_id, name)`, `index(type)`, `index(is_base)`.

**Reglas**
- La unidad base de cada tipo tiene `conversion_factor = 1` y no es editable.
- No se puede desactivar una unidad usada como unidad base de algún artículo.
- La conversión se aplica siempre a la unidad base del artículo al mover inventario (`base_quantity`).

---

## 4. Impuestos

Impuestos aplicables a líneas de compra y de venta (IVA, retenciones, impuestos específicos).

**Tabla:** `app_taxes` — **Prefijo:** `IMP`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(100)` | No | | Nombre (p. ej. "IVA 15%"). |
| `type` | `enum` | No | `'percentage'` | `percentage` (sobre la base) o `fixed` (monto por unidad). |
| `scope` | `enum` | No | `'both'` | `sale`, `purchase`, `both`. Dónde puede seleccionarse. |
| `rate` | `decimal(7,4)` | No | `0` | Porcentaje (`15.0000`) o monto fijo según `type`. |
| `is_withholding` | `boolean` | No | `false` | `true` = retención (resta del total a pagar/cobrar). |
| `is_included_in_price` | `boolean` | No | `false` | `true` = el precio capturado ya lo contiene. |
| `is_default` | `boolean` | No | `false` | Impuesto sugerido al crear un artículo. |
| `accounting_code` | `string(50)` | Sí | | Código fiscal/contable para reportes y declaraciones. |
| `description` | `text` | Sí | | |

**Índices:** `unique(company_id, name)`, `index(scope)`, `index(is_withholding)`, `index(is_default)`.

**Reglas**
- La tasa se **copia** a la línea del documento (`tax_percent`) al confirmarlo. Cambiar la tasa no
  recalcula documentos ya emitidos.
- Un impuesto con `rate = 0` sirve para artículos exentos y permite declararlos correctamente.
- No se puede desactivar si está asignado como impuesto de algún artículo activo.

---

## 5. Tipo de proveedor

Clasificación de proveedores para reglas comerciales y reportes de Compras.

**Tabla:** `app_supplier_types` — **Prefijo:** `TPR`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(100)` | No | | Nombre (p. ej. "Nacional", "Importador", "Servicios"). |
| `description` | `text` | Sí | | |
| `default_payment_term_days` | `integer` | No | `0` | Días de crédito sugeridos al crear un proveedor de este tipo. |
| `default_tax_id` | `uuid` | Sí | | FK → `app_taxes.id` (`nullOnDelete`). Impuesto sugerido en compras. |
| `requires_tax_id` | `boolean` | No | `true` | Exige identificación fiscal al registrar el proveedor. |
| `order` | `integer` | No | `0` | Orden de presentación. |

**Índices:** `unique(company_id, name)`, `index(default_tax_id)`.

**Reglas:** no se puede desactivar si tiene proveedores activos asociados.

---

## 6. Tipo de cliente

Clasificación de clientes para precios, crédito y reportes de Ventas.

**Tabla:** `app_client_types` — **Prefijo:** `TCL`

| Columna | Tipo | Nulo | Default | Descripción |
|---|---|---|---|---|
| `name` | `string(100)` | No | | Nombre (p. ej. "Mayorista", "Detalle", "Corporativo"). |
| `description` | `text` | Sí | | |
| `price_list_id` | `uuid` | Sí | | FK → `app_price_lists.id` (`nullOnDelete`). Lista de precio por defecto del tipo. |
| `default_payment_term_days` | `integer` | No | `0` | Días de crédito sugeridos. |
| `default_credit_limit` | `decimal(18,2)` | No | `0` | Límite de crédito sugerido. |
| `default_discount_percent` | `decimal(7,4)` | No | `0` | Descuento sugerido en venta. |
| `requires_tax_id` | `boolean` | No | `false` | Exige identificación fiscal al registrar el cliente. |
| `order` | `integer` | No | `0` | Orden de presentación. |

**Índices:** `unique(company_id, name)`, `index(price_list_id)`.

**Reglas**
- El orden de resolución del precio en una venta es:
  cliente → tipo de cliente → lista predeterminada de la empresa → precio base del artículo.
- No se puede desactivar si tiene clientes activos asociados.
