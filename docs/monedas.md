# Monedas y tasas de cambio

Cómo maneja el sistema el multimoneda: en qué moneda trabaja cada empresa, de dónde sale la tasa de cambio y qué se
guarda en cada documento.

> Las columnas base, los índices base, los tipos numéricos y la política de no borrado están descritos en
> [README.md](README.md) y no se repiten aquí.

**Contexto:** el negocio opera en Venezuela. La ley exige que factura y pago expresen el monto en bolívares, pero muchas
empresas llevan sus cifras en dólares o euros. El sistema resuelve las dos cosas a la vez: cada empresa elige su moneda
principal y el bolívar acompaña a cada importe al cambio del día.

---

## Las tres capas

Cada una tiene una responsabilidad y ninguna invade a la otra:

| Capa              | Tabla                 | Qué es                                                                                       |
|-------------------|-----------------------|----------------------------------------------------------------------------------------------|
| **Catálogo**      | `app_currencies`      | Qué monedas existen. Global, igual para todas las empresas.                                  |
| **Tasas**         | `app_exchange_rates`  | El hecho objetivo: cuántos bolívares vale 1 unidad de cada moneda, por fecha y por empresa.  |
| **Configuración** | `app_configurations`  | La política de la empresa: en qué moneda lleva sus cifras y qué tasa aplica.                 |
| **Documento**     | `app_*_orders`, etc.  | El hecho congelado: las tasas que aplicó en su momento.                                      |

---

## 1. Semántica de la tasa

Es la regla de la que depende todo lo demás, y no admite excepciones:

> **`rate` = cuántos bolívares vale 1 unidad de la moneda extranjera.**

De ahí sale una única fórmula: `monto_en_bolívares = monto × rate`.

**El bolívar nunca lleva tasa.** Su equivalencia es siempre 1, así que registrar una tasa del bolívar contra sí mismo no
significa nada. El backend lo impide con la regla `ForeignCurrency` (módulo Currency), hermana de `ActiveCurrency`:

| Regla                 | Valida que el valor sea…                                       | Se usa en                                  |
|-----------------------|----------------------------------------------------------------|--------------------------------------------|
| `ActiveCurrency`      | una moneda activa del catálogo, **incluido** el bolívar        | Todo campo `currency` (documentos, precios) |
| `ForeignCurrency`     | una moneda activa del catálogo, **excluido** el bolívar        | El campo `currency` de una tasa de cambio  |

El código de la moneda local vive en una sola constante: `Currency::LOCAL_CODE` (`'VES'`).

**Cruce entre monedas extranjeras.** No hace falta tabla de pares: como toda tasa está expresada contra el bolívar, el
cruce se deriva. Con USD a 36,50 y EUR a 40,00, el cambio EUR→USD es `40,00 ÷ 36,50 = 1,0959`.

---

## 2. Configuración de la empresa

**Tabla:** `app_configurations` — **sin prefijo de código**

Es un tipo de tabla que no aparece en la clasificación de [README.md](README.md): un **singleton por empresa**. No se
lista, no se crea a mano, no se elimina y no se desactiva. Por eso no lleva `code` ni `status`.

| Columna                | Tipo         | Nulo | Default | Descripción                                                                              |
|------------------------|--------------|------|---------|------------------------------------------------------------------------------------------|
| `company_id`           | `uuid`       | No   |         | FK → `app_companies.id` (`cascadeOnDelete`). `unique`: una sola fila por empresa.        |
| `base_currency`        | `string(3)`  | No   | `'USD'` | Moneda en la que la empresa lleva sus cifras. Valida con `ActiveCurrency`.               |
| `secondary_currency`   | `string(3)`  | Sí   | `'VES'` | Moneda de presentación que acompaña a cada importe. `null` la desactiva.                 |
| `rate_type`            | `enum`       | No   | `'legal'` | Qué tasa valora los documentos: `legal` o `manual`.                                    |
| `allows_rate_override` | `enum`       | No   | `'yes'` | Si el usuario puede corregir a mano la tasa que resuelve el sistema.                     |
| `amount_decimals`      | `integer`    | No   | `2`     | Decimales de los importes. Entre 0 y 6.                                                  |
| `price_decimals`       | `integer`    | No   | `6`     | Decimales de los precios unitarios. Entre 0 y 8.                                         |

**Índices:** `unique(company_id)`, `index(created_by)`.

### Reglas

- **Ninguna empresa existe sin configuración.** `CompanyCreateService` la crea con los valores por defecto dentro de la
  misma transacción que la empresa y su rol administrador.
- **Se estrena sola si falta.** `ConfigurationFindService` la crea al primer acceso, así que las empresas anteriores a
  este módulo no necesitaron migración de relleno.
- **`base_currency = secondary_currency` apaga el multimoneda.** Una empresa que trabaja en bolívares pone ambas en
  `VES`: el sistema deja de convertir y los importes salen en una sola línea. No es una rama especial de código, es la
  misma fórmula con tasa 1.
- El cálculo `secondary_currency != null && secondary_currency != base_currency` viaja **resuelto** al frontend como
  `dual_currency` en el Resource, para que ninguna pantalla tenga que comparar monedas.
- La configuración se comparte en las props de Inertia (`configuration`) en cada request con empresa activa, junto al
  catálogo de monedas (`currencies`). El hook `useConfiguration()` es la puerta de entrada desde React.

**Permisos:** `configuration.show`, `configuration.update`. El módulo solo tiene pantalla de edición
(`GET`/`PUT /{company}/configuration`); no hay index, create ni update-status.

---

## 3. Resolución de la tasa

La conversión vive en un solo sitio: `ExchangeRateResolver` (módulo ExchangeRate), expuesto por
`ExchangeRateResolverInterface`. **Ningún módulo consulta `app_exchange_rates` por su cuenta ni multiplica montos a
mano.**

```php
public function rateFor(string $companyId, string $currency, string $date, string $type = 'legal'): float;
public function convert(float $amount, string $from, string $to, string $companyId, string $date, string $type = 'legal'): float;
```

`rateFor()` resuelve en este orden:

1. ¿Es el bolívar? → devuelve `1.0` sin tocar la base de datos.
2. ¿Ya se resolvió en este request? → caché en memoria. El binding es `scoped`, así que un documento de 30 líneas hace
   **una** consulta, no 31.
3. Busca la última tasa **activa**, del **tipo** pedido, de **esa empresa**, con `rate_date <= fecha del documento`.
4. ¿No hay ninguna? → `ExchangeRateNotFoundException` con mensaje accionable
   (*«No hay tasa de cambio cargada para USD al 2026-08-16»*) y el documento no se emite.

### Reglas

- **Se usa la última tasa anterior, no solo la del día exacto.** El emisor legal no publica sábados, domingos ni
  feriados: una factura del sábado 15 se valora con la tasa del viernes 14, automáticamente. Este comportamiento **no es
  configurable**; es el único.
- **Nunca se usa una tasa posterior a la fecha del documento.** Una tasa cargada el 20 no valora un documento del 16.
- **Faltar la tasa bloquea la emisión.** Es deliberado: es preferible no poder facturar a facturar con tasa 1 y
  descubrir el desastre al cierre de mes.
- Las tasas `legal` y `manual` no se mezclan: son series independientes.
- `convert()` cruza siempre por bolívares y **no redondea**. Redondea quien persiste, según `amount_decimals`.

---

## 4. Qué guarda cada documento

Los importes se guardan en la **moneda del documento**, más las tasas necesarias para reexpresarlos sin recalcular
históricos:

| Columna                | Tipo             | Descripción                                                                     |
|------------------------|------------------|---------------------------------------------------------------------------------|
| `currency`             | `string(3)`      | Moneda en la que se emite el documento.                                         |
| `exchange_rate`        | `decimal(18,8)`  | Bolívares por 1 unidad de `currency`. Congelada al confirmar.                   |
| `base_currency`        | `string(3)`      | Moneda principal de la empresa **en ese momento**.                              |
| `base_exchange_rate`   | `decimal(18,8)`  | Bolívares por 1 unidad de `base_currency`. Congelada al confirmar.              |

Los dos pares son necesarios. El primero da el monto en bolívares; el segundo permite reexpresar en la moneda de la
empresa **aunque esa moneda cambie mañana**. Sin `base_currency` congelada, cambiar la moneda principal reescribiría el
significado de todo el histórico.

**Montos en bolívares persistidos:** solo en **facturas y pagos**, que son los documentos con valor legal
(`subtotal_ves`, `tax_amount_ves`, `total_ves`, **solo en la cabecera**). Las órdenes de venta y de compra muestran el
equivalente calculado al vuelo, sin columnas `_ves`: un pedido no compromete un monto en bolívares.

Las **líneas** solo llevan importes en la moneda del documento. Duplicar ocho columnas por línea no aporta nada que no
salga de la cabecera.

### Ejemplos

Empresa con `base_currency = USD`, `secondary_currency = VES`. Tasas del 16/08: USD 36,50 · EUR 40,00.

**Venta en la moneda principal** — 1.000 USD:

| Columna                            | Valor       |
|------------------------------------|-------------|
| `currency` / `exchange_rate`       | USD / 36,50 |
| `base_currency` / `base_exchange_rate` | USD / 36,50 |
| `total`                            | 1.000,00    |
| `total_ves` (solo factura y pago)  | 36.500,00   |

**Compra en euros a un proveedor europeo** — 500 EUR:

| Columna                            | Valor       |
|------------------------------------|-------------|
| `currency` / `exchange_rate`       | EUR / 40,00 |
| `base_currency` / `base_exchange_rate` | USD / 36,50 |
| `total`                            | 500,00      |

De ahí sale todo sin consultar nada más: en bolívares son `500 × 40,00 = 20.000`; en la moneda de la empresa,
`20.000 ÷ 36,50 = 547,95 USD`. Ese último número es el que suma en los reportes, y queda reproducible dentro de diez
años.

**Empresa que trabaja en bolívares** — `base_currency = VES`, `secondary_currency = VES`: `dual_currency` es `false`,
`rateFor()` devuelve 1 y los importes salen en una sola línea.

---

## 5. Congelado de la tasa

| Estado del documento     | Tasa                                                          |
|--------------------------|---------------------------------------------------------------|
| `draft`                  | Se refresca del catálogo cada vez que se abre o se guarda.    |
| `confirmed` en adelante  | Congelada para siempre. Nunca se recalcula.                   |

- La **factura** usa la tasa de **su fecha de emisión**, no la de la orden que le dio origen.
- El **pago** usa la de **su fecha de pago**.

### Diferencial cambiario

Consecuencia directa de lo anterior, y hay que preverlo:

Se factura 1.000 USD el 16/08 con tasa 36,50 → quedan **36.500 Bs** congelados en la factura. El cliente paga los
1.000 USD el 30/08, cuando la tasa está en 38,00 → **38.000 Bs**.

En dólares la deuda queda saldada exacta. En bolívares sobran **1.500 Bs**. Eso no es un error de redondeo: es una
cuenta contable real que hay que registrar como diferencial cambiario. El esquema ya lo permite porque cada documento
carga su propia tasa; el asiento correspondiente está pendiente de definir junto con los módulos de
[Ventas](ventas.md) y [Compras](compras.md).

---

## 6. Precios de artículos

Cada lista de precio tiene su propia moneda (`app_item_prices.currency`), independiente de la del documento. Al capturar
una línea, el precio se convierte con `convert()` a la moneda del documento y queda congelado en `unit_price`.

Un artículo con precio en euros vendido en una orden en dólares se convierte **una vez**, al capturar la línea. La línea
no recuerda que el precio venía en euros: eso pertenece a la lista, no al documento.

---

## 7. Presentación

- El símbolo de cada moneda sale del catálogo global (`app_currencies.symbol`), compartido por Inertia. Ninguna pantalla
  declara símbolos propios.
- Cuando `dual_currency` es `true`, cada importe se muestra en dos líneas: la moneda del documento arriba y el
  equivalente en bolívares debajo, en tono atenuado.
- Cuando es `false`, una sola línea. La decisión se toma en un solo componente, no en cada pantalla.

---

## 8. Estado de implementación

| Pieza                                                                     | Estado     |
|---------------------------------------------------------------------------|------------|
| Catálogo global de monedas (`app_currencies`) + regla `ActiveCurrency`     | Hecho      |
| Semántica de la tasa + regla `ForeignCurrency` + `Currency::LOCAL_CODE`    | Hecho      |
| `ExchangeRateResolver` (`rateFor`, `convert`, caché por request)           | Hecho      |
| Módulo `Configuration` + creación automática + props compartidas           | Hecho      |
| `lib/money.ts` + componente de importe dual + `todayRates` compartidas     | Pendiente  |
| `base_currency` / `base_exchange_rate` en órdenes de venta y compra        | Pendiente  |
| Órdenes que piden la tasa al resolver en vez de aceptarla del formulario   | Pendiente  |
| Facturas y pagos (con `_ves` congelados)                                   | Pendiente  |
| Registro del diferencial cambiario                                         | Por definir |

> **Hueco conocido:** hasta que las órdenes pidan la tasa al resolver, `exchange_rate` es el número que manda el
> formulario (`required|numeric|gt:0`, por defecto `1`) y nadie lo contrasta contra el catálogo. Un usuario puede
> registrar una orden en euros con tasa 1 y el sistema la acepta.
